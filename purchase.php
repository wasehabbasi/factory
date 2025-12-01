<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include "./db/db.php";

header('Content-Type: application/json');

// GET: fetch all purchases
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT p.*, v.name as vendor_name
            FROM purchase p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN products pr ON p.product_id = pr.id
            ORDER BY p.id DESC";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
    exit();
}

// POST: add/update purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $purchase_date = $_POST['purchase_date'] ?? null;
    $vendor_id = $_POST['vendor_id'] ?? null;
    $rate = $_POST['rate'] ?? 0;
    $lot_number = $_POST['lot_number'] ?? '';
    $measurement = $_POST['measurement'] ?? '';
    $product_name = $_POST['product_name'] ?? '';
    $width = $_POST['width'] ?? '';
    $thaan = $_POST['thaan'] ?? '';
    $issue_meter = $_POST['issue_meter'] ?? '';
    $warehouse_id = $_POST['warehouse_id'] ?? 1; // default warehouse (change if dynamic)

    if (!$purchase_date || !$vendor_id || !$lot_number || !$product_name) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    if ($id) {
        // ✅ Update purchase
        $stmt = $conn->prepare("UPDATE purchase 
            SET date=?, vendor_id=?, rate=?, lot_number=?, measurement=?, product_name=?, width=?, thaan=?, issue_meter=? 
            WHERE id=?");
        $stmt->bind_param("sidssssssi", $purchase_date, $vendor_id, $rate, $lot_number, $measurement, $product_name, $width, $thaan, $issue_meter, $id);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        // ✅ Insert purchase
        $stmt = $conn->prepare("INSERT INTO purchase 
            (date, vendor_id, rate, lot_number, measurement, product_name, width, thaan, issue_meter) 
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sidssssss", $purchase_date, $vendor_id, $rate, $lot_number, $measurement, $product_name, $width, $thaan, $issue_meter);
        $success = $stmt->execute();
        $stmt->close();


        $ins = $conn->prepare("INSERT INTO products (name, lot_number, measurement, vendor_id, unit, cost, price, type) 
                                   VALUES (?, ?, ?, ?, ?, ?, 0, 'purchase')");
        $ins->bind_param("sssiii", $product_name, $lot_number, $measurement, $vendor_id, $issue_meter, $rate);
        $ins->execute();
        $product_id = $conn->insert_id;
        $ins->close();
    }

    if ($success) {
        // ✅ 1. Check if product exists
        $check = $conn->prepare("SELECT id FROM products WHERE lot_number = ? OR measurement = ?");
        $check->bind_param("ss", $lot_number, $product_name);
        $check->execute();
        $result = $check->get_result();
        $product = $result->fetch_assoc();
        $check->close();

        if ($product) {
            // Update existing product
            $product_id = $product['id'];
            $upd = $conn->prepare("UPDATE products SET measurement=? WHERE id=?");
            $upd->bind_param("si", $measurement, $product_id);
            $upd->execute();
            $upd->close();
        }

        // ✅ 2. Insert into product_lots (only for new purchases)
        $expiry_date = null; // optional — or set via POST
        $qty = (float)$issue_meter;

        $lotInsert = $conn->prepare("
            INSERT INTO product_lots (product_id, lot_number, expiry_date, vendor_id, warehouse_id, qty)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
        ");
        $lotInsert->bind_param("issiid", $product_id, $lot_number, $expiry_date, $vendor_id, $warehouse_id, $qty);
        $lotInsert->execute();
        $lotInsert->close();

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error"]);
    }

    exit();
}

// DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $del_vars);
    $id = $del_vars['id'] ?? 0;
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM purchase WHERE id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $success]);
    }
    exit();
}
$conn->close();

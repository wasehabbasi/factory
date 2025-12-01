<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include './db/db.php';
session_start();
header('Content-Type: application/json');
// error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT p.*, 
                   v.name AS vendor_name, 
                   w.name AS warehouse_name
            FROM products p
            LEFT JOIN vendors v ON p.vendor_id = v.id
            LEFT JOIN warehouses w ON p.warehouse_id = w.id
            ORDER BY p.id DESC";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

if ($method === 'POST') {
    $id = $_POST['id'] ?? null;
    $sku = $_POST['sku'] ?? null;
    $name = $_POST['name'];
    $warehouse_id = $_POST['warehouse_id'] ?? null;
    $unit = $_POST['unit'] ?? 'pcs';
    $lot_number = $_POST['lot_number'] ?? null;
    $vendor_id = $_POST['vendor_id'] ?? null;
    $cost = $_POST['cost'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $type = $_POST['type'] ?? null;

    if ($id) {
        $stmt = $conn->prepare("UPDATE products 
            SET sku=?, name=?, warehouse_id=?, unit=?, lot_number=?, vendor_id=?, cost=?, price=?, type=? 
            WHERE id=?");
        $stmt->bind_param("ssissiddsi", $sku, $name, $warehouse_id, $unit, $lot_number, $vendor_id, $cost, $price, $type, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO products 
            (sku, name, warehouse_id, unit, lot_number, vendor_id, cost, price, type)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssissidds", $sku, $name, $warehouse_id, $unit, $lot_number, $vendor_id, $cost, $price, $type);
    }

    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => $success]);
    exit;
}

if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $del_vars);
    $id = $del_vars['id'] ?? 0;
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(["success" => $success]);
    }
    exit;
}

$conn->close();
<?php
// factories.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "./db/db.php"; // DB connection

$uploadDir = './uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

header('Content-Type: application/json');

// DELETE: delete factory
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);
    $id = $deleteData['id'] ?? null;
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM factories WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID missing']);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'get_products') {
    $res = $conn->query("SELECT id, name, lot_number FROM products WHERE lot_number IS NOT NULL");
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'save_invoice') {
    $factory_id       = intval($_POST['factory_id'] ?? 0);
    $product_id       = intval($_POST['product_id'] ?? 0);
    $lot_number       = trim($_POST['lot_number'] ?? '');
    $total_meter      = floatval($_POST['total_meter'] ?? 0);
    $per_meter_rate   = floatval($_POST['per_meter_rate'] ?? 0);
    $rejection        = floatval($_POST['rejection'] ?? 0);
    $advance_adjusted = floatval($_POST['advance_adjusted'] ?? 0);

    // ✅ Validation
    if ($factory_id <= 0 || $product_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid factory or product"]);
        exit;
    }

    // ✅ Check if product exists
    $check = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check->bind_param("i", $product_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Product not found"]);
        exit;
    }

    // ✅ Calculate total and net amount
    $total_amount = $total_meter * $per_meter_rate;
    $net_amount   = $total_amount - $rejection - $advance_adjusted;

    // ✅ INSERT or UPDATE
    if (!empty($_POST['id'])) {
        // ---------------------- UPDATE EXISTING INVOICE ----------------------
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("
            UPDATE factory_invoices 
            SET factory_id=?, product_id=?, lot_number=?, total_meter=?, per_meter_rate=?, rejection=?, advance_adjusted=?, total_amount=?, net_amount=? 
            WHERE id=?
        ");
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }

        $stmt->bind_param(
            "iisddddddi",
            $factory_id,
            $product_id,
            $lot_number,
            $total_meter,
            $per_meter_rate,
            $rejection,
            $advance_adjusted,
            $total_amount,
            $net_amount,
            $id
        );

        $stmt->execute();
        
        $stmt2 = $conn->prepare("
            INSERT INTO factory_ledger 
            (factory_id, product_id, lot_number, total_meter, per_meter_rate, rejection, advance_adjusted, total_amount, net_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt2) {
            echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }

        $stmt2->bind_param(
            "iisdddddd",
            $factory_id,
            $product_id,
            $lot_number,
            $total_meter,
            $per_meter_rate,
            $rejection,
            $advance_adjusted,
            $total_amount,
            $net_amount
        );

        $stmt2->execute();
        
        if ($stmt->affected_rows >= 0) {
            echo json_encode(["success" => true, "message" => "Invoice updated successfully", "invoice_id" => $id]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update invoice"]);
        }

        

    } else {
        // ---------------------- INSERT NEW INVOICE ----------------------
        $stmt = $conn->prepare("
            INSERT INTO factory_invoices 
            (factory_id, product_id, lot_number, total_meter, per_meter_rate, rejection, advance_adjusted, total_amount, net_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }

        $stmt->bind_param(
            "iisdddddd",
            $factory_id,
            $product_id,
            $lot_number,
            $total_meter,
            $per_meter_rate,
            $rejection,
            $advance_adjusted,
            $total_amount,
            $net_amount
        );

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Invoice added successfully", "invoice_id" => $stmt->insert_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to add invoice"]);
        }

        $stmt = $conn->prepare("
            INSERT INTO factory_ledger 
            (factory_id, product_id, lot_number, total_meter, per_meter_rate, rejection, advance_adjusted, total_amount, net_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }

        $stmt->bind_param(
            "iisdddddd",
            $factory_id,
            $product_id,
            $lot_number,
            $total_meter,
            $per_meter_rate,
            $rejection,
            $advance_adjusted,
            $total_amount,
            $net_amount
        );

        $stmt->execute();
    }

    exit;
}



// GET: fetch all factories
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM factories ORDER BY id DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
    exit();
}

// POST: add or edit factory
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';

    $image_url = '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('factory_') . '.' . $ext;
        $destination = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $destination)) {
            $image_url = $destination;
        }
    }

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit();
    }

    if ($id) { // update
        $stmt = $conn->prepare("UPDATE factories SET name=?, address=?, phone=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $address, $phone, $image_url, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'factory' => ['id' => $id, 'name' => $name, 'address' => $address, 'phone' => $phone, 'image_url' => $image_url]]);
    } else { // add
        $stmt = $conn->prepare("INSERT INTO factories (name,address,phone,image_url) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $name, $address, $phone, $image_url);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'factory' => ['id' => $newId, 'name' => $name, 'address' => $address, 'phone' => $phone, 'image_url' => $image_url]]);
    }
}

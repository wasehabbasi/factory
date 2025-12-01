<?php
session_start();
include "./db/db.php";

$uploadDir = 'uploads/shops/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $image_url = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) $image_url = $targetPath;
    } elseif (!empty($_POST['existing_image'])) $image_url = $_POST['existing_image'];

    if (empty($name) || empty($address) || empty($phone_number)) {
        echo json_encode(['success' => false, 'message' => 'Name, Address and Phone required']);
        exit();
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE shops SET name=?, address=?, phone_number=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $address, $phone_number, $image_url, $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'shop' => ['id' => $id, 'name' => $name, 'address' => $address, 'phone_number' => $phone_number, 'image_url' => $image_url]]);
    } else {
        $stmt = $conn->prepare("INSERT INTO shops (name,address,phone_number,image_url) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $name, $address, $phone_number, $image_url);
        $stmt->execute();
        $newId = $stmt->insert_id;
        echo json_encode(['success' => true, 'shop' => ['id' => $newId, 'name' => $name, 'address' => $address, 'phone_number' => $phone_number, 'image_url' => $image_url]]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'] ?? null;
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM shops WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else echo json_encode(['success' => false, 'message' => 'ID missing']);
}


if (isset($_GET['action']) && $_GET['action'] === 'get_invoices') {
    $result = $conn->query("
        SELECT id, date, customer_name, paandi_name, grand_total
        FROM shop_invoices
        ORDER BY id DESC
    ");

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// Now the generic GET block comes AFTER the action check
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM shops ORDER BY id DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}


<?php
session_start();
include "./db/db.php";

// GET: fetch all buyers
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM buyers ORDER BY id DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// POST: add/edit buyer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $image_url = null;

    if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'uploads/buyers/' . uniqid() . '.' . $ext;
        if (!is_dir('uploads/buyers')) mkdir('uploads/buyers', 0777, true);
        move_uploaded_file($_FILES['image']['tmp_name'], $filename);
        $image_url = $filename;
    }

    if (empty($name) || empty($address) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Name, Address and Phone are required']);
        exit();
    }

    if ($id) { // edit
        if ($image_url) {
            $stmt = $conn->prepare("UPDATE buyers SET name=?, address=?, phone=?, image_url=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $address, $phone, $image_url, $id);
        } else {
            $stmt = $conn->prepare("UPDATE buyers SET name=?, address=?, phone=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $address, $phone, $id);
        }
        $success = $stmt->execute();
        echo json_encode(['success' => $success]);
    } else { // add
        $stmt = $conn->prepare("INSERT INTO buyers (name,address,phone,image_url) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $name, $address, $phone, $image_url);
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'id' => $stmt->insert_id]);
    }
    exit();
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'] ?? null;
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM buyers WHERE id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID missing']);
    }
    exit();
}

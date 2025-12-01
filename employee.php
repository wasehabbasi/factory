<?php
// employee.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "./db/db.php";

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action == 'add') {
    $name = $_POST['name'];
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $joining_date = $_POST['joining_date'] ?? null;

    $stmt = $conn->prepare("INSERT INTO employees (name, email, phone, address, designation, joining_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $phone, $address, $designation, $joining_date);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "employee" => ["id" => $conn->insert_id, "name" => $name, "email" => $email, "phone" => $phone, "address" => $address, "designation" => $designation, "joining_date" => $joining_date]]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
} elseif ($action == 'update') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $joining_date = $_POST['joining_date'] ?? null;

    $stmt = $conn->prepare("UPDATE employees SET name=?, email=?, phone=?, address=?, designation=?, joining_date=? WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $email, $phone, $address, $designation, $joining_date, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
} elseif ($action == 'delete') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
} elseif ($action == 'fetch') {
    $result = $conn->query("SELECT * FROM employees ORDER BY id DESC");
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $employees]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}

$conn->close();

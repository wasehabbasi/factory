<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "./db/db.php";
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $factory_id = $_POST['factory_id'] ?? 0;
    $total_meter = $_POST['total_meter'] ?? 0;
    $lot_number = $_POST['lot_number'] ?? '';
    $per_meter_rate = $_POST['per_meter_rate'] ?? 0;
    $rejection = $_POST['rejection'] ?? 0;
    $remarks = $_POST['remarks'] ?? '';

    if (!$factory_id || !$total_meter || !$lot_number || !$per_meter_rate) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    // Generate unique invoice no
    $prefix = "FAC-";
    $result = $conn->query("SELECT COUNT(*) AS count FROM factory_invoices");
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1;
    $invoice_no = $prefix . str_pad($count, 4, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO factory_invoices (invoice_no, factory_id, total_meter, lot_number, per_meter_rate, rejection, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidddds", $invoice_no, $factory_id, $total_meter, $lot_number, $per_meter_rate, $rejection, $remarks);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "invoice_no" => $invoice_no]);
    } else {
        echo json_encode(["success" => false, "message" => $conn->error]);
    }
    exit;
}

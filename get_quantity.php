<?php
include './db/db.php';
header('Content-Type: application/json');

$lot = $_GET['lot_number'] ?? '';

if (!$lot) {
    echo json_encode(['success' => false, 'message' => 'Missing lot number']);
    exit;
}

$stmt = $conn->prepare("SELECT quantity FROM send_inventories WHERE lot_number = ?");
$stmt->bind_param("s", $lot);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    echo json_encode(['success' => true, 'quantity' => (int)$row['quantity']]);
} else {
    echo json_encode(['success' => false, 'message' => 'No record found']);
}

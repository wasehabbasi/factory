<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include './db/db.php';

$id = $_POST['id'] ?? null;

if ($id) {
    $stmt = $conn->prepare("
        SELECT 
            fi.*, 
            p.name AS product_name
        FROM factory_invoices AS fi
        LEFT JOIN products AS p ON p.id = fi.product_id
        WHERE fi.id = ?
    ");
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
}

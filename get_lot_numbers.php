<?php
include './db/db.php';
header('Content-Type: application/json');

$vendor_id = intval($_GET['vendor_id'] ?? 0);

if (!$vendor_id) {
    echo json_encode(["success" => false, "message" => "Invalid parameters"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT DISTINCT lot_number FROM `product_lots` WHERE lot_number IS NOT NULL AND lot_number <> '' AND vendor_id = ? ORDER BY lot_number ASC");
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row['lot_number'];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

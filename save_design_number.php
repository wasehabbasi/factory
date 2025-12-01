<?php
include "./db/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$product_id = $data['product_id'] ?? null;
$design_number = $data['design_number'] ?? null;


echo $product_id;
echo $design_number;

if (!$product_id || !$design_number) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$stmt = $conn->prepare("UPDATE products SET design_number = ? WHERE id = ?");
$stmt->bind_param("si", $design_number, $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();

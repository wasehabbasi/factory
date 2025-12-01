<?php
include "./db/db.php";
header("Content-Type: application/json; charset=utf-8");

$vendor_id = intval($_GET['vendor_id'] ?? 0);
$lot_number = trim($_GET['lot_number'] ?? '');

if (!$vendor_id || !$lot_number) {
    echo json_encode(["success" => false, "message" => "Invalid parameters"]);
    exit;
}

// Fetch rate and issue_meter from purchase
$stmt = $conn->prepare("SELECT rate, issue_meter FROM purchase WHERE vendor_id = ? AND lot_number = ?");
$stmt->bind_param("is", $vendor_id, $lot_number);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
while ($row = $result->fetch_assoc()) {
    $rate = floatval($row['rate'] ?? 0);
    $issue_meter = floatval($row['issue_meter'] ?? 0);
    $total += ($rate * $issue_meter);
}

$stmt->close();

echo json_encode([
    "success" => true,
    "total_amount" => $total
]);
exit;

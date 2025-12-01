<?php
include "./db/db.php";
header("Content-Type: application/json");

$vendor_id = intval($_GET['vendor_id'] ?? 0);
$lot_number = $_GET['lot_number'] ?? '';

if (!$vendor_id || !$lot_number) {
    echo json_encode(["success" => false, "message" => "Missing vendor or lot number"]);
    exit;
}

try {
    // Get rate and issue_meter from purchase
    $stmt1 = $conn->prepare("SELECT rate, issue_meter FROM purchase WHERE vendor_id = ? AND lot_number = ? LIMIT 1");
    $stmt1->bind_param("is", $vendor_id, $lot_number);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $purchase = $result1->fetch_assoc();

    // Get rejection from receive_inventories
    $stmt2 = $conn->prepare("SELECT rejection FROM receive_inventories WHERE vendor_id = ? AND lot_number = ? LIMIT 1");
    $stmt2->bind_param("is", $vendor_id, $lot_number);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $receive = $result2->fetch_assoc();

    if ($purchase || $receive) {
        echo json_encode([
            "success" => true,
            "rate" => $purchase['rate'] ?? null,
            "issue_meter" => $purchase['issue_meter'] ?? null,
            "rejection" => $receive['rejection'] ?? null
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "No record found"]);
    }

    $stmt1->close();
    $stmt2->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

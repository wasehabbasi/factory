<?php
include "./db/db.php";
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';

if ($action == "list") {
    $sql = "SELECT 
    p.date AS purchase_date,
    v.name AS vendor_name,
    p.lot_number,
    p.measurement,
    -- Agar product_id null hai to direct purchase.product_name use karo
    COALESCE(pr.name, p.product_name) AS product_name,
    p.width,
    p.thaan,
    p.issue_meter,
    COALESCE(SUM(si.quantity),0) AS net_gazana,
    COALESCE(SUM(ri.receive_quantity),0) AS fresh_gazana,
    p.rate,
    MAX(b.l_kmi) AS l_kmi,
    MAX(ri.rejection) AS rejection,
    ri.shortage AS shortage,
    b.remaining_meter,
    b.final_remarks
FROM purchase p
LEFT JOIN vendors v ON p.vendor_id = v.id
LEFT JOIN products pr ON p.product_id = pr.id
LEFT JOIN send_inventories si ON p.lot_number = si.lot_number
LEFT JOIN receive_inventories ri ON p.lot_number = ri.lot_number
LEFT JOIN balance_sheet b ON p.lot_number = b.lot_number
GROUP BY p.lot_number
ORDER BY p.date DESC
";

    $res = $conn->query($sql);
    echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action == "update") {
    $lot = $_POST['lot_number'];
    $l_kmi = $_POST['l_kmi'] ?? null;
    $remaining = $_POST['remaining_meter'] ?? 0;
    $remarks = $_POST['final_remarks'] ?? null;

    $check = $conn->prepare("SELECT id FROM balance_sheet WHERE lot_number=?");
    $check->bind_param("i", $lot);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE balance_sheet SET l_kmi=?, remaining_meter=?, final_remarks=? WHERE lot_number=?");
        $stmt->bind_param("sdsi", $l_kmi, $remaining, $remarks, $lot);
    } else {
        $stmt = $conn->prepare("INSERT INTO balance_sheet (l_kmi, remaining_meter, final_remarks, lot_number) VALUES (?,?,?,?)");
        $stmt->bind_param("sdsi", $l_kmi, $remaining, $remarks, $lot);
    }
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["error" => "Invalid action"]);
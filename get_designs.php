<?php
include "./db/db.php";

header('Content-Type: application/json');

// Fetch all unique design numbers with related product info
$sql = "
SELECT 
    wts.design_number,
    wts.lot_number,
    wts.product_id,
    p.name AS product_name
FROM warehouse_to_shop wts
LEFT JOIN products p ON p.id = wts.product_id
WHERE wts.design_number IS NOT NULL
GROUP BY wts.design_number, wts.product_id
ORDER BY wts.design_number ASC
";

$result = $conn->query($sql);
$designs = [];

while ($row = $result->fetch_assoc()) {
    $designs[] = $row;
}

echo json_encode(['success' => true, 'data' => $designs]);
?>

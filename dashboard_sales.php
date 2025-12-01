<?php
include "./db/db.php";
header("Content-Type: application/json");

$shop_id = isset($_GET['shop_id']) && $_GET['shop_id'] !== 'all' ? intval($_GET['shop_id']) : null;
$filter = $_GET['filter'] ?? 'all'; // daily | monthly | yearly | all

$where = [];
if ($shop_id) {
    $where[] = "si.shop_id = $shop_id";
}
if ($filter === 'daily') {
    $where[] = "si.date = CURDATE()";
} elseif ($filter === 'monthly') {
    $where[] = "MONTH(si.date) = MONTH(CURDATE()) AND YEAR(si.date) = YEAR(CURDATE())";
} elseif ($filter === 'yearly') {
    $where[] = "YEAR(si.date) = YEAR(CURDATE())";
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT 
            sh.name AS shop_name,
            SUM(si.grand_total) AS total_sale,
            SUM(sii.qty) AS total_quantity,
            SUM(sii.total_suits) AS total_suits
        FROM shop_invoices si
        LEFT JOIN shops sh ON si.shop_id = sh.id
        LEFT JOIN shop_sales sii ON sii.invoice_id = si.id
        $where_sql
        GROUP BY si.shop_id
        ORDER BY sh.name ASC";

$res = $conn->query($sql);
$data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

echo json_encode(["success" => true, "data" => $data]);
exit;
?>

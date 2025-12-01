<?php
include "./db/db.php";
header("Content-Type: application/json");

// Fetch all products
$sql = "SELECT id, name FROM products ORDER BY name ASC";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
} else {
    echo json_encode(["success" => false, "data" => []]);
}
exit;
?>

<?php
require_once 'db/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'read') {

    $sql = "
        SELECT 
            fi.id,
            f.name AS factory_name,
            p.name AS product_name,
            fi.lot_number,
            fi.total_meter,
            fi.per_meter_rate,
            (fi.total_meter * fi.per_meter_rate) AS total_amount,
            fi.rejection,
            fi.advance_adjusted,
            ((fi.total_meter * fi.per_meter_rate) - fi.rejection) AS net_amount,
            fi.created_at
        FROM factory_invoices fi
        LEFT JOIN factories f ON fi.factory_id = f.id
        LEFT JOIN products p ON fi.product_id = p.id
        ORDER BY fi.id DESC
    ";

    $res = $conn->query($sql);
    $invoices = [];

    while ($row = $res->fetch_assoc()) {
        $invoices[] = $row;
    }

    echo json_encode($invoices);
    exit;
}

// --- Invalid action ---
echo json_encode(["error" => "Invalid action"]);
exit;

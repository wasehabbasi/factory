<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "./db/db.php";
header("Content-Type: application/json; charset=utf-8");

// Get action from GET or POST
$action = $_REQUEST['action'] ?? '';

// --- List Transfers ---
if ($action === 'list_transfers') {
    $sql = "SELECT s.*, 
                   w.name AS warehouse_name, 
                   sh.name AS shop_name, 
                   p.name AS product_name, 
                   p.measurement AS measurement,
                   s.nag AS nag,
                   s.design_number as design_number
            FROM warehouse_to_shop s
            LEFT JOIN warehouses w ON s.warehouse_id = w.id
            LEFT JOIN shops sh ON s.shop_id = sh.id
            LEFT JOIN products p ON s.product_id = p.id
            ORDER BY s.id DESC";
    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}


// --- List Shop Inventory (remaining items only) ---
if ($action === 'shop_inventory' && isset($_REQUEST['shop_id'])) {
    $shop_id = intval($_REQUEST['shop_id']);
    $sql = "SELECT s.id AS transfer_id, s.product_id, p.name AS product_name, p.measurement, s.qty, s.remaining_qty
            FROM warehouse_to_shop s
            LEFT JOIN products p ON s.product_id = p.id
            WHERE s.shop_id = ? AND s.remaining_qty > 0
            ORDER BY s.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

// --- Save Transfer ---
if ($action === 'save_transfer') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $warehouse_id = intval($_POST['warehouse_id'] ?? 0);
    $shop_id = intval($_POST['shop_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);
    $lot_number = trim($_POST['lot_number'] ?? '');
    $design_number = trim($_POST['design_number'] ?? '');
    $nag = intval($_POST['nag'] ?? 0);
    $qty = floatval($_POST['qty'] ?? 0);

    $remaining = $qty;

    $stmt = $conn->prepare("
    SELECT 
        p.id AS product_id,
        SUM(r.receive_quantity) AS total_quantity,
        SUM(r.nag) AS total_nag
    FROM receive_inventories r
    LEFT JOIN factories f ON r.factory_id = f.id
    LEFT JOIN vendors v ON r.vendor_id = v.id
    LEFT JOIN warehouses w ON r.warehouse_id = w.id
    LEFT JOIN product_lots pl ON pl.lot_number = r.lot_number
    LEFT JOIN products p ON pl.product_id = p.id
    WHERE p.id = ?
    GROUP BY p.id
    ORDER BY p.id DESC
");
    $stmt->bind_param("i", $product_id); // assuming product_id is integer
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_quantity = (float) $row['total_quantity'];
    $total_nag = (float) $row['total_nag'];

    if ($total_quantity) {
        if ($total_quantity >= $qty && $total_nag >= $nag) {

            $stmt = $conn->prepare("SELECT SUM(qty) AS total_qty, SUM(nag) AS total_nag 
                  FROM warehouse_to_shop 
                  WHERE design_number = ? AND lot_number = ?;
                  ");
            $stmt->bind_param("is", $design_number, $lot_number);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $total_nags = $row['total_nag'];
                $total_qty = $row['total_qty'];

                if ($total_qty > $total_quantity) {
                    echo json_encode(["message" => "Total quantity is less then you have provided"]);
                    exit;
                } else if ($total_nags > $total_nag) {
                    echo json_encode(["message" => "Total nag is less then you have provided"]);
                    exit;
                } else {

                    $stmt = $conn->prepare(
                        "INSERT INTO warehouse_to_shop 
            (date, warehouse_id, shop_id, product_id, lot_number, design_number, nag, qty, remaining_qty)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param("siiiisidd", $date, $warehouse_id, $shop_id, $product_id, $lot_number, $design_number, $nag, $qty, $remaining);

                    $ok = $stmt->execute();
                    echo json_encode(["success" => $ok, "message" => $ok ? "Saved" : $conn->error]);
                    exit;
                }
            } else {
                echo json_encode(["message" => "Total quantity or total nag is less then you have provided"]);
                exit;
            }
        }
    } else {
        echo json_encode(["message" => "It doesnot have any quantity"]);
        exit;
    }
}

if ($action === 'get_design_numbers') {
    $sql = "
        SELECT DISTINCT 
            r.design_number, 
            p.id AS product_id, 
            p.measurement AS measurement,
            p.name AS product_name, 
            pl.lot_number, 
            r.nag
        FROM receive_inventories r
        LEFT JOIN product_lots pl ON r.lot_number = pl.lot_number
        LEFT JOIN products p ON pl.product_id = p.id
        ORDER BY r.design_number ASC
    ";

    $res = $conn->query($sql);
    $designs = [];
    while ($row = $res->fetch_assoc()) {
        $designs[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $designs]);
    exit;
}

// --- Save Invoice ---
if ($action === 'save_invoice') {
    $transfer_id = intval($_POST['transfer_id'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');
    $qty = floatval($_POST['qty'] ?? 0);
    $rate = floatval($_POST['rate'] ?? 0);
    $detail = trim($_POST['detail'] ?? '');
    $total = round($qty * $rate, 2);

    if (!$transfer_id || $qty <= 0 || $customer_name === '') {
        echo json_encode(["success" => false, "message" => "Invalid data"]);
        exit;
    }

    // Fetch transfer
    $stmt = $conn->prepare("SELECT id, shop_id, remaining_qty FROM warehouse_to_shop WHERE id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $transfer = $res->fetch_assoc();
    if (!$transfer) {
        echo json_encode(["success" => false, "message" => "Transfer not found"]);
        exit;
    }

    if ($qty > floatval($transfer['remaining_qty'])) {
        echo json_encode(["success" => false, "message" => "Invoice qty exceeds remaining quantity"]);
        exit;
    }

    // Generate invoice number
    $invoice_no = 'SI-' . date('YmdHis') . '-' . rand(100, 999);

    // Insert invoice
    $stmt = $conn->prepare("INSERT INTO shop_invoices (invoice_no, transfer_id, shop_id, customer_name, date, grand_total) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siissi", $invoice_no, $transfer_id, $transfer['shop_id'], $customer_name, $date, $total);
    $ok = $stmt->execute();
    if (!$ok) {
        echo json_encode(["success" => false, "message" => $conn->error]);
        exit;
    }
    $invoice_id = $stmt->insert_id;

    // Insert invoice item
    $stmt = $conn->prepare("INSERT INTO shop_invoice_items (invoice_id, description, qty, rate, total) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isddd", $invoice_id, $detail, $qty, $rate, $total);
    $stmt->execute();

    // Update remaining_qty
    $new_rem = floatval($transfer['remaining_qty']) - $qty;
    $stmt = $conn->prepare("UPDATE warehouse_to_shop SET remaining_qty = ? WHERE id = ?");
    $stmt->bind_param("di", $new_rem, $transfer_id);
    $stmt->execute();

    echo json_encode(["success" => true, "invoice_id" => $invoice_id, "invoice_no" => $invoice_no]);
    exit;
}

// --- List Shop Totals ---
if ($action === 'list_shop_totals') {
    // ✅ 1. Get shop totals (with warehouse & temp adjustments)
    $sql = "
        SELECT 
            sh.id AS shop_id,
            sh.name AS shop_name,
            COALESCE(SUM(s.qty), 0) + COALESCE((
                SELECT SUM(adjusted_qty) 
                FROM temp_quantity_adjustments t 
                WHERE t.shop_id = sh.id
            ), 0) AS total_qty
        FROM shops sh
        LEFT JOIN warehouse_to_shop s ON s.shop_id = sh.id
        GROUP BY sh.id
        ORDER BY sh.name ASC
    ";
    $res = $conn->query($sql);

    $data = [];

    if ($res && $res->num_rows > 0) {
        $shops = $res->fetch_all(MYSQLI_ASSOC);

        // ✅ 2. Loop through each shop to calculate sold quantity
        foreach ($shops as $shop) {
            $shop_id = $shop['shop_id'];
            $total_qty = $shop['total_qty'];

            $sql2 = "
                SELECT COALESCE(SUM(SS.qty), 0) AS sold_qty
                FROM shop_invoices AS SI
                LEFT JOIN shop_sales AS SS ON SI.id = SS.invoice_id
                WHERE SI.shop_id = $shop_id
            ";

            $res2 = $conn->query($sql2);
            $sold_qty = 0;

            if ($res2 && $res2->num_rows > 0) {
                $row2 = $res2->fetch_assoc();
                $sold_qty = $row2['sold_qty'];
            }

            // ✅ Calculate remaining qty (and prevent negative)
            $real_qty = max(0, $total_qty - $sold_qty);

            $data[] = [
                'shop_id' => $shop_id,
                'shop_name' => $shop['shop_name'],
                'total_qty' => $real_qty
            ];
        }
    } else {
        // ✅ Fallback
        $data = [[
            "shop_id"   => 0,
            "shop_name" => "000",
            "total_qty" => 0
        ]];
    }

    echo json_encode(["success" => true, "data" => $data]);
    exit;
}


if ($action === 'list_unique_shop_inventory') {
    // ✅ Step 1: Aggregate total_quantity per shop + product (and related info)
    $sql = "
        SELECT 
            p.name AS product_name,
            p.id AS product_id,
            p.measurement,
            sh.name AS shop_name,
            sh.id AS shop_id,
            s.design_number,
            COALESCE(tnu.new_nag, s.nag) AS nag,
            SUM(s.remaining_qty) AS total_quantity -- ✅ combine quantities for same shop/product
        FROM warehouse_to_shop s
        LEFT JOIN products p ON s.product_id = p.id
        LEFT JOIN shops sh ON s.shop_id = sh.id
        LEFT JOIN temp_nag_updates tnu ON tnu.wts_id = s.id
        GROUP BY s.shop_id, s.product_id, p.measurement, s.design_number, nag
        ORDER BY sh.name, p.name, s.design_number;
    ";

    $res = $conn->query($sql);
    $final_data = [];

    if ($res && $res->num_rows > 0) {
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $row) {
            $shop_id = $row['shop_id'];
            $product_id = $row['product_id'];
            $total_quantity = $row['total_quantity'];

            // ✅ Step 2: Find total sold quantity of that product for that shop
            $sql2 = "
                SELECT COALESCE(SUM(SS.qty), 0) AS sold_qty
                FROM shop_invoices AS SI
                LEFT JOIN shop_sales AS SS ON SI.id = SS.invoice_id
                WHERE SI.shop_id = $shop_id AND SS.product_id = $product_id
            ";

            $res2 = $conn->query($sql2);
            $sold_qty = 0;

            if ($res2 && $res2->num_rows > 0) {
                $sold_row = $res2->fetch_assoc();
                $sold_qty = $sold_row['sold_qty'];
            }

            // ✅ Step 3: Calculate remaining quantity safely (prevent negative)
            $real_qty = max(0, $total_quantity - $sold_qty);

            // ✅ Step 4: Push final combined data
            $final_data[] = [
                "product_name"   => $row['product_name'],
                "product_id"     => $row['product_id'],
                "measurement"    => $row['measurement'],
                "shop_name"      => $row['shop_name'],
                "shop_id"        => $row['shop_id'],
                "design_number"  => $row['design_number'],
                "nag"            => $row['nag'],
                "total_quantity" => $real_qty
            ];
        }
    } else {
        // ✅ Fallback row
        $final_data = [[
            "product_name"   => "000",
            "product_id"     => 0,
            "measurement"    => "000",
            "shop_name"      => "000",
            "shop_id"        => 0,
            "design_number"  => "000",
            "nag"            => "000",
            "total_quantity" => 0
        ]];
    }

    echo json_encode(["success" => true, "data" => $final_data]);
    exit;
}


if ($action === 'update_nag_temp') {
    $data = json_decode(file_get_contents("php://input"), true);
    $product_id = $data['product_id'] ?? 0;
    $shop_id = $data['shop_id'] ?? 0;
    $design_number = $data['design_number'] ?? '';
    $old_nag = $data['old_nag'] ?? '';
    $new_nag = $data['new_nag'] ?? '';
    $wts_id = $data['wts_id'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO temp_nag_updates (product_id, shop_id, design_number, old_nag, new_nag, wts_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisssi", $product_id, $shop_id, $design_number, $old_nag, $new_nag, $wts_id);
    $ok = $stmt->execute();

    echo json_encode(["success" => $ok]);
    exit;
}

if ($action === 'adjust_quantity') {
    $data = json_decode(file_get_contents("php://input"), true);
    $wts_id = $data['wts_id'] ?? 0;
    $product_id = $data['product_id'] ?? 0;
    $shop_id = $data['shop_id'] ?? 0;
    $design_number = $data['design_number'] ?? '';
    $adjusted_qty = $data['adjusted_qty'] ?? 0;
    $note = $data['note'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO temp_quantity_adjustments (wts_id, product_id, shop_id, design_number, adjusted_qty, note)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisds", $wts_id, $product_id, $shop_id, $design_number, $adjusted_qty, $note);
    $ok = $stmt->execute();

    echo json_encode(["success" => $ok]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
exit;

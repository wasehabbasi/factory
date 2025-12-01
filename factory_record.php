<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "./db/db.php";
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';

// ---- SEND INVENTORY ----
if ($action == "list_send") {
    $sql = "SELECT s.*, f.name as factory_name, v.name as vendor_name
            FROM send_inventories s
            LEFT JOIN factories f ON s.factory_id = f.id
            LEFT JOIN vendors v ON s.vendor_id = v.id
            ORDER BY s.id DESC";
    $res = $conn->query($sql);
    echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action == "save_send") {
    $id = $_POST['id'] ?? null;
    $date = $_POST['date'];
    $factory_id = $_POST['factory_id'];
    $vendor_id = $_POST['vendor_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $lot = $_POST['lot_number'];
    $qty = $_POST['quantity'];

    if ($id) {
        $sql = "SELECT unit FROM `products` where lot_number = $lot";
        $res = $conn->query($sql);

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            echo $row['unit']; // ✅ yahan actual unit print hogi
        } else {
            echo "No record found";
        }

        // print_r($res);
        exit;

        $stmt = $conn->prepare("UPDATE send_inventories SET date=?, factory_id=?, vendor_id=?, lot_number=?, quantity=? WHERE id=?");
        $stmt->bind_param("siiidi", $date, $factory_id, $vendor_id, $lot, $qty, $id);
    } else {

        $sql = "SELECT unit FROM `products` where lot_number = $lot";
        $res = $conn->query($sql);

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $purchase_unit =  $row['unit'];
            if ($purchase_unit >= $qty) {
                $sql = "SELECT SUM(quantity) AS total_quantity FROM `send_inventories` where lot_number = $lot";
                $res = $conn->query($sql);
                $row = $res->fetch_assoc();
                $total_quantity =  $row['total_quantity'];

                $QT = $purchase_unit - $total_quantity;

                if ($QT > 0 && $qty <= $QT) {

                    $issue_meter = 0;
                    $query = "SELECT SUM(issue_meter) AS total_issue FROM `purchase`";
                    $result = $conn->query($query);
                    if ($row = $result->fetch_assoc()) {
                        $issue_meter = floatval($row['total_issue']);
                    }

                    if ($issue_meter >= $qty) {
                        $stmt = $conn->prepare("INSERT INTO send_inventories 
            (date, factory_id, vendor_id, warehouse_id, lot_number, quantity) VALUES (?,?,?,?,?,?)");
                        $stmt->bind_param("siiiid", $date, $factory_id, $vendor_id, $warehouse_id, $lot, $qty);
                    } else {
                        echo json_encode(["success" => false, "message" => "Your quantity is mismatched"]);
                        exit;
                    }

                    $stmt = $conn->prepare("INSERT INTO send_inventories (date,factory_id,vendor_id,lot_number,quantity) VALUES (?,?,?,?,?)");
                    $stmt->bind_param("siiid", $date, $factory_id, $vendor_id, $lot, $qty);
                } else {
                    echo json_encode(["success" => false, "message" => "You need more quantity of this product."]);
                    exit;
                }
                // }
            } else {
                echo json_encode(["success" => false, "message" => "You need more quantity of this product."]);
                exit;
            }
        } else {
            echo json_encode(["success" => false, "message" => "You cannot send this inventory"]);
            exit;
        }
    }
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}

// ---- RECEIVE INVENTORY ----
if ($action == "list_receive") {
    $sql = "SELECT 
                r.*, 
                f.name AS factory_name, 
                v.name AS vendor_name,
                w.name AS warehouse_name
            FROM receive_inventories r
            LEFT JOIN factories f ON r.factory_id = f.id
            LEFT JOIN vendors v ON r.vendor_id = v.id
            LEFT JOIN warehouses w ON r.warehouse_id = w.id
            ORDER BY r.id DESC";

    $res = $conn->query($sql);
    echo json_encode([
        "success" => true,
        "data" => $res->fetch_all(MYSQLI_ASSOC)
    ]);
    exit;
}

if ($action == "get_lot_numbers") {
    $vendor_id = intval($_GET['vendor_id'] ?? 0);
    if (!$vendor_id) {
        echo json_encode(["success" => false, "data" => [], "message" => "Invalid vendor"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT DISTINCT lot_number FROM product_lots WHERE vendor_id = ?");
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $lots = [];
    while ($row = $res->fetch_assoc()) {
        $lots[] = $row['lot_number'];
    }
    $stmt->close();

    echo json_encode(["success" => true, "data" => $lots]);
    exit;
}



if ($action == "save_receive") {
    $id = $_POST['id'] ?? null;
    $date = $_POST['date'];
    $factory_id = $_POST['factory_id'];
    $vendor_id = $_POST['vendor_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $lot = $_POST['lot_number'];
    $send_qty = $_POST['send_quantity'];
    $receive_qty = $_POST['receive_quantity'];
    $design_number = $_POST['design_number'] ?? 0;
    $nag = $_POST['nag'] ?? 0;
    $shortage = $_POST['shortage'] ?? 0;
    $rejection = $_POST['rejection'] ?? 0;
    $l_kmi = $_POST['l_kmi'] ?? null;

    // ✅ Check if lot_number exists in product_lots
    $check = $conn->prepare("SELECT COUNT(*) FROM product_lots WHERE lot_number = ?");
    $check->bind_param("i", $lot);
    $check->execute();
    $check->bind_result($exists);
    $check->fetch();
    $check->close();

    if (!$exists) {
        echo json_encode(["success" => false, "message" => "Invalid Lot Number!"]);
        exit;
    }

    if ($id) {
        // ✅ UPDATE existing record
        $stmt_update = $conn->prepare("
        UPDATE receive_inventories 
        SET date=?, factory_id=?, vendor_id=?, warehouse_id=?, lot_number=?, 
            send_quantity=?, receive_quantity=?, design_number=?, nag=?, 
            shortage=?, rejection=?, l_kmi=? 
        WHERE id=?
    ");
        $stmt_update->bind_param(
            "siiiiddddddsi",
            $date,
            $factory_id,
            $vendor_id,
            $warehouse_id,
            $lot,
            $send_qty,
            $receive_qty,
            $design_number,
            $nag,
            $shortage,
            $rejection,
            $l_kmi,
            $id
        );

        $query_to_run = $stmt_update;
    } else {
        // ✅ Get total sent quantity
        $stmt_send = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total_sent 
        FROM send_inventories 
        WHERE lot_number = ?
    ");
        $stmt_send->bind_param("s", $lot);
        $stmt_send->execute();
        $result = $stmt_send->get_result();
        $sent_row = $result->fetch_assoc();
        $total_sent = (float) $sent_row['total_sent'];
        $stmt_send->close();

        // ✅ Get total received quantity so far
        $stmt_recv = $conn->prepare("
        SELECT COALESCE(SUM(receive_quantity), 0) AS total_received 
        FROM receive_inventories 
        WHERE lot_number = ?
    ");
        $stmt_recv->bind_param("s", $lot);
        $stmt_recv->execute();
        $result = $stmt_recv->get_result();
        $recv_row = $result->fetch_assoc();
        $total_received = (float) $recv_row['total_received'];
        $stmt_recv->close();

        // ✅ Validation
        $new_total_received = $total_received + $receive_qty;
        if ($new_total_received > $total_sent) {
            echo json_encode([
                "success" => false,
                "message" => "Quantity mismatch: received quantity exceeds sent quantity."
            ]);
            exit;
        }

        // ✅ Prepare insert
        $stmt_insert = $conn->prepare("
        INSERT INTO receive_inventories 
            (date, factory_id, vendor_id, warehouse_id, lot_number, 
             send_quantity, receive_quantity, design_number, nag, 
             shortage, rejection, l_kmi)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");
        $stmt_insert->bind_param(
            "siiiidddddds",
            $date,
            $factory_id,
            $vendor_id,
            $warehouse_id,
            $lot,
            $send_qty,
            $receive_qty,
            $design_number,
            $nag,
            $shortage,
            $rejection,
            $l_kmi
        );

        $query_to_run = $stmt_insert;
    }

    // ✅ Execute whichever query is prepared
    if ($query_to_run->execute()) {
        // echo json_encode([
        //     "success" => true,
        //     "message" => $id ? "Record updated successfully" : "Record added successfully"
        // ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $query_to_run->error
        ]);
    }

    $query_to_run->close();


    // $stmt->execute();

    $upd = $conn->prepare("UPDATE product_lots SET qty = qty + ? WHERE lot_number = ?");
    $upd->bind_param("di", $receive_qty, $lot);
    $upd->execute();

    echo json_encode(["success" => true]);
    exit;
}

if ($action == "save_send") {
    $id = $_POST['id'] ?? null;
    $date = $_POST['date'];
    $factory_id = $_POST['factory_id'];
    $vendor_id = $_POST['vendor_id'];
    $warehouse_id = $_POST['warehouse_id']; // ✅ new field
    $lot = $_POST['lot_number'];
    $qty = $_POST['quantity'];

    // echo $qty;
    // exit;

    if ($id) {
        $stmt = $conn->prepare("UPDATE send_inventories 
            SET date=?, factory_id=?, vendor_id=?, warehouse_id=?, lot_number=?, quantity=? WHERE id=?");
        $stmt->bind_param("siiiidi", $date, $factory_id, $vendor_id, $warehouse_id, $lot, $qty, $id);
    } else {
        $issue_meter = 0;
        $query = "SELECT SUM(issue_meter) AS total_issue FROM `purchase`";
        $result = $conn->query($query);
        if ($row = $result->fetch_assoc()) {
            $issue_meter = floatval($row['total_issue']);
        }

        if ($issue_meter >= $qty) {
            echo json_encode(["success" => false, "message" => "Your quantity is mismatched"]);
            exit;
        } else {
            $stmt = $conn->prepare("INSERT INTO send_inventories 
            (date, factory_id, vendor_id, warehouse_id, lot_number, quantity) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("siiiid", $date, $factory_id, $vendor_id, $warehouse_id, $lot, $qty);
        }
    }
    $stmt->execute();

    // ✅ Deduct quantity from product_lots / warehouse stock
    $update = $conn->prepare("UPDATE product_lots SET qty = qty - ? WHERE lot_number = ? AND warehouse_id = ?");
    $update->bind_param("dii", $qty, $lot, $warehouse_id);
    $update->execute();

    echo json_encode(["success" => true]);
    exit;
}

// ---- WAREHOUSE STOCK VIEW ----
if ($action === 'list_unique_warehouse_design') {
    $sql = "
        SELECT 
            MAX(r.id) AS id,
            r.design_number,
            r.nag,
            w.name AS warehouse_name,
            f.name AS factory_name,
            v.name AS vendor_name,
            p.name AS product_name,
            w.id AS warehouse_id,
            p.id AS product_id,
            pl.lot_number AS lot_number,
            p.measurement AS product_measurement,
            SUM(r.receive_quantity) AS total_quantity
        FROM receive_inventories r
        LEFT JOIN factories f ON r.factory_id = f.id
        LEFT JOIN vendors v ON r.vendor_id = v.id
        LEFT JOIN warehouses w ON r.warehouse_id = w.id
        LEFT JOIN product_lots pl 
            ON pl.lot_number = r.lot_number
        LEFT JOIN products p 
            ON pl.product_id = p.id
        GROUP BY r.warehouse_id, r.design_number, p.id
        ORDER BY MAX(r.id) DESC
    ";
    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    $i = 0;
    foreach ($data as &$row) {
        // echo $i . '=>' . $row['total_quantity'] . ', ';
        // print_r($row);exit;
        $wh_id = $row['warehouse_id'];

        $sql2 = "SELECT SUM(quantity) AS total_stock FROM warehouse_stock WHERE warehouse_id=? and product_id=?";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("ii", $wh_id, $row['product_id']);
        $stmt->execute();
        $res2 = $stmt->get_result();
        $stock = $res2->fetch_assoc();

        $stockQty = ($stock['total_stock'] ?? 0);

        // echo $i . '=>' . $stockQty  . ', ';

        $sql3 = "SELECT SUM(qty) AS total_sent FROM warehouse_to_shop WHERE warehouse_id=? and product_id=?";
        $stmt = $conn->prepare($sql3);
        $stmt->bind_param("ii", $wh_id, $row['product_id']);
        $stmt->execute();
        $res3 = $stmt->get_result();
        $transfer = $res3->fetch_assoc();

        $transferQty = ($transfer['total_sent'] ?? 0);

        $row['total_quantity'] = $row['total_quantity'] - $transferQty - $stockQty;

        $sql4 = "
    SELECT SUM(BI.nag) AS nag
    FROM buyer_invoices AS BI
    LEFT JOIN products AS P ON BI.product_id = P.id
    WHERE BI.product_id = ? AND BI.warehouse_id = ? AND P.measurement = ?
";
        $stmt = $conn->prepare($sql4);
        $stmt->bind_param("iis", $row['product_id'], $wh_id, $row['product_measurement']);
        $stmt->execute();
        $res4 = $stmt->get_result();
        $nag = $res4->fetch_assoc();

        // echo $nag['nag'] ?? 0; // ✅ safe access (0 if null)
        // exit;


        // $nagQty = ($nag['nag'] ?? 0);
        $row['nag'] = $row['nag'] - $nag['nag'];
        // echo $i . '=>' . $transferQty . ', ';


        $i++;
    }


    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
    exit;
}

// ---- WAREHOUSE SUMMARY (UNIQUE BY WAREHOUSE) ----
if ($action == "list_warehouse_summary") {
    $sql = "SELECT 
                w.id AS warehouse_id,
                w.name AS warehouse_name,
                SUM(pl.qty) AS total_qty
            FROM product_lots pl
            LEFT JOIN warehouses w ON pl.warehouse_id = w.id
            GROUP BY w.id
            ORDER BY w.name";

    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'list_unique_warehouse_summary') {
    $sql = "
        SELECT 
            MAX(r.id) AS id,
            w.id AS warehouse_id,
            w.name AS warehouse_name,
            SUM(r.receive_quantity) AS total_quantity
        FROM receive_inventories r
        LEFT JOIN warehouses w ON r.warehouse_id = w.id
        GROUP BY r.warehouse_id
        ORDER BY MAX(r.id) DESC;
    ";

    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($data as &$row) {
        $wh_id = $row['warehouse_id'];

        // 1️⃣ warehouse_stock total
        $sql2 = "SELECT SUM(quantity) AS total_stock FROM warehouse_stock WHERE warehouse_id=?";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("i", $wh_id);
        $stmt->execute();
        $res2 = $stmt->get_result();
        $stock = $res2->fetch_assoc();
        $stmt->close();

        $total_stock = $stock['total_stock'] ?? 0;

        // 2️⃣ warehouse_to_shop total
        $sql3 = "SELECT SUM(qty) AS total_sent FROM warehouse_to_shop WHERE warehouse_id=?";
        $stmt = $conn->prepare($sql3);
        $stmt->bind_param("i", $wh_id);
        $stmt->execute();
        $res3 = $stmt->get_result();
        $transfer = $res3->fetch_assoc();
        $stmt->close();

        $total_transfer = $transfer['total_sent'] ?? 0;

        // ✅ Final available quantity
        $row['total_quantity'] = $row['total_quantity'] - $total_stock - $total_transfer; // 
    }

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
    exit;
}

if ($_GET['action'] == 'get_lot_number') {

    $vendor_id = intval($_GET['vendor_id'] ?? 0); // vendor_id ko read karo
    if (!$vendor_id) {
        echo json_encode(["success" => false, "data" => [], "message" => "Invalid vendor"]);
        exit;
    }

    $sql = "SELECT id, lot_number FROM product_lots WHERE vendor_id = $vendor_id ORDER BY id DESC";
    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
    exit;
}

if ($action == "get_design_number") {
    $lot_number = $_GET['lot_number'] ?? null;

    if (!$lot_number) {
        echo json_encode(["success" => false, "message" => "Lot number missing!"]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT design_number 
        FROM receive_inventories 
        WHERE lot_number = ? AND design_number IS NOT NULL AND design_number <> 0 
        ORDER BY id ASC LIMIT 1
    ");
    $stmt->bind_param("s", $lot_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "design_number" => $row['design_number']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No record found for this lot number!"
        ]);
    }

    $stmt->close();
    exit;
}


if ($action == "get_send_qty") {
    $factory_id = $_GET['factory_id'];
    $vendor_id = $_GET['vendor_id'];
    $lot_number = $_GET['lot_number'];

    // SELECT quantity FROM `send_inventories` where factory_id = 19 AND vendor_id = 17 AND lot_number = 100;

    $sql = "SELECT quantity FROM `send_inventories` where factory_id = ? AND vendor_id = ? AND lot_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $factory_id, $vendor_id, $lot_number);
    $stmt->execute();
    $res = $stmt->get_result();
    $result = $res->fetch_assoc();

    echo json_encode(["success" => true, "quantity" => $result['quantity']]);
    exit;
}

if ($_GET['action'] == 'get_design_numbers') {
    $res = $conn->query("SELECT DISTINCT design_number FROM receive_inventories ORDER BY design_number ASC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

echo json_encode(["error" => "Invalid action"]);

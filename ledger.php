<?php
include "./db/db.php";
header("Content-Type: application/json; charset=utf-8");

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $sql = "SELECT vi.*, v.name AS vendor_name
          FROM vendor_ledger vi
          LEFT JOIN vendors v ON vi.vendor_id = v.id
          ORDER BY vi.id DESC";
    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

if ($action === 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM vendor_ledger WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if ($row) {
        echo json_encode(["success" => true, "data" => $row]);
    } else {
        echo json_encode(["success" => false, "message" => "Not found"]);
    }
    exit;
}

if ($action === 'save') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
    $vendor_id = intval($_POST['vendor_id'] ?? 0);
    $lot_number = trim($_POST['lot_number'] ?? '');
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $remarks = trim($_POST['remarks'] ?? '');

    // 🔹 Check if vendor + lot_number already exists
    $check = $conn->prepare("SELECT id, total_amount, amount_paid FROM vendor_ledger WHERE vendor_id = ? AND lot_number = ?");
    $check->bind_param("is", $vendor_id, $lot_number);
    $check->execute();
    $result = $check->get_result();
    $existing = $result->fetch_assoc();
    $check->close();

    if ($existing) {
        // ✅ Add new payment to existing amount_paid
        $newPaid = floatval($existing['amount_paid']) + $amount_paid;
        $total = floatval($existing['total_amount'] ?: $total_amount);

        // Prevent overpayment
        if ($newPaid > $total) {
            echo json_encode([
                "success" => false,
                "message" => "Amount paid cannot exceed total amount ($total)"
            ]);
            exit;
        }

        $balance = $total - $newPaid;

        // ✅ Update existing record
        $stmt = $conn->prepare("UPDATE vendor_ledger 
          SET total_amount=?, amount_paid=?, balance=?, payment_date=?, remarks=? 
          WHERE id=?");
        $stmt->bind_param("dddssi", $total, $newPaid, $balance, $payment_date, $remarks, $existing['id']);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            "success" => $ok,
            "message" => $ok ? "Ledger updated successfully" : $conn->error
        ]);
        exit;
    }

    // 🔹 If no existing vendor+lot, insert new record
    $balance = $total_amount - $amount_paid;

    $stmt = $conn->prepare("INSERT INTO vendor_ledger 
      (vendor_id, lot_number, total_amount, amount_paid, balance, payment_date, remarks) 
      VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdddss", $vendor_id, $lot_number, $total_amount, $amount_paid, $balance, $payment_date, $remarks);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode([
        "success" => $ok,
        "message" => $ok ? "Ledger entry added successfully" : $conn->error
    ]);
    exit;
}

if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vendor_invoices.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Vendor', 'Lot No', 'Total Amount', 'Paid', 'Balance', 'Payment Date', 'Remarks']);

    $sql = "SELECT v.name AS vendor_name, vi.lot_number, vi.total_amount, vi.amount_paid, vi.balance, vi.payment_date, vi.remarks 
          FROM vendor_ledger vi 
          LEFT JOIN vendors v ON vi.vendor_id = v.id 
          ORDER BY vi.id DESC";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['vendor_name'],
            $row['lot_number'],
            $row['total_amount'],
            $row['amount_paid'],
            $row['balance'],
            $row['payment_date'],
            $row['remarks']
        ]);
    }
    fclose($output);
    exit;
}

if ($action === 'buyer_list') {
  $sql = "SELECT bi.*, b.name AS buyer_name, p.name AS product_name 
          FROM buyer_ledger bi
          LEFT JOIN buyers b ON bi.buyer_id = b.id
          LEFT JOIN products p ON bi.product_id = p.id
          ORDER BY bi.id DESC";
  $res = $conn->query($sql);
  $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  echo json_encode(["success" => true, "data" => $data]);
  exit;
}

if ($action === 'get_shop_ledger') {
    $result = $conn->query("
        SELECT id, date, customer_name, paandi_name, grand_total
        FROM shop_invoices
        ORDER BY id DESC
    ");

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

if ($action === 'get_factory_ledger') {
    $sql = "
        SELECT p.name AS product_name, f.name AS factory_name, fl.lot_number, fl.total_meter, fl.per_meter_rate, fl.total_amount, fl.rejection, fl.advance_adjusted, fl.net_amount, fl.created_at FROM `factory_ledger` AS fl LEFT JOIN factories f ON fl.factory_id = f.id
LEFT JOIN products p ON fl.product_id = p.id
ORDER BY fl.id DESC
    ";

    $res = $conn->query($sql);
    $invoices = [];

    while ($row = $res->fetch_assoc()) {
        $invoices[] = $row;
    }

    echo json_encode($invoices);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "./db/db.php";
header("Content-Type: application/json; charset=utf-8");

$action = $_GET['action'] ?? '';

// --- List Invoices ---
if ($action === 'list') {
    $sql = "SELECT vi.*, v.name AS vendor_name
          FROM vendor_invoices vi
          LEFT JOIN vendors v ON vi.vendor_id = v.id
          ORDER BY vi.id DESC";
    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

// --- Get Single Invoice (for edit) ---
if ($action === 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM vendor_invoices WHERE id = ?");
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

// --- Save Invoice (Add/Edit) ---
if ($action === 'save') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
    $vendor_id = intval($_POST['vendor_id'] ?? 0);
    $lot_number = trim($_POST['lot_number'] ?? '');
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $remarks = trim($_POST['remarks'] ?? '');

    // 🔹 Check if vendor + lot_number combination already exists (excluding same ID if editing)
    $checkSql = "SELECT id, total_amount, amount_paid, balance FROM vendor_invoices WHERE vendor_id = ? AND lot_number = ?";
    $params = [$vendor_id, $lot_number];
    $types = "is";

    if ($id) {
        $checkSql .= " AND id != ?";
        $params[] = $id;
        $types .= "i";
    }

    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    // 🔹 If record exists with same vendor + lot_number
    if ($existing) {
        $total_amount = floatval($existing['total_amount']); // total amount locked
        $newPaid = floatval($existing['amount_paid']) + $amount_paid;

        // Prevent overpayment
        if ($newPaid > $total_amount) {
            echo json_encode([
                "success" => false,
                "message" => "Amount paid cannot exceed total amount (" . $total_amount . ")"
            ]);
            exit;
        }

        $balance = $total_amount - $newPaid;

        // Update existing invoice record with new paid and balance values
        $stmt = $conn->prepare("UPDATE vendor_invoices SET amount_paid = ?, balance = ?, payment_date = ?, remarks = ? WHERE id = ?");
        $stmt->bind_param("ddssi", $newPaid, $balance, $payment_date, $remarks, $existing['id']);
        $ok = $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO vendor_ledger 
            (vendor_id, lot_number, total_amount, amount_paid, balance, payment_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdddss", $vendor_id, $lot_number, $total_amount, $amount_paid, $balance, $payment_date, $remarks);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            echo json_encode([
                "success" => true,
                "message" => "Existing invoice updated (Vendor + Lot already existed)"
            ]);
        } else {
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        exit;
    }

    // 🔹 If no existing record, proceed with insert/update normally
    $balance = $total_amount - $amount_paid;

    if ($id) {
        if ($amount_paid <= $total_amount) {

            $stmt = $conn->prepare('SELECT SUM(amount_paid) AS total_amount_paid FROM vendor_ledger WHERE vendor_id = ? AND lot_number = ?');
            $stmt->bind_param("ii", $vendor_id, $lot_number);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total_amount_paid = $row['total_amount_paid'] ?? 0;
            $stmt->close();

            $remaining_amount = $total_amount - ($total_amount_paid + $amount_paid);

            $TPA = $total_amount_paid + $amount_paid;

            if ($total_amount_paid <= $total_amount) {
                if ($remaining_amount >= 0) {
                    // Update existing invoice
                    $stmt = $conn->prepare("INSERT INTO vendor_invoices 
            (vendor_id, lot_number, total_amount, amount_paid, balance, issue_meter, rejection, rate, payment_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isddddddss", $vendor_id, $lot_number, $total_amount, $amount_paid, $balance, $issue_meter, $rejection, $rate, $payment_date, $remarks);
                    $ok = $stmt->execute(); // ✅ Execute missing thi
                    $stmt->close();

                    echo $ok;exit;

                    // Insert into ledger
                    $stmt = $conn->prepare("INSERT INTO vendor_ledger 
        (vendor_id, lot_number, total_amount, amount_paid, balance, payment_date, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isdddss", $vendor_id, $lot_number, $total_amount, $amount_paid, $balance, $payment_date, $remarks);
                    $ok2 = $stmt->execute();
                    $stmt->close();

                    echo json_encode([
                        "success" => ($ok && $ok2),
                        "message" => ($ok && $ok2) ? "Invoice updated successfully" : $conn->error
                    ]);
                    exit;
                }

                echo json_encode(["error" => "you cannot pay this amount"]);
                exit;
            }
        }

        echo json_encode(["error" => "Paid amount cannot be greater than total amount"]);
        exit;
    } else {
        if ($amount_paid <= $total_amount) {

            // Fetch issue_meter and rejection
            $stmt = $conn->prepare("SELECT p.issue_meter, r.rejection, p.rate 
                        FROM purchase p 
                        LEFT JOIN receive_inventories r 
                        ON r.vendor_id = p.vendor_id AND r.lot_number = p.lot_number
                        WHERE p.vendor_id = ? AND p.lot_number = ?");
            $stmt->bind_param("ii", $vendor_id, $lot_number);
            $stmt->execute();
            $result = $stmt->get_result();
            $purchaseData = $result->fetch_assoc();
            $stmt->close();

            $issue_meter = floatval($purchaseData['issue_meter'] ?? 0);
            $rejection = floatval($purchaseData['rejection'] ?? 0);
            $safi_meter = $issue_meter - $rejection;
            $rate = floatval($purchaseData['rate'] ?? 0);

            $stmt = $conn->prepare("INSERT INTO vendor_invoices 
            (vendor_id, lot_number, total_amount, amount_paid, balance, issue_meter, rejection, rate, payment_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isddddddss", $vendor_id, $lot_number, $total_amount, $amount_paid, $balance, $issue_meter, $rejection, $rate, $payment_date, $remarks);
            $ok = $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO vendor_ledger 
            (vendor_id, lot_number, total_amount, amount_paid, balance, payment_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdddss", $vendor_id, $lot_number, $total_amount, $amount_paid, $balance, $payment_date, $remarks);
            $ok = $stmt->execute();
            $stmt->close();

            echo json_encode([
                "success" => $ok,
                "id" => $ok ? $conn->insert_id : null,
                "message" => $ok ? "Invoice added" : $conn->error
            ]);
            exit;
        }

        echo json_encode(["error" => "Paid amount cannot be greater than total amount"]);
        exit;
    }
}


// --- Export to CSV (same as before, secure) ---
if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vendor_invoices.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Vendor', 'Lot No', 'Total Amount', 'Paid', 'Balance', 'Payment Date', 'Remarks']);

    $sql = "SELECT v.name AS vendor_name, vi.lot_number, vi.total_amount, vi.amount_paid, vi.balance, vi.payment_date, vi.remarks 
          FROM vendor_invoices vi 
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

echo json_encode(["success" => false, "message" => "Invalid action"]);

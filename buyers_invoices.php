<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "./db/db.php";
header("Content-Type: application/json; charset=utf-8");

$action = $_GET['action'] ?? '';

if ($action === 'list') {
  $sql = "SELECT bi.*, b.name AS buyer_name, p.name AS product_name 
          FROM buyer_invoices bi
          LEFT JOIN buyers b ON bi.buyer_id = b.id
          LEFT JOIN products p ON bi.product_id = p.id
          ORDER BY bi.id DESC";
  $res = $conn->query($sql);
  $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  echo json_encode(["success" => true, "data" => $data]);
  exit;
}

if ($action === 'get' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $stmt = $conn->prepare("SELECT * FROM buyer_invoices WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  echo json_encode($row ? ["success" => true, "data" => $row] : ["success" => false]);
  exit;
}

if ($action === 'save') {

  $id = $_POST['id'] ?? null;
  $buyer_id = intval($_POST['buyer_id']);
  $product_id = intval($_POST['product_id']);
  $warehouse_id = intval($_POST['warehouse_id']);
  $lot_number = trim($_POST['lot_number']);
  $design_number = trim($_POST['design_number']);
  $qty = intval($_POST['qty']);
  $rate = floatval($_POST['rate']);
  $total_amount = floatval($_POST['total_amount']);
  $nag = $_POST['nag'] ?? 0;
  $amount_paid = floatval($_POST['amount_paid']);
  $payment_date = $_POST['payment_date'] ?? null;
  $remarks = trim($_POST['remarks']);
  $balance = $total_amount - $amount_paid;

  // Start transaction for safety
  $conn->begin_transaction();

  try {

    if ($id) {

      $stmt = $conn->prepare("
    SELECT invoice_no FROM `buyer_invoices`where id = ?
");
      $stmt->bind_param("i", $id); // assuming product_id is integer
      $stmt->execute();

      $result = $stmt->get_result();
      $row = $result->fetch_assoc();

      $invoice_no = $row['invoice_no'];

      $stmt = $conn->prepare('SELECT SUM(amount_paid) AS total_amount_paid FROM buyer_ledger WHERE invoice_no = ?');
      $stmt->bind_param("s", $invoice_no);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $total_amount_paid = $row['total_amount_paid'] ?? 0;
      $stmt->close();

      $remaining_amount = $total_amount - ($total_amount_paid + $amount_paid);

      // echo $remaining_amount;exit;

      $TPA = $total_amount_paid + $amount_paid;

      // echo $TPA;exit;

      if ($remaining_amount >= 0) {

        // Update existing invoice
        $stmt = $conn->prepare("UPDATE buyer_invoices SET buyer_id=?, product_id=?, lot_number=?, design_number=?, warehouse_id=?, qty=?, rate=?, total_amount=?, amount_paid=?, balance=?, payment_date=?, remarks=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("iisiidddddssi", $buyer_id, $product_id, $lot_number, $design_number, $warehouse_id, $qty, $rate, $total_amount, $TPA, $balance, $payment_date, $remarks, $id);
        $ok = $stmt->execute();

        $date = date('Y-m-d');


        $stmt = $conn->prepare("INSERT INTO buyer_ledger (date, invoice_no, buyer_id, product_id, design_number, lot_number, warehouse_id, qty, rate, total_amount, nag, amount_paid, balance, payment_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiiisiddddiss", $date, $invoice_no, $buyer_id, $product_id, $design_number, $lot_number, $warehouse_id, $qty, $rate, $total_amount, $nag, $amount_paid, $balance, $payment_date, $remarks);
        $ok = $stmt->execute();

        // ✅ Update warehouse_stock table
        $checkStock = $conn->prepare("SELECT id, quantity FROM warehouse_stock WHERE product_id=? AND lot_number=?");
        $checkStock->bind_param("is", $product_id, $lot_number);
        $checkStock->execute();
        $res = $checkStock->get_result();
        $stock = $res->fetch_assoc();

        if ($stock) {
          $newQty = max(0, $stock['quantity'] - $qty); // prevent negative
          $upd = $conn->prepare("UPDATE warehouse_stock SET quantity=?, updated_at=NOW() WHERE id=?");
          $upd->bind_param("di", $newQty, $stock['id']);
          $upd->execute();
          $upd->close();
        } else {
          // If no stock exists, initialize as zero
          $ins = $conn->prepare("INSERT INTO warehouse_stock (product_id, lot_number, quantity, warehouse_id) VALUES (?, ?, ?, ?)");
          $ins->bind_param("isii", $product_id, $lot_number, $qty, $warehouse_id); // qty insert karega
          $ins->execute();
          $ins->close();
        }

        $conn->commit(); // commit transaction

      } else {
        echo json_encode(["error" => "you cannot pay this amount"]);
        exit;
      }
    } else {

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
                  FROM buyer_invoices 
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
              // Insert new invoice
              $invoice_no = 'INV-' . date('YmdHis');
              $date = date('Y-m-d');
              $stmt = $conn->prepare("INSERT INTO buyer_invoices (date, invoice_no, buyer_id, product_id, design_number, lot_number, warehouse_id, qty, rate, total_amount, nag, amount_paid, balance, payment_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
              $stmt->bind_param("ssiiiisiddddiss", $date, $invoice_no, $buyer_id, $product_id, $design_number, $lot_number, $warehouse_id, $qty, $rate, $total_amount, $nag, $amount_paid, $balance, $payment_date, $remarks);
              $ok = $stmt->execute();

              $stmt = $conn->prepare("INSERT INTO buyer_ledger (date, invoice_no, buyer_id, product_id, design_number, lot_number, warehouse_id, qty, rate, total_amount, nag, amount_paid, balance, payment_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
              $stmt->bind_param("ssiiiisiddddiss", $date, $invoice_no, $buyer_id, $product_id, $design_number, $lot_number, $warehouse_id, $qty, $rate, $total_amount, $nag, $amount_paid, $balance, $payment_date, $remarks);
              $ok = $stmt->execute();

              // ✅ Update warehouse_stock table
              $checkStock = $conn->prepare("SELECT id, quantity FROM warehouse_stock WHERE product_id=? AND lot_number=?");
              $checkStock->bind_param("is", $product_id, $lot_number);
              $checkStock->execute();
              $res = $checkStock->get_result();
              $stock = $res->fetch_assoc();

              if ($stock) {
                // print_r($stock);exit;
                $newQty = max(0, $stock['quantity'] + $qty); // prevent negative
                $upd = $conn->prepare("UPDATE warehouse_stock SET quantity=?, updated_at=NOW() WHERE id=?");
                $upd->bind_param("di", $newQty, $stock['id']);
                $upd->execute();
                $upd->close();
              } else {
                $ins = $conn->prepare("INSERT INTO warehouse_stock (product_id, lot_number, quantity, warehouse_id) VALUES (?, ?, ?, ?)");
                $ins->bind_param("isii", $product_id, $lot_number, $qty, $warehouse_id); // qty insert karega
                $ins->execute();
                $ins->close();
              }

              $conn->commit(); // commit transaction

              $id = $stmt->insert_id; // new invoice id
            }
          }
        } else {
          echo json_encode(["message" => "Total quantity or total nag is less then you have provided"]);
          exit;
        }
      } else {
        echo json_encode(["message" => "It doesnot have any quantity"]);
        exit;
      }
    }

    echo json_encode(["success" => true, "message" => "Saved successfully!"]);
  } catch (Exception $e) {
    $conn->rollback(); // rollback if error
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
  }

  exit;
}

if ($action === 'export') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="buyer_invoices.csv"');
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Invoice No', 'Buyer', 'Product', 'Lot No', 'Qty', 'Rate', 'Total', 'Paid', 'Balance', 'Payment Date', 'Remarks']);

  $sql = "SELECT bi.invoice_no, b.name AS buyer_name, p.name AS product_name, bi.lot_number, bi.qty, bi.rate, bi.total_amount, bi.amount_paid, bi.balance, bi.payment_date, bi.remarks 
          FROM buyer_invoices bi
          LEFT JOIN buyers b ON bi.buyer_id = b.id
          LEFT JOIN products p ON bi.product_id = p.id
          ORDER BY bi.id DESC";
  $res = $conn->query($sql);
  while ($row = $res->fetch_assoc()) {
    fputcsv($output, $row);
  }
  fclose($output);
  exit;
}

if ($action === 'get_warehouses') {
  $sql = "SELECT id, name FROM `warehouses`";
  $res = $conn->query($sql);
  $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  echo json_encode(["success" => true, "data" => $data]);
  exit;
}

if ($action === 'get_design_numbers') {
  $sql = "SELECT DISTINCT design_number 
            FROM receive_inventories 
            WHERE design_number IS NOT NULL AND design_number <> 0
            ORDER BY design_number ASC";
  $res = $conn->query($sql);
  $data = [];
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $data[] = $row['design_number'];
    }
  }
  echo json_encode(["success" => true, "data" => $data]);
  exit;
}

if ($action === 'get_product_by_design' && isset($_GET['design_number'])) {
  $design_number = intval($_GET['design_number']);

  // 1️⃣ Get the lot_number from receive_inventories
  $stmt = $conn->prepare("SELECT lot_number FROM receive_inventories WHERE design_number=? LIMIT 1");
  $stmt->bind_param("i", $design_number);
  $stmt->execute();
  $res = $stmt->get_result();
  $lot = $res->fetch_assoc()['lot_number'] ?? null;
  $stmt->close();

  if (!$lot) {
    echo json_encode(["success" => false, "message" => "No lot found for this design number"]);
    exit;
  }

  // 2️⃣ Get product_id from product_lots using lot_number
  $stmt = $conn->prepare("SELECT product_id FROM product_lots WHERE lot_number=? LIMIT 1");
  $stmt->bind_param("i", $lot);
  $stmt->execute();
  $res = $stmt->get_result();
  $product_id = $res->fetch_assoc()['product_id'] ?? null;
  $stmt->close();

  if (!$product_id) {
    echo json_encode(["success" => false, "message" => "No product found for this lot"]);
    exit;
  }

  // 3️⃣ Get product name from products table
  $stmt = $conn->prepare("SELECT name FROM products WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $product_name = $res->fetch_assoc()['name'] ?? null;
  $stmt->close();

  if (!$product_name) {
    echo json_encode(["success" => false, "message" => "Product name not found"]);
    exit;
  }

  echo json_encode([
    "success" => true,
    "lot_number" => $lot,
    "product_id" => $product_id,
    "product_name" => $product_name
  ]);
  exit;
}



echo json_encode(["success" => false, "message" => "Invalid action"]);

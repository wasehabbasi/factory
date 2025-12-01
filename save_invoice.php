<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include "./db/db.php";

$data = json_decode(file_get_contents('php://input'), true);

$shop_id = $data['shop_id'];
$customer_name = $data['customer_name'];
$paandi_name = $data['paandi_name'];
$date = $data['date'];
$items = $data['items'];

$total_amount = 0;
foreach ($items as $item) {
    $total_amount += $item['qty'] * $item['rate'];
}

// ✅ Generate unique invoice number
$invoice_no = "SI-" . date("YmdHis") . "-" . rand(100, 999);
$grand_total = $total_amount; // same as total_amount for now

// ✅ Insert into shop_invoices

// Insert into shop_sales
foreach ($items as $item) {
    $product_id = intval($item['product_id']);
    $cutting = floatval($item['cutting']);
    $qty = floatval($item['qty']);
    $total_suits = intval($item['total_suits']);
    $rate = floatval($item['rate']);
    $design_number = intval($item['Design']);

    // ✅ Check if product exists
    $check = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check->bind_param("i", $product_id);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows === 0) {
        die(json_encode(["success" => false, "message" => "Product ID not found: $product_id"]));
    }
    $check->close();

    // ✅ Check available qty
    $sql = "SELECT SUM(s.remaining_qty) AS total_quantity FROM warehouse_to_shop s WHERE s.design_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $design_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $available_qty = floatval($result->fetch_assoc()['total_quantity'] ?? 0);
    $stmt->close();

    if ($qty > $available_qty) {
        die(json_encode([
            "success" => false,
            "message" => "Error: Quantity entered ($qty) is greater than available quantity ($available_qty) for Design #$design_number"
        ]));
    }

    // ✅ Insert invoice (only once, move outside loop ideally)
    $stmt = $conn->prepare("
        INSERT INTO shop_invoices 
        (date, invoice_no, shop_id, customer_name, paandi_name, total_amount, grand_total, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssissdd", $date, $invoice_no, $shop_id, $customer_name, $paandi_name, $total_amount, $grand_total);
    $stmt->execute();
    $invoice_id = $stmt->insert_id;
    $stmt->close();

    // ✅ Correct insert for shop_sales
    $stmt = $conn->prepare("
        INSERT INTO shop_sales (invoice_id, invoice_no, product_id, cutting, qty, rate, total_suits)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isidddd", $invoice_id, $invoice_no, $product_id, $cutting, $qty, $rate, $total_suits);
    $stmt->execute();
    $stmt->close();
}


echo json_encode([
    'success' => true,
    'invoice_no' => $invoice_no,
    'invoice_id' => $invoice_id,
    'grand_total' => $grand_total
]);

<?php
session_start();
if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

include './db/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$shop_id = $data['shop_id'] ?? null;

if(!$shop_id){
    echo json_encode(['success'=>false,'message'=>'Shop ID missing']);
    exit;
}

// 1️⃣ Fetch shop info
$shop_res = $conn->query("SELECT * FROM shops WHERE id = $shop_id");
if($shop_res->num_rows == 0){
    echo json_encode(['success'=>false,'message'=>'Shop not found']);
    exit;
}
$shop = $shop_res->fetch_assoc();

// 2️⃣ Fetch inventory for this shop
$inv_res = $conn->query("SELECT si.*, p.name AS product_name FROM shop_inventory si
                         LEFT JOIN products p ON si.product_id = p.id
                         WHERE si.shop_id = $shop_id AND si.quantity > 0");
if($inv_res->num_rows == 0){
    echo json_encode(['success'=>false,'message'=>'No inventory available for this shop']);
    exit;
}

$total_amount = 0;
$items = [];

while($item = $inv_res->fetch_assoc()){
    $line_total = $item['price'] * $item['quantity'];
    $total_amount += $line_total;

    $items[] = [
        'description' => $item['product_name'],
        'qty'         => $item['quantity'],
        'rate'        => $item['price'],
        'total'       => $line_total
    ];

    // Deduct inventory
    $stmt = $conn->prepare("UPDATE shop_inventory SET quantity = quantity - ? WHERE id = ?");
    $stmt->bind_param("di", $item['quantity'], $item['id']);
    $stmt->execute();
}

// 3️⃣ Insert into shop_invoice
$invoice_no = 'INV-' . date('YmdHis'); // generate invoice number
$stmt = $conn->prepare("INSERT INTO shop_invoice (date, invoice_no, shop_id, transfer_id, customer_name, total_amount, grand_total, created_at) VALUES (NOW(), ?, ?, 0, ?, ?, ?, NOW())");
$customer_name = "Walk-in Customer";
$grand_total = $total_amount; // you can add taxes if needed
$stmt->bind_param("siidd", $invoice_no, $shop_id, $customer_name, $total_amount, $grand_total);
$stmt->execute();
$invoice_id = $stmt->insert_id;

// 4️⃣ Insert invoice items
foreach($items as $inv_item){
    $stmt = $conn->prepare("INSERT INTO shop_invoice_item (invoice_id, description, qty, rate, total) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdid", $invoice_id, $inv_item['description'], $inv_item['qty'], $inv_item['rate'], $inv_item['total']);
    $stmt->execute();
}

echo json_encode(['success'=>true,'invoice_no'=>$invoice_no,'invoice_id'=>$invoice_id]);
$conn->close();

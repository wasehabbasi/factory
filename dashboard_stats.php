<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include './db/db.php'; // Database connection file

try {
    // --- Fetch all counts safely ---
    $vendors     = $conn->query("SELECT COUNT(*) AS cnt FROM vendors")->fetch_assoc()['cnt'] ?? 0;
    $warehouses  = $conn->query("SELECT COUNT(*) AS cnt FROM warehouses")->fetch_assoc()['cnt'] ?? 0;
    $products    = $conn->query("SELECT COUNT(*) AS cnt FROM products")->fetch_assoc()['cnt'] ?? 0;
    // $invoices    = $conn->query("SELECT COUNT(*) AS cnt FROM shop_invoices")->fetch_assoc()['cnt'] ?? 0;

    // Newly added stats
    $factories   = $conn->query("SELECT COUNT(*) AS cnt FROM factories")->fetch_assoc()['cnt'] ?? 0;
    $shops       = $conn->query("SELECT COUNT(*) AS cnt FROM shops")->fetch_assoc()['cnt'] ?? 0;
    $buyers      = $conn->query("SELECT COUNT(*) AS cnt FROM buyers")->fetch_assoc()['cnt'] ?? 0;
    $users       = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'] ?? 0;
    $employees   = $conn->query("SELECT COUNT(*) AS cnt FROM employees")->fetch_assoc()['cnt'] ?? 0;

    // --- Send JSON response ---
    echo json_encode([
        'success'    => true,
        'vendors'    => $vendors,
        'warehouses' => $warehouses,
        'products'   => $products,
        'invoices'   => $invoices,
        'factories'  => $factories,
        'shops'      => $shops,
        'buyers'     => $buyers,
        'users'      => $users,
        'employees'  => $employees
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

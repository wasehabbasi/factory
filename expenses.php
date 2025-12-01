<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "./db/db.php";
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';

// --- List Expenses ---
if ($action === "list") {
    $sql = "SELECT e.*, emp.name AS employee_name
            FROM expenses e
            LEFT JOIN employees emp ON e.employee_id = emp.id
            ORDER BY e.created_at DESC";
    $res = $conn->query($sql);
    echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

// --- Save Expense ---
if ($action === "save") {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'];
    $date = $_POST['date'];
    $month = $_POST['month'] ?? null;
    $employee_id = $_POST['employee_id'] ?? null;
    $details = $_POST['details'];
    $amount = $_POST['amount'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE expenses SET type=?, date=?, month=?, employee_id=?, details=?, amount=? WHERE id=?");
        $stmt->bind_param("sssdsdi", $type, $date, $month, $employee_id, $details, $amount, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (type,date,month,employee_id,details,amount) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("sssdsd", $type, $date, $month, $employee_id, $details, $amount);
    }

    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}

// --- Monthly Summary ---
if ($action === "monthly_summary") {
    $sql = "SELECT MONTHNAME(date) AS month,
                   SUM(CASE WHEN type='general' THEN amount ELSE 0 END) AS total_general,
                   SUM(CASE WHEN type='salary' THEN amount ELSE 0 END) AS total_salary,
                   SUM(amount) AS total
            FROM expenses
            GROUP BY MONTH(date)
            ORDER BY MIN(date) DESC";
    $res = $conn->query($sql);
    echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

echo json_encode(["error" => "Invalid action"]);

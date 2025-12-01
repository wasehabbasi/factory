<?php
session_start();
header('Content-Type: application/json');
include "./db/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please enter both username and password."]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.password_hash, u.status, r.name AS role_name, u.role_id as role_id
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = ? OR u.name = ?
        LIMIT 1
    ");

    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user["password_hash"])) {
        if ($user["status"] !== "active") {
            echo json_encode(["status" => "error", "message" => "Account is inactive. Contact administrator."]);
            exit;
        }

        // Save session
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["name"];
        $_SESSION["role"] = $user["role_name"];
        $_SESSION["role_id"] = $user["role_id"];

        // Update last login
        $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $upd->bind_param("i", $user["id"]);
        $upd->execute();

        echo json_encode(["status" => "success", "message" => "Login successful"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    }
}

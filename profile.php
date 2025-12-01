<?php
session_start();
include "./db/db.php";

if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit();
}

$username = $_SESSION['username'];

// GET → fetch profile
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, r.name AS role_name, u.last_login, u.status 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email=? OR u.name=? LIMIT 1
    ");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode(['success'=>true, 'data'=>$row]);
    exit();
}


// POST → update profile
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // get user
    $stmt = $conn->prepare("SELECT id,password_hash FROM users WHERE email=? OR name=? LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $uid = $user['id'];

    // password check (optional)
    if(!empty($old_password) && !empty($new_password)){
        if(password_verify($old_password, $user['password_hash'])){
            $newHash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt->bind_param("si", $newHash, $uid);
            $stmt->execute();
        } else {
            echo json_encode(['success'=>false,'message'=>'Old password incorrect']);
            exit();
        }
    }

    // update name/email
    $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $uid);
    $success = $stmt->execute();

    echo json_encode(['success'=>$success]);
    exit();
}

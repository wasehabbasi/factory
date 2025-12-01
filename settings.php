<?php
session_start();
include "./db/db.php";

$uploadDir = './uploads/';
if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// GET: fetch settings (sirf ek record expected hai)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM settings LIMIT 1");
    $row = $res->fetch_assoc();
    echo json_encode(["success" => true, "data" => $row]);
    exit();
}

// POST: update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if(!isset($_SESSION['username'])){
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $site_name = $_POST['site_name'] ?? '';
    $site_email = $_POST['site_email'] ?? '';

    $logo_url = '';
    $favicon_url = '';

    // Upload logo
    if(isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === 0){
        $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('logo_') . '.' . $ext;
        $destination = $uploadDir . $filename;
        if(move_uploaded_file($_FILES['logo_file']['tmp_name'], $destination)){
            $logo_url = $destination;
        }
    }

    // Upload favicon
    if(isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === 0){
        $ext = pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('favicon_') . '.' . $ext;
        $destination = $uploadDir . $filename;
        if(move_uploaded_file($_FILES['favicon_file']['tmp_name'], $destination)){
            $favicon_url = $destination;
        }
    }

    // Check if record already exists
    $res = $conn->query("SELECT id FROM settings LIMIT 1");
    if($res->num_rows > 0){
        $row = $res->fetch_assoc();
        $id = $row['id'];

        $sql = "UPDATE settings SET site_name=?, site_email=? ";
        $params = [$site_name, $site_email];
        $types = "ss";

        if($logo_url){
            $sql .= ", logo_url=? ";
            $params[] = $logo_url;
            $types .= "s";
        }
        if($favicon_url){
            $sql .= ", favicon_url=? ";
            $params[] = $favicon_url;
            $types .= "s";
        }
        $sql .= " WHERE id=?";
        $params[] = $id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (site_name, site_email, logo_url, favicon_url) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $site_name, $site_email, $logo_url, $favicon_url);
        $success = $stmt->execute();
    }

    echo json_encode(['success' => $success]);
    exit();
}

// Password change → user table se hoga
if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    parse_str(file_get_contents("php://input"), $vars);
    $old_password = $vars['old_password'] ?? '';
    $new_password = $vars['new_password'] ?? '';
    $username = $_SESSION['username'] ?? '';

    if(!$username){
        echo json_encode(['success'=>false,'message'=>'Not logged in']);
        exit();
    }

    // Verify old password
    $stmt = $conn->prepare("SELECT password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if(password_verify($old_password, $row['password'])){
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
        $stmt->bind_param("ss", $hash, $username);
        $success = $stmt->execute();
        echo json_encode(['success'=>$success]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Old password incorrect']);
    }
    exit();
}

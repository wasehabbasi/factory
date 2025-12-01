<?php
session_start();
include "./db/db.php";

header("Content-Type: application/json");
ini_set('display_errors',1);
error_reporting(E_ALL);

// Ensure logged-in
if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit();
}

// GET -> list users (no password_hash)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT u.id, u.name, u.email, r.name AS role_name, u.status, u.last_login
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.id DESC";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success'=>true,'data'=>$data]);
    exit();
}

// POST -> add / update user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // use POST variables (FormData)
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : null;
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$role_id) {
        echo json_encode(['success'=>false,'message'=>'Name, Email and Role are required']);
        exit();
    }

    // When creating new user: require password
    if (!$id && empty($password)) {
        echo json_encode(['success'=>false,'message'=>'Password is required for new user']);
        exit();
    }

    // Insert
    if (!$id) {
        // ensure email unique
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['success'=>false,'message'=>'Email already exists']);
            exit();
        }
        $check->close();

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssds", $name, $email, $hash, $role_id, $status);
        $ok = $stmt->execute();
        if ($ok) {
            $newId = $stmt->insert_id;
            echo json_encode(['success'=>true,'user'=>['id'=>$newId,'name'=>$name,'email'=>$email,'role_id'=>$role_id,'status'=>$status]]);
        } else {
            echo json_encode(['success'=>false,'message'=>$stmt->error]);
        }
        $stmt->close();
        exit();
    }

    // Update existing user
    // fetch current user to verify existence
    $q = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $q->bind_param("i", $id);
    $q->execute();
    $q->store_result();
    if ($q->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'User not found']);
        exit();
    }
    $q->close();

    // If password provided, update hash; otherwise keep existing
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password_hash=?, role_id=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->bind_param("sssisi", $name, $email, $hash, $role_id, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role_id=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->bind_param("ssisi", $name, $email, $role_id, $status, $id);
    }
    $ok = $stmt->execute();
    if ($ok) {
        echo json_encode(['success'=>true,'user'=>['id'=>$id,'name'=>$name,'email'=>$email,'role_id'=>$role_id,'status'=>$status]]);
    } else {
        echo json_encode(['success'=>false,'message'=>$stmt->error]);
    }
    $stmt->close();
    exit();
}

// DELETE -> delete user
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $del_vars);
    $id = isset($del_vars['id']) ? intval($del_vars['id']) : 0;
    if ($id) {
        // prevent deleting logged-in user accidentally (optional)
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            echo json_encode(['success'=>false,'message'=>'Cannot delete currently logged-in user']);
            exit();
        }
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success'=>$ok]);
        exit();
    }
    echo json_encode(['success'=>false,'message'=>'Invalid id']);
    exit();
}

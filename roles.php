<?php
session_start();
include "./db/db.php";

header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// GET → fetch all roles with permissions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT r.id, r.name, r.description, 
                   GROUP_CONCAT(m.name) as modules
            FROM roles r
            LEFT JOIN role_permissions rp ON r.id = rp.role_id
            LEFT JOIN modules m ON rp.module_id = m.id
            GROUP BY r.id
            ORDER BY r.id DESC"; // 👈 Added this line
    $res = $conn->query($sql);

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $row['modules'] = $row['modules'] ? explode(',', $row['modules']) : [];
        $data[] = $row;
    }

    echo json_encode(["success" => true, "data" => $data]);
    exit();
}


// POST → add or update role
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $modules = $_POST['modules'] ?? [];

    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Role name required']);
        exit();
    }

    if ($id) {
        // Update role
        $stmt = $conn->prepare("UPDATE roles SET name=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $description, $id);
        $ok = $stmt->execute();
        $stmt->close();

        // Clear old permissions
        $conn->query("DELETE FROM role_permissions WHERE role_id=$id");

        // Insert new permissions
        if (!empty($modules)) {
            $perm_stmt = $conn->prepare("INSERT INTO role_permissions (role_id, module_id) VALUES (?, ?)");
            foreach ($modules as $module_id) {
                $perm_stmt->bind_param("ii", $id, $module_id);
                $perm_stmt->execute();
            }
            $perm_stmt->close();
        }

        echo json_encode(['success' => $ok, 'id' => $id, 'name' => $name, 'description' => $description, 'modules' => $modules]);
        exit();

    } else {
        // Insert new role
        $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            $role_id = $stmt->insert_id;

            if (!empty($modules)) {
                $perm_stmt = $conn->prepare("INSERT INTO role_permissions (role_id, module_id) VALUES (?, ?)");
                foreach ($modules as $module_id) {
                    $perm_stmt->bind_param("ii", $role_id, $module_id);
                    $perm_stmt->execute();
                }
                $perm_stmt->close();
            }

            echo json_encode(['success' => true, 'id' => $role_id, 'name' => $name, 'description' => $description, 'modules' => $modules]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit();
    }
}

// DELETE → remove role
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $del_vars);
    $id = $del_vars['id'] ?? 0;

    if ($id) {
        $stmt = $conn->prepare("DELETE FROM roles WHERE id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $success]);
        exit();
    }
}

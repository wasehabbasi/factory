<?php
session_start();
include "./db/db.php";

$uploadDir = './uploads/';
if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// GET: fetch all vendors
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM vendors ORDER BY id DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
    exit();
}

// POST: add/update vendor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if(!isset($_SESSION['username'])){
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $id = $_POST['id'] ?? null; // agar id hai → edit, warna add
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    $image_url = '';
    if(isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0){
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('vendor_') . '.' . $ext;
        $destination = $uploadDir . $filename;
        if(move_uploaded_file($_FILES['image_file']['tmp_name'], $destination)){
            $image_url = $destination;
        }
    }

    if($id){ // update
        if($image_url){
            $stmt = $conn->prepare("UPDATE vendors SET name=?, phone=?, address=?, image_url=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $phone, $address, $image_url, $id);
        } else {
            $stmt = $conn->prepare("UPDATE vendors SET name=?, phone=?, address=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $phone, $address, $id);
        }
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'vendor' => ['id'=>$id,'name'=>$name,'phone'=>$phone,'address'=>$address,'image_url'=>$image_url]]);
        exit();
    } else { // insert
        $stmt = $conn->prepare("INSERT INTO vendors (name, phone, address, image_url) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $phone, $address, $image_url);
        if($stmt->execute()){
            $id = $stmt->insert_id;
            echo json_encode(['success'=>true,'vendor'=>['id'=>$id,'name'=>$name,'phone'=>$phone,'address'=>$address,'image_url'=>$image_url]]);
        } else {
            echo json_encode(['success'=>false,'message'=>$stmt->error]);
        }
        exit();
    }
}

// DELETE request
if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
    parse_str(file_get_contents("php://input"), $del_vars);
    $id = $del_vars['id'] ?? 0;
    if($id){
        $stmt = $conn->prepare("DELETE FROM vendors WHERE id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        echo json_encode(['success'=>$success]);
        exit();
    }
}

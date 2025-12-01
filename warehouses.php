<?php
session_start();
include "./db/db.php";

// GET: fetch all warehouses
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM warehouses ORDER BY id DESC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
    exit();
}

// POST: add or edit warehouse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_SESSION['username'])){
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if($id){ // update
        $stmt = $conn->prepare("UPDATE warehouses SET name=?, address=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $address, $phone, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success'=>true,'warehouse'=>['id'=>$id, 'name'=>$name, 'address'=>$address,'phone'=>$phone]]);
    } else { // add
        $stmt = $conn->prepare("INSERT INTO warehouses (name, address, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $address, $phone);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['success'=>true,'warehouse'=>['id'=>$newId, 'name'=>$name, 'address'=>$address,'phone'=>$phone]]);
    }
}

// DELETE: delete warehouse
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);
    $id = $deleteData['id'] ?? null;
    if($id){
        $stmt = $conn->prepare("DELETE FROM warehouses WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>'ID missing']);
    }
}
?>

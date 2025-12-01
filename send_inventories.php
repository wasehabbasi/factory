<?php
session_start();
include "./db/db.php";

$factory_id = $_POST['factory_id'] ?? null;
$vendor_id = $_POST['vendor_id'] ?? null;
$lot_number = $_POST['lot_number'] ?? null;

if(!$factory_id || !$vendor_id || !$lot_number){
    echo json_encode(['success'=>false,'message'=>'All fields required']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO send_inventories (factory_id,vendor_id,lot_number) VALUES (?,?,?)");
$stmt->bind_param("iii", $factory_id,$vendor_id,$lot_number);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'message'=>$stmt->error]);
}

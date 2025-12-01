<?php
session_start();
include "./db/db.php";

$factory_id = $_POST['factory_id'] ?? null;
$vendor_id = $_POST['vendor_id'] ?? null;
$lot_number = $_POST['lot_number'] ?? null;
$meter = $_POST['meter'] ?? null;
$shortage = $_POST['shortage'] ?? null;
$rejection = $_POST['rejection'] ?? null;

if(!$factory_id || !$vendor_id || !$lot_number || !$meter || !$shortage || !$rejection){
    echo json_encode(['success'=>false,'message'=>'All fields required']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO receive_inventories (factory_id,vendor_id,lot_number,meter,shortage,rejection) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("iiiddd", $factory_id,$vendor_id,$lot_number,$meter,$shortage,$rejection);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'message'=>$stmt->error]);
}

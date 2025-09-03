<?php
include 'database.php';
$id = $_POST['id'] ?? '';
$name = $_POST['name'] ?? '';
$programme = $_POST['programme'] ?? '';
$intake = $_POST['intake'] ?? '';

$sql = "UPDATE student_detail__1_ SET Name=?, Programme=?, Intake=? WHERE ID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $programme, $intake, $id);
if($stmt->execute()){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>$conn->error]);
}

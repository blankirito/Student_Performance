<?php
include 'database.php';
$id = $_GET['id'] ?? '';
if(!$id) { echo json_encode(['success'=>false,'message'=>'No ID provided']); exit; }

$sql = "SELECT ID, Name, Programme, Intake FROM student_detail__1_ WHERE ID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()){
    echo json_encode(['success'=>true,'student'=>$row]);
} else {
    echo json_encode(['success'=>false,'message'=>'Student not found']);
}

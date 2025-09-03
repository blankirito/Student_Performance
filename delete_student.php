<?php
include 'database.php';
$id = $_GET['id'] ?? '';
if(!$id) { echo json_encode(['success'=>false,'message'=>'No ID provided']); exit; }

$sql = "DELETE FROM student_detail__1_ WHERE ID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
if($stmt->execute()){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>$conn->error]);
}

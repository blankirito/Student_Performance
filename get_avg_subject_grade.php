<?php
include 'db_connect.php';
header('Content-Type: application/json');

$sql = "SELECT Subject_Name, Grade FROM student_detail__1_";
$result = $conn->query($sql);

$subjects = [];
$grades = [];

while($row = $result->fetch_assoc()){
    $subjects[] = $row['Subject_Name'];
    $grades[] = $row['Grade']; 
}

echo json_encode([
    'subjects' => $subjects,
    'grades' => $grades
]);

$conn->close();
?>

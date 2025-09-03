<?php
include 'database.php';

$sql = "SELECT ID, Name FROM student_detail__1_ ORDER BY Name ASC";
$result = $conn->query($sql);

$students = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = ['id' => $row['ID'], 'name' => $row['Name']];
    }
}

echo json_encode($students);
?>

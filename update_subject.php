<?php
include 'database.php';

$code = $_POST['code'] ?? '';    
$newName = $_POST['name'] ?? '';   

if (!$code || !$newName) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$sql = "UPDATE student_detail__1_ SET Subject_Name=? WHERE Subject_Code=?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

$stmt->bind_param("ss", $newName, $code);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();

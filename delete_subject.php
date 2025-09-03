<?php
include 'database.php';

$code = $_POST['code'] ?? '';
if (!$code) {
    echo json_encode(['success' => false, 'message' => 'No Subject Code provided']);
    exit;
}

$sql = "DELETE FROM student_detail__1_ WHERE Subject_Code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $code);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>

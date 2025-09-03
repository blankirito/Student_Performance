<?php
include 'database.php';

header('Content-Type: application/json');

$code = $_GET['subject_code'] ?? '';
$registered = [];
$unregistered = [];

$notTakenGrades = ['N', 'CT', 'X'];

if ($code) {
    $stmt = $conn->prepare("
        SELECT ID, Name, Grade 
        FROM student_detail__1_ 
        WHERE Subject_Code = ?
    ");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $grade = strtoupper(trim($row['Grade'] ?? ''));
        
        if (in_array($grade, $notTakenGrades, true)) {
            $unregistered[] = $row; 
        } else {
            $registered[] = $row; 
        }
    }
}

echo json_encode([
    'success' => true,
    'registered' => $registered,
    'unregistered' => $unregistered
]);
?>

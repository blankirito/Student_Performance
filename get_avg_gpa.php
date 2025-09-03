<?php
include 'db_connect.php'; 

$conn = new mysqli("localhost", "root", "", "student_performance");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, intake, GPA FROM student_detail__1_ ORDER BY id, intake";
$result = $conn->query($sql);

$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $gpa = floatval($row['GPA']);
        $intake = $row['intake'];

        if (!isset($students[$id])) {
            $students[$id] = ['gpas' => [], 'intake' => $intake];
        }
        $students[$id]['gpas'][] = $gpa;
    }
}

foreach ($students as &$data) {
    $data['cgpa'] = array_sum($data['gpas']) / count($data['gpas']);
}

$intakeGPA = [];
$intakeCGPA = [];
$intakeCounts = [];

foreach ($students as $data) {
    $intake = $data['intake'];
    $gpa = end($data['gpas']); 
    $cgpa = $data['cgpa'];

    if (!isset($intakeGPA[$intake])) {
        $intakeGPA[$intake] = 0;
        $intakeCGPA[$intake] = 0;
        $intakeCounts[$intake] = 0;
    }
    $intakeGPA[$intake] += $gpa;
    $intakeCGPA[$intake] += $cgpa;
    $intakeCounts[$intake]++;
}

$output = [];
foreach ($intakeGPA as $intake => $totalGPA) {
    $output[] = [
        'intake' => $intake,
        'avgGPA' => round($totalGPA / $intakeCounts[$intake], 2),
        'avgCGPA' => round($intakeCGPA[$intake] / $intakeCounts[$intake], 2)
    ];
}

usort($output, function($a, $b){
    $dateA = DateTime::createFromFormat('M-y', $a['intake']);
    $dateB = DateTime::createFromFormat('M-y', $b['intake']);
    return $dateA <=> $dateB;
});

header('Content-Type: application/json');
echo json_encode($output);
$conn->close();
?>

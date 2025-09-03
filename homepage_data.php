<?php
include 'database.php';

$program = $_GET['program'] ?? '';
$filter = $_GET['filter'] ?? 'highest';
$subject = $_GET['subject'] ?? '';
$intake  = $_GET['intake'] ?? '';

$totalStudent = $program ? getTotalStudentsByProgram($conn, $program) : getTotalStudents($conn);

$cgpaData = $program ? getAverageCGPAByProgram($conn, $program) : getAverageCGPAByIntake($conn);

if ($subject || $intake) {
    $statusCountsFiltered = getStudentStatusCounts($conn, $subject ?: null, $intake ?: null);
} else {
    $statusCountsFiltered = $program ? getStudentStatusCountsByProgram($conn, $program) : getStudentStatusCounts($conn);
}

if ($subject || $intake) {
    $passFailCountsFiltered = getPassFailCountsFiltered($conn, $subject ?: null, $intake ?: null);
} else {
    $passFailCountsFiltered = $program ? getPassFailCountsByProgram($conn, $program) : getPassFailCountsFiltered($conn);
}

$topSubjects = getTop5SubjectsByAverage($conn, $filter, $program);
$subjects = [];
$scores = [];
foreach ($topSubjects as $s) {
    $subjects[] = $s['Subject_Name'];
    $scores[] = round($s['avg_score'], 2);
}

$alerts = [];
$stmt = $conn->prepare("SELECT Name, Subject_Name, Grade FROM student_detail__1_ 
                        WHERE Grade NOT IN ('A+','A','A-','B+','B','B-','C+','C','N','CT')");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $alerts[] = "Student {$row['Name']} in {$row['Subject_Name']} has a low grade ({$row['Grade']})";
}

$sql = "SELECT Subject_Name, AVG(Percentage) AS avg_score 
        FROM student_detail__1_ 
        GROUP BY Subject_Name 
        HAVING AVG(Percentage) < 50";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $alerts[] = "Subject {$row['Subject_Name']} has a low average score ({$row['avg_score']})";
}

echo json_encode([
    'totalStudent' => $totalStudent,
    'cgpaLabels' => array_map(fn($i)=>$i['intake'], $cgpaData),
    'cgpaDataPoints' => array_map(fn($i)=>round($i['avg_cgpa'],2), $cgpaData),
    'statusLabels' => ['Dean\'s List','Normal','Probation'],
    'statusDataPoints' => [
        $statusCountsFiltered['dean'] ?? 0,
        $statusCountsFiltered['normal'] ?? 0,
        $statusCountsFiltered['probation'] ?? 0
    ],
    'passFailLabels' => ['Pass','Fail'],
    'passFailData' => [
        $passFailCountsFiltered['pass'] ?? 0,
        $passFailCountsFiltered['fail'] ?? 0
    ],
    'topSubjects' => $subjects,
    'topScores' => $scores,
    'alerts' => $alerts
]);
?>

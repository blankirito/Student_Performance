<?php
include 'db_connect.php';

/*Homepage*/
function getTotalStudents() {
    $conn = new mysqli("localhost", "root", "", "student_performance");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT COUNT(DISTINCT ID) AS total FROM student_detail__1_";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $conn->close();
    return $row['total'];
}

function getAverageCGPAByIntake($conn) {
    $sql = "SELECT id, intake, GPA FROM student_detail__1_ ORDER BY id, intake";
    $result = $conn->query($sql);

    if (!$result) {
        return [];
    }

    $students = []; 

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $gpa = floatval($row['GPA']);
        $intake = $row['intake'];

        if (!isset($students[$id])) {
            $students[$id] = ['gpas' => [], 'intake' => $intake];
        }
        $students[$id]['gpas'][] = $gpa;
    }

    foreach ($students as $id => &$data) {
        $data['cgpa'] = array_sum($data['gpas']) / count($data['gpas']);
    }

    $intakeCGPAs = [];
    $intakeCounts = [];

    foreach ($students as $data) {
        $intake = $data['intake'];
        $cgpa = $data['cgpa'];

        if (!isset($intakeCGPAs[$intake])) {
            $intakeCGPAs[$intake] = 0;
            $intakeCounts[$intake] = 0;
        }
        $intakeCGPAs[$intake] += $cgpa;
        $intakeCounts[$intake]++;
    }

    $avgCGPAByIntake = [];
    foreach ($intakeCGPAs as $intake => $totalCGPA) {
        $avgCGPAByIntake[] = [
            'intake' => $intake,
            'avg_cgpa' => $totalCGPA / $intakeCounts[$intake]
        ];
    }

    usort($avgCGPAByIntake, function($a, $b) {
        $dateA = DateTime::createFromFormat('M-y', $a['intake']);
        $dateB = DateTime::createFromFormat('M-y', $b['intake']);
        return $dateA <=> $dateB;
    });

    return $avgCGPAByIntake;
}

function getStudentStatusCounts($conn, $subject = '', $intake = '') {
    $sql = "SELECT id, grade FROM student_detail__1_ WHERE 1=1";

    if ($subject !== '') {
        $sql .= " AND Subject_Name = '".$conn->real_escape_string($subject)."'";
    }

    if ($intake !== '') {
        $sql .= " AND Intake = '".$conn->real_escape_string($intake)."'";
    }

    $result = $conn->query($sql);

    $gradePointsMap = [
        'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D' => 1.0, 'F' => 0,
    ];

    $studentGrades = [];

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $grade = strtoupper(trim($row['grade']));

        if (!isset($gradePointsMap[$grade])) continue;

        $point = $gradePointsMap[$grade];

        $studentGrades[$id]['total'] = ($studentGrades[$id]['total'] ?? 0) + $point;
        $studentGrades[$id]['count'] = ($studentGrades[$id]['count'] ?? 0) + 1;
    }

    $statusCounts = ['dean' => 0, 'normal' => 0, 'probation' => 0];
    foreach ($studentGrades as $id => $data) {
        $avg = $data['total'] / $data['count'];
        if ($avg >= 3.0) {
            $statusCounts['dean']++;
        } elseif ($avg >= 2.0) {
            $statusCounts['normal']++;
        } else {
            $statusCounts['probation']++;
        }
    }

    return $statusCounts;
}

function getAllSubjects($conn) {
    $result = $conn->query("SELECT DISTINCT Subject_Name FROM student_detail__1_ ORDER BY Subject_Name");
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['Subject_Name'];
    }
    return $subjects;
}

function getAllIntakes($conn) {
    $result = $conn->query("SELECT DISTINCT Intake FROM student_detail__1_ ORDER BY Intake");
    $intakes = [];
    while ($row = $result->fetch_assoc()) {
        $intakes[] = $row['Intake'];
    }
    return $intakes;
}

function getTop5SubjectsByAverage($conn, $filter = 'highest', $program = '') {
    $order = $filter === 'lowest' ? 'ASC' : 'DESC';
    $sql = "
        SELECT Subject_Name, AVG(Percentage) AS avg_score
        FROM student_detail__1_
        " . ($program ? "WHERE Programme = '".$conn->real_escape_string($program)."'" : "") . "
        GROUP BY Subject_Name
        ORDER BY avg_score $order
        LIMIT 5
    ";

    $result = $conn->query($sql);
    $topSubjects = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $topSubjects[] = $row;
        }
    }

    return $topSubjects;
}

function getTotalStudentsByProgram($conn, $program) {
    if (!$program) return getTotalStudents();

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT ID) AS total FROM student_detail__1_ WHERE Programme = ?");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getAverageCGPAByProgram($conn, $program) {
    $allData = getAverageCGPAByIntake($conn);
    if (!$program) return $allData;

    $sql = "SELECT id, intake, GPA FROM student_detail__1_ WHERE Programme = ? ORDER BY id, intake";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $gpa = floatval($row['GPA']);
        $intake = $row['intake'];
        if (!isset($students[$id])) {
            $students[$id] = ['gpas' => [], 'intake' => $intake];
        }
        $students[$id]['gpas'][] = $gpa;
    }

    foreach ($students as $id => &$data) {
        $data['cgpa'] = array_sum($data['gpas']) / count($data['gpas']);
    }

    $intakeCGPAs = [];
    $intakeCounts = [];
    foreach ($students as $data) {
        $intake = $data['intake'];
        $cgpa = $data['cgpa'];
        if (!isset($intakeCGPAs[$intake])) {
            $intakeCGPAs[$intake] = 0;
            $intakeCounts[$intake] = 0;
        }
        $intakeCGPAs[$intake] += $cgpa;
        $intakeCounts[$intake]++;
    }

    $avgCGPAByIntake = [];
    foreach ($intakeCGPAs as $intake => $totalCGPA) {
        $avgCGPAByIntake[] = [
            'intake' => $intake,
            'avg_cgpa' => $totalCGPA / $intakeCounts[$intake]
        ];
    }

    usort($avgCGPAByIntake, function($a, $b) {
        $dateA = DateTime::createFromFormat('M-y', $a['intake']);
        $dateB = DateTime::createFromFormat('M-y', $b['intake']);
        return $dateA <=> $dateB;
    });

    return $avgCGPAByIntake;
}

function getStudentStatusCountsByProgram($conn, $program) {
    if (!$program) return getStudentStatusCounts($conn);

    $stmt = $conn->prepare("SELECT id, grade FROM student_detail__1_ WHERE Programme = ?");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();

    $gradePointsMap = [
        'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D' => 1.0, 'F' => 0,
    ];

    $studentGrades = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $grade = strtoupper(trim($row['grade']));
        if (!isset($gradePointsMap[$grade])) continue;
        $point = $gradePointsMap[$grade];
        $studentGrades[$id]['total'] = ($studentGrades[$id]['total'] ?? 0) + $point;
        $studentGrades[$id]['count'] = ($studentGrades[$id]['count'] ?? 0) + 1;
    }

    $statusCounts = ['dean' => 0, 'normal' => 0, 'probation' => 0];
    foreach ($studentGrades as $data) {
        $avg = $data['total'] / $data['count'];
        if ($avg >= 3.0) $statusCounts['dean']++;
        elseif ($avg >= 2.0) $statusCounts['normal']++;
        else $statusCounts['probation']++;
    }

    return $statusCounts;
}

function getPassFailCountsByProgram($conn, $program) {
    $passGrades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C'];
    $stmt = $conn->prepare("SELECT grade FROM student_detail__1_ WHERE Programme = ? AND grade IS NOT NULL");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();

    $pass = 0;
    $fail = 0;
    while ($row = $result->fetch_assoc()) {
        $grade = strtoupper(trim($row['grade']));
        if (in_array($grade, $passGrades)) $pass++;
        else $fail++;
    }
    return ['pass' => $pass, 'fail' => $fail];
}

function getPassFailCountsFiltered($conn, $subject = '', $intake = '') {
    $passGrades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C'];
    $sql = "SELECT grade FROM student_detail__1_ WHERE grade IS NOT NULL";
    $params = [];
    $types = '';

    if ($subject) {
        $sql .= " AND Subject_Name = ?";
        $types .= 's';
        $params[] = $subject;
    }
    if ($intake) {
        $sql .= " AND Intake = ?";
        $types .= 's';
        $params[] = $intake;
    }

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $pass = 0;
    $fail = 0;
    while ($row = $result->fetch_assoc()) {
        $grade = strtoupper(trim($row['grade']));
        if (in_array($grade, $passGrades)) $pass++;
        else $fail++;
    }
    return ['pass' => $pass, 'fail' => $fail];
}



function getTop5SubjectsByAverageProgram($conn, $program) {
    $stmt = $conn->prepare("
        SELECT Subject_Name, AVG(Percentage) AS avg_score
        FROM student_detail__1_
        WHERE Programme = ?
        GROUP BY Subject_Name
        ORDER BY avg_score DESC
        LIMIT 5
    ");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();
    $topSubjects = [];
    while ($row = $result->fetch_assoc()) $topSubjects[] = $row;
    return $topSubjects;
}

function getPerformanceAlerts($conn) {
    $alerts = [];

    $stmt = $conn->prepare("SELECT Name, Subject_Name, Grade 
                        FROM student_detail__1_ 
                        WHERE Grade NOT IN ('A+','A','A-','B+','B','B-','C+','C','N','CT')");

    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $alerts[] = "Student {$row['Name']} in {$row['Subject_Name']} has a low grade ({$row['Grade']})";
    }

    $sql = "SELECT Subject_Name, AVG(score) as avg_score 
            FROM student_detail__1_ 
            GROUP BY Subject_Name 
            HAVING AVG(score) < 50";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
        $alerts[] = "Subject {$row['Subject_Name']} has a low average score ({$row['avg_score']})";
    }

    return $alerts;
}


/*Student Profile*/
function gradeToPoint($grade) {
    $map = [
        "A+" => 4.0, "A" => 4.0,
        "A-" => 3.7,
        "B+" => 3.3, "B" => 3.0,
        "C+" => 2.7, "C" => 2.0,
        "D" => 1.0,
        "F" => 0.0
    ];
    return $map[$grade] ?? null; 
}

function calculateGPA_CGPA($courses, $intakeMonth, $intakeYear) {
    $semesters = [];
    $completedCourses = [];
    $semesterMonths = ["Jun", "Oct", "Mar"]; 

    $semesterIndex = array_search($intakeMonth, $semesterMonths);
    $year = (int)$intakeYear;

    $i = 0;
    while ($i < count($courses)) {
        $semesterCourses = array_slice($courses, $i, 4);
        $i += count($semesterCourses);

        $semesterName = $semesterMonths[$semesterIndex] . " " . $year;

        $semesterIndex = ($semesterIndex + 1) % 3;
        if ($semesterIndex == 0) $year++;

        $points = 0;
        $credits = 0;
        foreach ($semesterCourses as $course) {
            $gp = gradeToPoint($course['grade']);
            if ($gp !== null) {
                $points += $gp * $course['credit'];
                $credits += $course['credit'];
                $completedCourses[] = $course;
            }
        }

        $gpa = $credits > 0 ? $points / $credits : null;

        $totalPoints = 0;
        $totalCredits = 0;
        foreach ($completedCourses as $c) {
            $gp = gradeToPoint($c['grade']);
            if ($gp !== null) {
                $totalPoints += $gp * $c['credit'];
                $totalCredits += $c['credit'];
            }
        }
        $cgpa = $totalCredits > 0 ? $totalPoints / $totalCredits : null;

        $semesters[] = [
            "semester" => $semesterName,
            "gpa" => $gpa,
            "cgpa" => $cgpa
        ];
    }

    return $semesters;
}

/*Program Leader Dashboard*/
function getAllStudents($conn) {
    $result = $conn->query("SELECT *, 
        CASE WHEN grade IS NOT NULL AND grade IN ('A+','A','A-','B+','B','B-','C+','C','C-') THEN 'Pass' 
             WHEN grade IS NOT NULL AND grade IN ('D','F') THEN 'Fail' 
             ELSE 'Unknown' END AS PassFail,
        CASE WHEN grade IN ('A+','A','A-') THEN 'Dean\'s List'
             WHEN grade IN ('D','F') THEN 'Probation'
             ELSE 'Normal' END AS Status
        FROM student_detail__1_");
    $students = [];
    while($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    return $students;
}

function getPassFailCounts($conn) {
    $sql = "SELECT grade FROM student_detail__1_";
    $result = $conn->query($sql);

    $counts = ['pass' => 0, 'fail' => 0];
    $gradePointsMap = [
        'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D' => 1.0, 'F' => 0,
    ];

    while ($row = $result->fetch_assoc()) {
        $grade = strtoupper(trim($row['grade']));
        if (!isset($gradePointsMap[$grade])) continue;
        if ($gradePointsMap[$grade] >= 2.0) {
            $counts['pass']++;
        } else {
            $counts['fail']++;
        }
    }

    return $counts;
}

$gradeMap = [
    'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
    'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
    'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
    'D' => 1.0, 'F' => 0
];

function getTopStudentsByCGPA($conn, $limit = 10) {
    global $gradeMap;
    $sql = "SELECT ID, Name, Programme, Grade, Subject_Name FROM student_detail__1_";
    $result = $conn->query($sql);

    $studentData = [];

    while ($row = $result->fetch_assoc()) {
        $id = $row['ID'];
        $grade = strtoupper(trim($row['Grade']));
        if (!isset($gradeMap[$grade])) continue;

        $point = $gradeMap[$grade];
        $studentData[$id]['total'] = ($studentData[$id]['total'] ?? 0) + $point;
        $studentData[$id]['count'] = ($studentData[$id]['count'] ?? 0) + 1;
        $studentData[$id]['Name'] = $row['Name'];
        $studentData[$id]['Programme'] = $row['Programme'];
    }

    $studentsCGPA = [];
    foreach ($studentData as $id => $data) {
        $avg = $data['total'] / $data['count'];
        $studentsCGPA[] = [
            'id' => $id,
            'Name' => $data['Name'],
            'Programme' => $data['Programme'],
            'cgpa' => round($avg, 2)
        ];
    }

    usort($studentsCGPA, fn($a, $b) => $b['cgpa'] <=> $a['cgpa']);

    return $studentsCGPA;
}

function getLowestStudentsByGPA($conn, $limit = 10) {
    global $gradeMap;
    $sql = "SELECT ID, Name, Programme, Grade, Subject_Name FROM student_detail__1_";
    $result = $conn->query($sql);

    $studentData = [];

    while ($row = $result->fetch_assoc()) {
        $id = $row['ID'];
        $grade = strtoupper(trim($row['Grade']));
        if (!isset($gradeMap[$grade])) continue;

        $point = $gradeMap[$grade];
        $studentData[$id]['total'] = ($studentData[$id]['total'] ?? 0) + $point;
        $studentData[$id]['count'] = ($studentData[$id]['count'] ?? 0) + 1;
        $studentData[$id]['Name'] = $row['Name'];
        $studentData[$id]['Programme'] = $row['Programme'];
    }

    $studentsCGPA = [];
    foreach ($studentData as $id => $data) {
        $avg = $data['total'] / $data['count'];
        $studentsCGPA[] = [
            'ID' => $id,
            'Name' => $data['Name'],
            'Programme' => $data['Programme'],
            'gpa' => round($avg, 2)
        ];
    }

    usort($studentsCGPA, fn($a, $b) => $a['gpa'] <=> $b['gpa']);

    return array_slice($studentsCGPA, 0, $limit);
}

function getAcademicPerformanceChart1($conn) {
    return [
        'labels' => ['Intake Jan', 'Intake May', 'Intake Sep'],
        'datasets' => [[
            'label' => 'Average GPA',
            'data' => [3.5, 3.2, 3.8],
            'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
            'borderColor' => 'rgba(54, 162, 235, 1)',
            'borderWidth' => 1
        ]]
    ];
}

function getAcademicPerformanceChart2($conn) {
    return [
        'labels' => ['Math', 'Science', 'English'],
        'datasets' => [[
            'label' => 'Average Score',
            'data' => [78, 85, 90],
            'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
            'borderColor' => 'rgba(255, 99, 132, 1)',
            'borderWidth' => 1
        ]]
    ];
}



?>

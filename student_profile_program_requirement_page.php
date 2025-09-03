<?php
include 'database.php';

if (!isset($_GET['id']) || trim($_GET['id']) === '') {
    header("Location: student_management_page.php");
    exit;
}
$id = $_GET['id'];

$stmt = $conn->prepare("SELECT ID, Name, Programme, Intake FROM student_detail__1_ WHERE ID = ? LIMIT 1");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    $student = null;
}

$courses = [];
$courseStmt = $conn->prepare("SELECT Subject_Code, Grade FROM student_detail__1_ WHERE ID = ?");
$courseStmt->bind_param("s", $id);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();

while ($row = $courseResult->fetch_assoc()) {
    $courses[] = [
        "course" => $row["Subject_Code"],
        "grade" => $row["Grade"]
    ];
}

$completeCount = 0;
$incompleteCount = 0;
$creditEarned = 0;

$gradeComplete = ["CT","A","A+","A-","B","B+","B-","C","C+","C-"];
$gradeIncomplete = ["F","N"];

foreach ($courses as $course) {
    $grade = strtoupper(trim($course['grade'])); 
    if (in_array($grade, $gradeComplete)) {
        $completeCount++;
        $creditEarned += 2; 
    } elseif (in_array($grade, $gradeIncomplete)) {
        $incompleteCount++;
    }
}

$totalSubjects = $completeCount + $incompleteCount;
$totalCredits = $totalSubjects * 2;

$completeSubjects = [];
$incompleteSubjects = [];
$upcomingSubjects = [];

foreach ($courses as $course) {
    $grade = strtoupper(trim($course['grade']));

    if (in_array($grade, $gradeComplete)) {
        $completeSubjects[] = $course;
    } elseif (in_array($grade, $gradeIncomplete)) {
        $incompleteSubjects[] = $course;
        if ($grade === 'N') {
            $upcomingSubjects[] = $course;
        }
    }
}

$status = 'inactive';
if (!empty($courses)) {
    foreach ($courses as $c) {
        $grade = strtoupper(trim($c['grade']));
        if ($grade === 'N' || $grade === 'F') {
            $status = 'active';
            break;
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Student Profile</title>
		<link rel = "stylesheet" href = "page.css">
		<meta charset="utf-8">
	</head>
	<style>
		.tab-content ul {
			list-style-type: none;
			padding: 0;
			margin: 0;
		}

		.tab-content li {
			padding: 8px 12px;
			margin-bottom: 6px;
			border-radius: 8px;
			background-color: #f3f3f3;
			display: flex;
			justify-content: space-between;
			font-weight: 500;
		}

		.tab-content li.complete {
			background-color: #d4edda;
		}

		.tab-content li.incomplete {
			background-color: #f8d7da;
		}

		.tab-content li.upcoming {
			background-color: #fff3cd; 
		}

		.tab-content ul {
			list-style: none;
			padding-left: 0;
		}

		.tab-content li.complete { color: #4caf50; }
		.tab-content li.incomplete { color: #f44336; }
		.tab-content li.upcoming { color: #ff9800; }
	</style>
	<body>
		<div class="main-layout">
			<nav class="sidebar">
			  <ul>
				<li><a href="homepage.php">Homepage</a></li>
				<li><a href="student_profile_academic_status_page.php">Student Profile</a></li>
				<li><a href="program_leader_dashboard_academic_performance_page.php">Program Leader Dashboard</a></li>
				<li><a href="student_management_page.php">Student & Course</a></li>
			  </ul>
			</nav>
			
			<div class="main-content">
				<div class = "top-bar">
					<h1 class = "page-title">Student Profile</h1>
					
					<div class="search-wide">
						<input type="text" id="studentSearch" placeholder="Type to search...">
						<ul id="studentDropdown"></ul>
					</div>

				</div>
				
				<div class="rounded-box header-box student-info-box" style="display: flex; align-items: flex-start; gap: 15px; padding: 15px; background: <?php echo ($status === 'active') ? '#d1fae5' : '#fee2e2'; ?>; border-radius:12px;">
					<?php if ($student): ?>
						<div class="student-avatar" style="flex-shrink: 0;">
							<img src="default_avatar.png" alt="Student Avatar" width="60" height="60" style="border-radius:50%; object-fit:cover;">
						</div>
						<div class="student-details" style="display: flex; flex-direction: column; justify-content: center; gap: 5px;">
							<div class="student-row" style="display: flex; gap: 10px; align-items: baseline;">
								<span class="student-name" style="font-weight:bold; font-size:1.2rem;"><?php echo htmlspecialchars($student['Name']); ?></span>
								<span class="student-id"><?php echo htmlspecialchars($student['ID']); ?></span>
							</div>
							<div class="student-row">
								<span class="student-programme">Programme: <?php echo htmlspecialchars($student['Programme']); ?></span>
							</div>
							<div class="student-row">
								<span class="student-intake">Intake: <?php echo htmlspecialchars($student['Intake']); ?></span>
							</div>
						</div>
					<?php else: ?>
						<p>Student not found.</p>
					<?php endif; ?>
				</div>

				<div class="content-wrapper">
					<div class = "inner-wrapper">
						<div class="nav-links">
							<a href="student_profile_academic_status_page.php?id=<?php echo urlencode($id); ?>">Academic Status</a>
							<a href="student_profile_program_requirement_page.php?id=<?php echo urlencode($id); ?>" class="active">Program Requirement</a>
						</div>
						<div class="rounded-box content-box">
							<div class = "box-row-triple">
								<div class = "rounded-box triple-box">
									<canvas id="completeChart" width="250" height="250"></canvas>
								</div>
								<div class = "rounded-box triple-box">
									<canvas id="incompleteChart" width="250" height="250"></canvas>
								</div>
								<div class = "rounded-box triple-box">
									<canvas id="creditChart" width="250" height="250"></canvas>
								</div>
							</div>
							
							<div class="tab-container">
							  <button class="tab-button active" onclick="showTab('complete')">Complete</button>
							  <button class="tab-button" onclick="showTab('incomplete')">Incomplete</button>
							  <button class="tab-button" onclick="showTab('upcoming')">Upcoming</button>
							</div>

							<div id="complete" class="tab-content active">
								<ul>
								<?php foreach ($courses as $c): 
									$grade = strtoupper(trim($c['grade']));
									if (in_array($grade, $gradeComplete)): ?>
										<li class="complete">
											<?php echo htmlspecialchars($c['course']) . " - Grade: " . htmlspecialchars($c['grade']); ?>
										</li>
								<?php endif; endforeach; ?>

								</ul>
							</div>

							<div id="incomplete" class="tab-content">
								<ul>
								<?php foreach ($courses as $c): 
									$grade = strtoupper(trim($c['grade']));
									if (in_array($grade, $gradeIncomplete)): ?>
										<li class="incomplete">
											<?php echo htmlspecialchars($c['course']) . " - Grade: " . htmlspecialchars($c['grade']); ?>
										</li>
								<?php endif; endforeach; ?>

								</ul>
							</div>

							<div id="upcoming" class="tab-content">
								<ul>
								<?php foreach ($courses as $c): 
									$grade = strtoupper(trim($c['grade']));
									if ($grade === 'N'): ?>
										<li class="upcoming">
											<?php echo htmlspecialchars($c['course']) . " - Grade: " . htmlspecialchars($c['grade']); ?>
										</li>
								<?php endif; endforeach; ?>

								</ul>
							</div>


						</div>
					</div>
				</div>


														
			</div>		
		</div>
		
			<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
			<script>
				function showTab(tabName) {
				  document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
				  document.querySelectorAll(".tab-content").forEach(tab => tab.classList.remove("active"));

				  document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add("active");
				  document.getElementById(tabName).classList.add("active");
				}
				
				const completeCount = <?php echo $completeCount; ?>;
				const incompleteCount = <?php echo $incompleteCount; ?>;
				const creditEarned = <?php echo $creditEarned; ?>;
				const totalCredits = <?php echo $totalCredits; ?>;

				new Chart(document.getElementById('completeChart'), {
					type: 'doughnut',
					data: {
						labels: ['Complete', 'Remaining'],
						datasets: [{
							data: [completeCount, <?php echo $totalSubjects; ?> - completeCount],
							backgroundColor: ['#4caf50','#e0e0e0'],
						}]
					},
					options: { responsive: false, plugins: { title: { display: true, text: 'Complete Subjects' } } }
				});

				new Chart(document.getElementById('incompleteChart'), {
					type: 'doughnut',
					data: {
						labels: ['Incomplete', 'Completed'],
						datasets: [{
							data: [incompleteCount, completeCount],
							backgroundColor: ['#f44336','#e0e0e0'],
						}]
					},
					options: { responsive: false, plugins: { title: { display: true, text: 'Incomplete Subjects' } } }
				});

				new Chart(document.getElementById('creditChart'), {
					type: 'doughnut',
					data: {
						labels: ['Credits Earned', 'Remaining'],
						datasets: [{
							data: [creditEarned, totalCredits - creditEarned],
							backgroundColor: ['#2196f3','#e0e0e0'],
						}]
					},
					options: { responsive: false, plugins: { title: { display: true, text: 'Credit Earned' } } }
				});
				
				const searchInput = document.getElementById('studentSearch');
				const dropdown = document.getElementById('studentDropdown');
				let allStudents = [];

				fetch('search_students.php')
				  .then(res => res.json())
				  .then(data => { allStudents = data; });

				searchInput.addEventListener('input', () => {
				  const query = searchInput.value.toLowerCase();
				  const filtered = allStudents.filter(s => 
					  s.name.toLowerCase().includes(query) || s.id.toLowerCase().includes(query)
				  );
				  renderDropdown(filtered);
				});

				function renderDropdown(students) {
				  dropdown.innerHTML = "";
				  if(students.length === 0 || searchInput.value.trim() === ""){
					dropdown.style.display = "none";
					return;
				  }
				  students.forEach(s => {
					const li = document.createElement('li');
					li.textContent = `${s.name} (${s.id})`;
					li.addEventListener('click', () => {
					  window.location.href = 'student_profile_program_requirement_page.php?id=' + encodeURIComponent(s.id);
					});
					dropdown.appendChild(li);
				  });
				  dropdown.style.display = "block";
				}

				document.addEventListener('click', (e) => {
				  if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
					dropdown.style.display = "none";
				  }
				});

			</script>
	</body>
</html> 
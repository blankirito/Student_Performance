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

$stmt2 = $conn->prepare("
    SELECT Subject_Code AS course, Grade AS grade
    FROM student_detail__1_
    WHERE ID = ?
    ORDER BY Subject_Code
");
$stmt2->bind_param("s", $id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$courses = [];
while ($row = $result2->fetch_assoc()) {
    $courses[] = [
        "course" => $row["course"],
        "grade" => $row["grade"],
        "credit" => 2
    ];
}

$semesters = [];
if ($student) {
    $intakeRaw = $student['Intake']; 
    $date = DateTime::createFromFormat("M-y", $intakeRaw);
    if ($date) {
        $intakeMonth = $date->format("M"); 
        $intakeYear = $date->format("Y"); 
        $semesters = calculateGPA_CGPA($courses, $intakeMonth, $intakeYear);
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
		<style>

		</style>
	</head>

	<body>
		<div class="main-layout">
			<nav class="sidebar">
			  <ul>
				<li><a href="homepage.php">Homepage</a></li>
				<li><a href="student_management_page.php">Student Profile</a></li>
				<li><a href="program_leader_dashboard_academic_performance_page.php">Program Leader Dashboard</a></li>
				<li><a href="student_management_page.php">Student & Course</a></li>
			  </ul>
			</nav>
			
			<div class="main-content">
				<div class = "top-bar">
					<h1 class = "page-title">Student Profile</h1>
					
					<div class="search-wide">
						<input type="text" id="studentSearch" placeholder="Search by Name or ID...">
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
							<a href="student_profile_academic_status_page.php?id=<?php echo urlencode($id); ?>" class="active">Academic Status</a>
							<a href="student_profile_program_requirement_page.php?id=<?php echo urlencode($id); ?>">Program Requirement</a>
						</div>
						
						<div class="rounded-box content-box" style="display:flex; gap:20px; flex-wrap:wrap;">
							<div style="flex:3; min-width:500px;">
								CGPA & GPA Overview<br>
								<canvas id="gpaChart" width="1350" height="270"></canvas>
								<hr style="border: none; border-top: 2px solid #ccc; margin: 10px 0;">
								Subject Grade<br>
								<canvas id="subjectChart" width="1350" height="270"></canvas>
							</div>

							<div style="flex:1; min-width:250px; display:flex; flex-direction:column; gap:15px;">
								
								<div class="storytelling-card" style = "background-color: #FEFCE0">
									<h3>CGPA Story</h3>
									<p>
										<?php
										if ($student && !empty($semesters)) {
											$latestGPA = end($semesters)['gpa'];
											$latestCGPA = end($semesters)['cgpa'];
											$gpaStatus = $latestGPA >= 3 ? "above average" : ($latestGPA >= 2 ? "around average" : "below average");

											echo htmlspecialchars($student['Name']) . "'s latest GPA is " . number_format($latestGPA,2) . " (" . $gpaStatus . "). ";
											echo "Current CGPA: " . number_format($latestCGPA,2) . ". ";
											if ($latestGPA >= 3.5) {
												echo "Excellent performance this semester!";
											}
										} else {
											echo "No GPA/CGPA data available.";
										}
										?>
									</p>
								</div>

								<div class="storytelling-card" style = "background-color: #FEFCE0">
									<h3>Subject Story</h3>
									<p>
										<?php
										if (!empty($courses)) {
											$high = array_filter($courses, fn($c) => in_array(strtoupper($c['grade']), ['A+', 'A', 'A-']));
											$low = array_filter($courses, fn($c) => in_array(strtoupper($c['grade']), ['F','D','N']));

											if ($high) {
												echo "High scoring subjects: ";
												$highList = array_map(fn($c) => $c['course']." (".$c['grade'].")", $high);
												echo implode(", ", $highList) . ". ";
											}

											if ($low) {
												echo "Subjects needing attention: ";
												$lowList = array_map(fn($c) => $c['course']." (".$c['grade'].")", $low);
												echo implode(", ", $lowList) . ".";
											}

										} else {
											echo "No subject grades available.";
										}
										?>
									</p>
								</div>

							</div>

					</div>


					</div>
				</div>										
			</div>		
		</div>

		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
		<script>
			const semesterData = <?php echo json_encode($semesters); ?>;

			const labels = semesterData.map(s => s.semester);
			const gpaData = semesterData.map(s => s.gpa);
			const cgpaData = semesterData.map(s => s.cgpa);

			new Chart(document.getElementById("gpaChart"), {
				type: "line",
				data: {
					labels: labels,
					datasets: [
						{
							label: "GPA",
							data: gpaData,
							borderColor: "blue",
							fill: false,
							tension: 0.3
						},
						{
							label: "CGPA",
							data: cgpaData,
							borderColor: "green",
							fill: false,
							tension: 0.3
						}
					]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
					scales: {
						y: {
							min: 0,
							max: 4.0,
							ticks: {
								stepSize: 0.5
							}
						}
					}
				}
			});

			const subjectData = <?php echo json_encode($courses); ?>;

			const filteredSubjects = subjectData.filter(c => {
				return c.grade && c.grade.trim() !== "" &&
					   c.grade !== "N" &&
					   !c.grade.includes("Credit") &&
					   !c.grade.includes("Exempted");
			});

			const subjectLabels = filteredSubjects.map(c => c.course);  
			const subjectGrades = filteredSubjects.map(c => c.grade);  

			const gradeLevels = ["F", "D", "C", "C+", "B", "B+", "A-", "A", "A+"];

			const subjectPoints = subjectGrades.map(g => gradeLevels.indexOf(g));

			new Chart(document.getElementById("subjectChart"), {
				type: "bar",
				data: {
					labels: subjectLabels,
					datasets: [{
						label: "Grade",
						data: subjectPoints,
						backgroundColor: "rgba(54, 162, 235, 0.6)",
						borderColor: "rgba(54, 162, 235, 1)",
						borderWidth: 1
					}]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
					scales: {
						y: {
							min: 0,
							max: gradeLevels.length - 1,
							ticks: {
								stepSize: 1,
								callback: function(value) {
									return gradeLevels[value]; 
								}
							},
							title: {
								display: true,
								text: "Grade"
							}
						}
					}
				}
			});
			
			const searchInput = document.getElementById('studentSearch');
			const dropdown = document.getElementById('studentDropdown');
			let allStudents = [];

			fetch('search_students.php')
				.then(res => res.json())
				.then(data => {
					allStudents = data;
				});

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
					li.style.padding = "8px 12px";
					li.style.cursor = "pointer";
					li.style.borderBottom = "1px solid #eee";

					li.addEventListener('mouseenter', () => li.style.background = "#f0f0f0");
					li.addEventListener('mouseleave', () => li.style.background = "#fff");

					li.addEventListener('click', () => {
						window.location.href = 'student_profile_academic_status_page.php?id=' + encodeURIComponent(s.id);
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
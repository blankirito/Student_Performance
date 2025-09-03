<?php
include 'database.php';

$total_student = getTotalStudents();
$cgpaData = getAverageCGPAByIntake($conn);

$cgpaLabels = [];
$cgpaDataPoints = [];
foreach ($cgpaData as $item) {
    $cgpaLabels[] = $item['intake'];
    $cgpaDataPoints[] = round($item['avg_cgpa'], 2);
}

$statusCounts = getStudentStatusCounts($conn);
$statusLabels = ['Dean\'s List', 'Normal', 'Probation'];
$statusDataPoints = [
    $statusCounts['dean'] ?? 0,
    $statusCounts['normal'] ?? 0,
    $statusCounts['probation'] ?? 0,
];

$passFailCounts = getPassFailCounts($conn);
$passFailLabels = ['Pass', 'Fail'];
$passFailData = [$passFailCounts['pass'], $passFailCounts['fail']];

$topSubjects = getTop5SubjectsByAverage($conn);

$subjects = [];
$scores = [];
foreach ($topSubjects as $subject) {
    $subjects[] = $subject['Subject_Name'];
    $scores[] = round($subject['avg_score'], 2);
}

$students = $conn->query("SELECT DISTINCT ID, Name FROM student_detail__1_ ORDER BY Name");


$programs = $conn->query("SELECT DISTINCT Programme FROM student_detail__1_ ORDER BY Programme");

$programOptions = "<option value='' selected>All Programs</option>";

if ($programs->num_rows > 0) {
    while ($row = $programs->fetch_assoc()) {
        $programOptions .= "<option value='{$row['Programme']}'>{$row['Programme']}</option>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Homepage</title>
		<link rel = "stylesheet" href = "page.css">
		<meta charset="utf-8">
		<style>

		</style>
	</head>

	<body>		
		<div class = "main-layout">
			<nav class = "sidebar">
			  <ul>
				<li><a href = "homepage.php">Homepage</a></li>
				<li><a href = "student_profile_academic_status_page.php">Student Profile</a></li>
				<li><a href = "program_leader_dashboard_academic_performance_page.php">Program Leader Dashboard</a></li>
				<li><a href = "student_management_page.php">Student & Course</a></li>
			  </ul>
			</nav>
			
			<div class = "main-content">
				<div class = "top-bar">
					<h1 class = "page-title">Homepage</h1>
					
					<div class="search-wide">
						<input list="studentsList" id="searchInput" placeholder="Type student name or ID...">
						<datalist id="studentsList">
						<?php
						if ($students->num_rows > 0) {
							while ($s = $students->fetch_assoc()) {
								echo "<option value='{$s['ID']}'>{$s['Name']} ({$s['ID']})</option>";
							}
						}
						?>
						</datalist>


					</div>

					<div class = "top-controls">
						<div class = "rounded-box small-box">
							Total Student: <?php echo $total_student; ?>
						</div>
						
						<select id="programSelect" name="program">
							<?php echo $programOptions; ?>
						</select>


					</div>	
				</div>	

				
				<div class = "box-row">
				  <div class = "left-column">
					<div class = "rounded-box" style = "width: 100%; height: 350px; background-color: #D4E3FC">
						CGPA By Intake Year<br>
						<canvas id="cgpaChart" width="900" height="300"></canvas>
					</div>
					<div class = "rounded-box" style="width: 100%; height: 170px; display: flex; align-items: flex-start; gap: 20px; background-color: #FEFAC0">
						<div style="flex: 2;">
							<div style="display: flex; justify-content: space-between; align-items: center;">
								<span>Average Grade</span>
								<select id="gradeFilter" class="small-select" style="font-size:12px; padding:0 4px; height:22px; line-height:18px;">
									<option value="highest" selected>Top 5 Highest</option>
									<option value="lowest">Top 5 Lowest</option>
								</select>
							</div>
							<canvas id="topSubjectsChart" width="400" height="55" style="margin-top: 10px;"></canvas>
						</div>
					</div>

				  </div>

				  <div class = "right-column">
					<div class="rounded-box" style="width: 100%; height: 260px; display: flex; align-items: flex-start; gap: 20px;background-color: #FEFAC0">
					  <div style="flex: 2;">
						Dean's List/Probation/Normal<br>
						  <select id="subjectSelect" class = "small-select" style="font-size:12px; padding:0 4px; height:22px; line-height:18px;">
							<option value="">All Subjects</option>
							<?php
							$subjects = getAllSubjects($conn);
							foreach ($subjects as $sub) {
								echo "<option value='$sub'>$sub</option>";
							}
							?>
						</select>

						<select id="intakeSelect" class = "small-select" style="font-size:12px; padding:0 4px; height:22px; line-height:18px;">
							<option value="">All Intakes</option>
							<?php
							$intakes = getAllIntakes($conn);
							foreach ($intakes as $i) {
								echo "<option value='$i'>$i</option>";
							}
							?>
						</select>
						<canvas id="statusChart" width="200" height="130"></canvas>
					  </div>
					  <div style="flex: 1; font-size: 14px; line-height: 1.8; margin-top: 40px;">
						<strong>Summary:</strong><br>
						Dean's List: <span id="deanCount">0</span> students<br>
						Normal: <span id="normalCount">0</span> students<br>
						Probation: <span id="probationCount">0</span> students
					  </div>
					</div>

					<div class="rounded-box" style="width: 100%; height: 260px; display: flex; align-items: flex-start; gap: 20px; margin-top: 20px;background-color: #FEFAC0">
					  <div style="flex: 2;">
						Average Pass/Fail<br>
						<select id="pfSubjectSelect" class = "small-select" style="font-size:12px; padding:0 4px; height:22px; line-height:18px;">
							<option value="">All Subjects</option>
							<?php
							$subjects = getAllSubjects($conn);
							foreach ($subjects as $sub) {
								echo "<option value='$sub'>$sub</option>";
							}
							?>
						</select>

						<select id="pfIntakeSelect" class = "small-select" style="font-size:12px; padding:0 4px; height:22px; line-height:18px;">
							<option value="">All Intakes</option>
							<?php
							$intakes = getAllIntakes($conn);
							foreach ($intakes as $i) {
								echo "<option value='$i'>$i</option>";
							}
							?>
						</select>
						<canvas id="passFailChart" width="300" height="170" style = "margin-top: 20px;"></canvas>
					  </div>
					  <div style="flex: 1; font-size: 14px; line-height: 1.8; margin-top: 40px;">
						<strong>Summary:</strong><br>
						Pass: <?php echo $passFailCounts['pass'] ?? 0; ?> subjects<br>
						Fail: <?php echo $passFailCounts['fail'] ?? 0; ?> subjects
					  </div>
					</div>

				  </div>
				</div>
				
				<div class = "rounded-box" style = "width: 100%; height: 130px; background-color: #FFDAD8;">
					Note:<br>
					<ul id="noteList"></ul>
				</div>

			</div>	
			
		</div>
		
		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

		<script>
		const ctx1 = document.getElementById('cgpaChart').getContext('2d');
		const cgpaChart = new Chart(ctx1, {
			type: 'line',
			data: {
				labels: <?php echo json_encode($cgpaLabels); ?>,
				datasets: [{
					label: 'Average CGPA by Intake',
					data: <?php echo json_encode($cgpaDataPoints); ?>,
					borderColor: 'blue',
					fill: false,
					tension: 0.2,
					pointRadius: 5,
				}]
			},
			options: {
				scales: {
					y: { min: 0, max: 4, ticks: { stepSize: 0.5 } }
				}
			}
		});

		const ctx2 = document.getElementById('statusChart').getContext('2d');
		const statusChart = new Chart(ctx2, {
			type: 'bar',
			data: {
				labels: ["Dean's List", "Normal", "Probation"],
				datasets: [{
					label: 'Number of Students',
					data: [0, 0, 0],
					backgroundColor: ['#4caf50', '#2196f3', '#f44336']
				}]
			},
			options: {
				scales: { y: { beginAtZero: true, stepSize: 1 } }
			}
		});

		function updateStatusChart() {
			const subject = document.getElementById('subjectSelect').value;
			const intake = document.getElementById('intakeSelect').value;

			fetch(`homepage_data.php?subject=${encodeURIComponent(subject)}&intake=${encodeURIComponent(intake)}`)
				.then(res => res.json())
				.then(data => {
					statusChart.data.datasets[0].data = data.statusDataPoints;
					statusChart.update();

					document.getElementById('deanCount').textContent = data.statusDataPoints[0];
					document.getElementById('normalCount').textContent = data.statusDataPoints[1];
					document.getElementById('probationCount').textContent = data.statusDataPoints[2];
				});
		}

		document.getElementById('subjectSelect').addEventListener('change', updateStatusChart);
		document.getElementById('intakeSelect').addEventListener('change', updateStatusChart);

		updateStatusChart();

		const ctxPassFail = document.getElementById('passFailChart').getContext('2d');
		const passFailChart = new Chart(ctxPassFail, {
			type: 'pie',
			data: {
				labels: ['Pass', 'Fail'],
				datasets: [{
					data: [0, 0],
					backgroundColor: ['#4caf50', '#f44336']
				}]
			},
			options: {
				responsive: false,
				maintainAspectRatio: false,
				plugins: { legend: { position: 'bottom' } }
			}
		});

		function updatePassFailChart() {
			const program = document.getElementById('programSelect').value;
			const subject = document.getElementById('pfSubjectSelect').value;
			const intake  = document.getElementById('pfIntakeSelect').value;

			fetch(`homepage_data.php?program=${encodeURIComponent(program)}&subject=${encodeURIComponent(subject)}&intake=${encodeURIComponent(intake)}`)
				.then(res => res.json())
				.then(data => {
					passFailChart.data.datasets[0].data = data.passFailData;
					passFailChart.update();
				});
		}

		document.getElementById('pfSubjectSelect').addEventListener('change', updatePassFailChart);
		document.getElementById('pfIntakeSelect').addEventListener('change', updatePassFailChart);
		document.getElementById('programSelect').addEventListener('change', updatePassFailChart);

		updatePassFailChart();


	
		const gradeFilter = document.getElementById('gradeFilter');

		gradeFilter.addEventListener('change', function() {
			updateCharts(currentProgram, this.value);
		});

		const ctxTop = document.getElementById('topSubjectsChart').getContext('2d');
		const topSubjectsChart = new Chart(ctxTop, {
			type: 'bar',
			data: {
				labels: <?php echo json_encode($subjects); ?>,
				datasets: [{
					label: 'Average Score (%)',
					data: <?php echo json_encode($scores); ?>,
					backgroundColor: ['#ff9800', '#f57c00', '#e65100', '#ffb74d', '#ffe0b2']
				}]
			},
			options: {
				responsive: true,
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: function(context) { return context.parsed.y + '%'; }
						}
					}
				},
				scales: { y: { beginAtZero: true, max: 100 } }
			}
		});

		const searchInput = document.getElementById('searchInput');
		searchInput.addEventListener('change', function() {
			if (this.value) {
				window.location.href = `student_profile_academic_status_page.php?id=${this.value}`;
			}
		});

		let currentProgram = '';
		document.getElementById('programSelect').addEventListener('change', function() {
			currentProgram = this.value;
			updateCharts(currentProgram, document.getElementById('gradeFilter').value);
		});

		updateCharts(currentProgram, document.getElementById('gradeFilter').value);

		function updateCharts(program='', filter='highest') {
			fetch(`homepage_data.php?program=${encodeURIComponent(program)}&filter=${encodeURIComponent(filter)}`)
				.then(res => res.json())
				.then(data => {
					cgpaChart.data.labels = data.cgpaLabels;
					cgpaChart.data.datasets[0].data = data.cgpaDataPoints;
					cgpaChart.update();

					statusChart.data.datasets[0].data = data.statusDataPoints;
					statusChart.update();

					passFailChart.data.datasets[0].data = data.passFailData;
					passFailChart.update();

					topSubjectsChart.data.labels = data.topSubjects;
					topSubjectsChart.data.datasets[0].data = data.topScores;
					topSubjectsChart.update();

					document.querySelector('.small-box').textContent = `Total Student: ${data.totalStudent}`;

					const noteDiv = document.querySelector('.rounded-box[style*="height: 130px"]');
					if (data.alerts && data.alerts.length > 0) {
						const filteredAlerts = data.alerts.filter(alert => !alert.includes('Grade N') && !alert.includes('Grade CT'));
						const firstFour = filteredAlerts.slice(0, 4);
						noteDiv.innerHTML = '<strong>Note:</strong><br>' + firstFour.join('<br>');
					} else {
						noteDiv.innerHTML = '<strong>Note:</strong><br>All students are performing normally.';
					}


				})
				.catch(err => console.error(err));
		}


		document.getElementById('gradeFilter').addEventListener('change', function() {
			updateCharts(currentProgram, this.value);
		});


		</script>


	</body>
</html>

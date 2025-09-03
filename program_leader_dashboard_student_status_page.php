<?php
include 'database.php';

$statusCounts = getStudentStatusCounts($conn);
$statusLabels = ["Dean's List", "Normal", "Probation"];
$statusDataPoints = [
    $statusCounts['dean'] ?? 0,
    $statusCounts['normal'] ?? 0,
    $statusCounts['probation'] ?? 0
];

$total_student = getTotalStudents();


$passFailCounts = getPassFailCounts($conn);
$passFailLabels = ['Pass', 'Fail'];
$passFailData = [$passFailCounts['pass'], $passFailCounts['fail']];

$cgpaData = getAverageCGPAByIntake($conn);

$topStudents = getTopStudentsByCGPA($conn);
$lowestStudents = getLowestStudentsByGPA($conn, 5);

$programs = $conn->query("SELECT DISTINCT Programme FROM student_detail__1_ ORDER BY Programme");
$programOptions = "<option value='' selected>All Programs</option>";
if ($programs->num_rows > 0) {
    while ($row = $programs->fetch_assoc()) {
        $programOptions .= "<option value='{$row['Programme']}'>{$row['Programme']}</option>";
    }
}


$intakes = $conn->query("SELECT DISTINCT Intake FROM student_detail__1_ ORDER BY Intake");
$intakeOptions = "<option value='' selected>All Intakes</option>";
if ($intakes->num_rows > 0) {
    while ($row = $intakes->fetch_assoc()) {
        $intakeOptions .= "<option value='{$row['Intake']}'>{$row['Intake']}</option>";
    }
}

$academicData1 = getAcademicPerformanceChart1($conn);
$academicData2 = getAcademicPerformanceChart2($conn);
$studentStatusData = getStudentStatusCounts($conn);
$allStudents = getAllStudents($conn);

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Program Leader Dashboard</title>
		<link rel = "stylesheet" href = "page.css">
		<meta charset="utf-8">
		<style>

		</style>
	</head>

	<body id = "studentStatusPage">
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
				<div class = "top-bar-pl">
					<h1 class = "page-title-pl">Program Leader Dashboard</h1>
					
					
					<div class="top-controls-pl">
						<select id="program" name="program">
							<?php echo $programOptions; ?>
						</select>

						<select id="intake" name="intake">
							<?php echo $intakeOptions; ?>
						</select>
					</div>
	
				</div>	
				
				<div class="content-wrapper">
					<div class = "inner-wrapper">
						<div class="nav-bar-with-btn">
							<div class="nav-links">
								<a href="program_leader_dashboard_academic_performance_page.php">Academic Performance</a>
								<a href="program_leader_dashboard_student_status_page.php" class="active">Student Status</a>
							</div>
							<button class="report-btn">Generate Report</button>
						</div>
						
						<div class="rounded-box content-box-pl">
							<div class = "box-row">
							  <div class = "left-column">
								<div class="rounded-box" style="width:100%; height:310px; display:flex; gap:20px; padding:10px;">
								  <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
									Dean's List / Probation / Normal
									<canvas id="statusPieChart" width="300" height="200"></canvas>
								  </div>
								  
								  <div style="flex:1; display:flex; flex-direction:column; gap:10px; background-color: #FEFCE0">
									<div style="display:flex; gap:10px;">
									  <select id="subjectSelect">
										<option value="" selected>All Subjects</option>
										<?php
										  $subjects = $conn->query("SELECT DISTINCT Subject_Name FROM student_detail__1_ ORDER BY Subject_Name");
										  while($row = $subjects->fetch_assoc()){
											  echo "<option value='{$row['Subject_Name']}'>{$row['Subject_Name']}</option>";
										  }
										?>
									  </select>

									  <select id="listSelect">
										<option value="" selected>All</option>
										<option value="Dean's List">Dean's List</option>
										<option value="Normal">Normal</option>
										<option value="Probation">Probation</option>
									  </select>
									</div>

									<div id="listStudentCards" style="flex:1; overflow-y:auto; border:1px solid #ccc; border-radius:8px; padding:5px;">
									</div>
								  </div>
								</div>
								
								<div class="rounded-box" style="width: 100%; height: 310px; display:flex; gap:20px; padding:10px;">
								  <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
									Pass / Fail
									<canvas id="passFailPieChart" style="width:100%; height:100%;"></canvas>
								  </div>
								  <div style="flex:1; display:flex; flex-direction:column; gap:10px; background-color: #FEFCE0">
									<div style="display:flex; gap:20px;">
									  <select id="passFailSubjectSelect">
										<option value="" selected>All Subjects</option>
										<?php
										  $subjects = $conn->query("SELECT DISTINCT Subject_Name FROM student_detail__1_ ORDER BY Subject_Name");
										  while($row = $subjects->fetch_assoc()){
											  echo "<option value='{$row['Subject_Name']}'>{$row['Subject_Name']}</option>";
										  }
										?>
									  </select>

									  <select id="passFailStatusSelect">
										<option value="" selected>All</option>
										<option value="Pass">Pass</option>
										<option value="Fail">Fail</option>
									  </select>
									</div>

									<div id="passFailStudentCards" style="flex:1; overflow-y:auto; border:1px solid #ccc; border-radius:8px; padding:5px;">
									</div>
								  </div>
								</div>


							  </div>

							  <div class = "right-column">
								<div class = "rounded-box" style = "width: 100%; height: 70px; background-color: #D4E3FC">
									Total Student: <?php echo $total_student; ?>
								</div>
								
								<div class="rounded-box" style="width: 100%; height: 250px; background-color: #A6DBA0;">
									<strong>Highest CGPA Students</strong>
									<div id="studentCardsContainer" style="max-height: 180px; overflow-y: auto;"></div>
									<label for="studentCount">Number of students to display:</label>
									<input type="range" id="studentCount" min="1" max="20" value="5" step="1">
								</div>	
								
								<div class = "rounded-box" style = "width: 100%; height: 250px; background-color:#FF8A84">
									<strong>Lowest CGPA Student</strong>
									<div id="lowestStudentCardsContainer" style="max-height: 180px; overflow-y: auto;"></div>
									<label for="lowestStudentCount">Number of students to display:</label>
									<input type="range" id="lowestStudentCount" min="1" max="20" value="5" step = "1">
								</div>
							  </div>
							</div>
						</div>
					</div>
				</div>									
			</div>		
		</div>

		<div id="academicCharts" style="position:absolute; left:-9999px; top:-9999px;">
			<canvas id="gpaChart" width="600" height="400"></canvas>
			<canvas id="subjectChart" width="600" height="400"></canvas>
		</div>
		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

		<script>
		const ctx = document.getElementById('statusPieChart').getContext('2d');
		const ctxPassFail = document.getElementById('passFailPieChart').getContext('2d');

		let statusLabels = <?php echo json_encode($statusLabels); ?>;
		let statusDataPoints = <?php echo json_encode($statusDataPoints); ?>;
		let passFailLabels = <?php echo json_encode($passFailLabels); ?>;
		let passFailData = <?php echo json_encode($passFailData); ?>;
		let topStudents = <?php echo json_encode($topStudents); ?>;
		let lowestStudents = <?php echo json_encode($lowestStudents); ?>;
		let allStudents = <?php echo json_encode(getAllStudents($conn)); ?>;

		const statusPieChart = new Chart(ctx, {
			type: 'pie',
			data: {
				labels: statusLabels,
				datasets: [{
					data: statusDataPoints,
					backgroundColor: ['#4caf50', '#2196f3', '#f44336'],
					borderColor: '#fff',
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				plugins: {
					legend: { position: 'bottom' },
					tooltip: {
						callbacks: {
							label: function(context) {
								return context.label + ': ' + context.raw + ' students';
							}
						}
					},
					title: { display: true, text: "Student Status Distribution" }
				}
			}
		});

		const passFailPieChart = new Chart(ctxPassFail, {
			type: 'pie',
			data: {
				labels: passFailLabels,
				datasets: [{
					data: passFailData,
					backgroundColor: ['#4caf50', '#f44336'],
					borderColor: '#fff',
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { position: 'bottom' },
					tooltip: {
						callbacks: {
							label: function(context) {
								return context.label + ': ' + context.raw + ' students';
							}
						}
					},
					title: { display: true, text: "Pass / Fail Distribution" }
				}
			}
		});

		const studentCardsContainer = document.getElementById('studentCardsContainer');
		const studentCountSlider = document.getElementById('studentCount');
		function renderStudents(count, array = topStudents) {
			studentCardsContainer.innerHTML = '';
			for (let i = 0; i < count && i < array.length; i++) {
				const s = array[i];
				const card = document.createElement('div');
				card.classList.add('student-card');
				card.innerHTML = `<strong>${s.id} | ${s.Name}</strong><br>Programme: ${s.Programme}<br>CGPA: ${s.cgpa.toFixed(2)}`;
				const link = document.createElement('a');
				link.href = `student_profile_academic_status_page.php?id=${s.id}`;
				link.classList.add('student-card-link');
				link.style.textDecoration = 'none';
				link.appendChild(card);
				studentCardsContainer.appendChild(link);
			}
		}
		renderStudents(parseInt(studentCountSlider.value));
		studentCountSlider.addEventListener('input', () => {
			renderStudents(parseInt(studentCountSlider.value));
		});

		const lowestCardsContainer = document.getElementById('lowestStudentCardsContainer');
		const lowestSlider = document.getElementById('lowestStudentCount');
		function renderLowestStudents(count, array = lowestStudents) {
			lowestCardsContainer.innerHTML = '';
			for (let i = 0; i < count && i < array.length; i++) {
				const s = array[i];
				if (!s || s.gpa === undefined) continue;
				const card = document.createElement('div');
				card.classList.add('student-card');
				card.innerHTML = `<strong>${s.ID} | ${s.Name}</strong><br>Programme: ${s.Programme}<br>CGPA: ${s.gpa.toFixed(2)}`;
				const link = document.createElement('a');
				link.href = `student_profile_academic_status_page.php?id=${s.ID}`;
				link.classList.add('student-card-link');
				link.appendChild(card);
				link.style.textDecoration = 'none';
				lowestCardsContainer.appendChild(link);
			}
		}
		renderLowestStudents(parseInt(lowestSlider.value));
		lowestSlider.addEventListener('input', () => {
			renderLowestStudents(parseInt(lowestSlider.value));
		});

		function updateCharts(program = '', intake = '') {
			const filtered = allStudents.filter(s =>
				(program === '' || s.Programme === program) &&
				(intake === '' || s.Intake === intake)
			);

			const dean = filtered.filter(s => s.Status === "Dean's List").length;
			const normal = filtered.filter(s => s.Status === "Normal").length;
			const probation = filtered.filter(s => s.Status === "Probation").length;
			statusPieChart.data.datasets[0].data = [dean, normal, probation];
			statusPieChart.update();

			const pass = filtered.filter(s => s.PassFail === "Pass").length;
			const fail = filtered.filter(s => s.PassFail === "Fail").length;
			passFailPieChart.data.datasets[0].data = [pass, fail];
			passFailPieChart.update();
		}

		document.getElementById('program').addEventListener('change', () => {
			const program = document.getElementById('program').value;
			const intake = document.getElementById('intake').value;

			updateCharts(program, intake);

			const filteredTop = topStudents.filter(s => program === '' || s.Programme === program);
			renderStudents(parseInt(studentCountSlider.value), filteredTop);

			const filteredLowest = lowestStudents.filter(s => program === '' || s.Programme === program);
			renderLowestStudents(parseInt(lowestSlider.value), filteredLowest);
		});

		document.getElementById('intake').addEventListener('change', () => {
			const program = document.getElementById('program').value;
			const intake = document.getElementById('intake').value;
			updateCharts(program, intake);
		});
		
		function renderListStudents() {
		  const selectedProgram = document.getElementById('program').value;
		  const selectedIntake = document.getElementById('intake').value;
		  const selectedSubject = document.getElementById('subjectSelect').value;
		  const selectedList = document.getElementById('listSelect').value;

		  const filtered = allStudents.filter(s =>
			(selectedProgram === '' || s.Programme === selectedProgram) &&
			(selectedIntake === '' || s.Intake === selectedIntake) &&
			(selectedSubject === '' || s.Subject_Name === selectedSubject) &&
			(selectedList === '' || s.Status === selectedList)
		  );

		  const container = document.getElementById('listStudentCards');
		  container.innerHTML = '';

		  filtered.forEach(s => {
			const card = document.createElement('div');
			card.classList.add('student-card');
			card.innerHTML = `
			  <strong>${s.ID} | ${s.Name}</strong><br>
			  Programme: ${s.Programme}<br>
			  Intake: ${s.Intake}<br>
			  Subject: ${s.Subject_Name}<br>
			  Status: ${s.Status}<br>
			  Pass/Fail: ${s.PassFail}
			`;
			const link = document.createElement('a');
			link.href = `student_profile_academic_status_page.php?id=${s.ID}`;
			link.appendChild(card);
			container.appendChild(link);
			link.style.textDecoration = 'none';
		  });
		}

		document.getElementById('subjectSelect').addEventListener('change', renderListStudents);
		document.getElementById('listSelect').addEventListener('change', renderListStudents);
		document.getElementById('program').addEventListener('change', renderListStudents);
		document.getElementById('intake').addEventListener('change', renderListStudents);

		renderListStudents();

		const passFailContainer = document.getElementById('passFailStudentCards');

		function renderPassFailStudents() {
		  const selectedProgram = document.getElementById('program').value;
		  const selectedIntake = document.getElementById('intake').value;
		  const selectedSubject = document.getElementById('passFailSubjectSelect').value;
		  const selectedStatus = document.getElementById('passFailStatusSelect').value;

		  const filtered = allStudents.filter(s =>
			(selectedProgram === '' || s.Programme === selectedProgram) &&
			(selectedIntake === '' || s.Intake === selectedIntake) &&
			(selectedSubject === '' || s.Subject_Name === selectedSubject) &&
			(selectedStatus === '' || s.PassFail === selectedStatus)
		  );

		  passFailContainer.innerHTML = '';

		  filtered.forEach(s => {
			const card = document.createElement('div');
			card.classList.add('student-card');
			card.innerHTML = `
			  <strong>${s.ID} | ${s.Name}</strong><br>
			  Programme: ${s.Programme}<br>
			  Intake: ${s.Intake}<br>
			  Subject: ${s.Subject_Name}<br>
			  Status: ${s.Status}<br>
			  Pass/Fail: ${s.PassFail}
			`;
			const link = document.createElement('a');
			link.href = `student_profile_academic_status_page.php?id=${s.ID}`;
			link.style.textDecoration = 'none';
			link.appendChild(card);
			passFailContainer.appendChild(link);
		  });
		}

		document.getElementById('program').addEventListener('change', renderPassFailStudents);
		document.getElementById('intake').addEventListener('change', renderPassFailStudents);
		document.getElementById('passFailSubjectSelect').addEventListener('change', renderPassFailStudents);
		document.getElementById('passFailStatusSelect').addEventListener('change', renderPassFailStudents);

		renderPassFailStudents();

		document.querySelector('.report-btn').addEventListener('click', async () => {
			const { jsPDF } = window.jspdf;
			const pdf = new jsPDF('p', 'mm', 'a4'); 
			
			const chartContainer = document.querySelector('.content-box-pl');
			
			const canvas = await html2canvas(chartContainer, { scale: 2 });
			
			const imgData = canvas.toDataURL('image/png');
			
			const pdfWidth = 210;
			const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
			
			pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
			
			pdf.save('academic_report.pdf');
		});


		</script>
		
		
	</body>
</html> 
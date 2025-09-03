<?php
include 'database.php';
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
				<div class = "top-bar-pl">
					<h1 class = "page-title-pl">Program Leader Dashboard</h1>
					
					<div class = "top-controls-pl">						
						<select id="programSelect" name="program">
						  <option value="" selected>Select Program</option>
						  <?php
							$programs = $conn->query("SELECT DISTINCT Programme FROM student_detail__1_ ORDER BY Programme");
							while($row = $programs->fetch_assoc()){
								echo "<option value='{$row['Programme']}'>{$row['Programme']}</option>";
							}
						  ?>
						</select>

					</div>	
				</div>	
				
				<div class="content-wrapper">
					<div class = "inner-wrapper">
						<div class="nav-bar-with-btn">
							<div class="nav-links">
								<a href="program_leader_dashboard_academic_performance_page.php" class="active">Academic Performance</a>
								<a href="program_leader_dashboard_student_status_page.php">Student Status</a>
							</div>
							<button class="report-btn">Generate Report</button>
						</div>
						<div class="rounded-box content-box-pl">
							<div class = "chart-wrapper">
								Average CGPA & GPA<br>
								<canvas id="gpaChart" style="width: 1100px; height: 300px;"></canvas>
							</div>

							<hr style="border: none; border-top: 2px solid #ccc; margin: 10px 0;">
							
							<div class = "class-wrapper">
								Subject Grade<br>
								<canvas id="subjectChart" style="width:1100px; height:300px;"></canvas>
							</div>
							
						</div>
					</div>
				</div>									
			</div>		
		</div>
		
		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

		<script>
		let gpaChart, subjectChart;

		function initCharts() {
			const gpaCtx = document.getElementById('gpaChart').getContext('2d');
			gpaChart = new Chart(gpaCtx, {
				type: 'line',
				data: { labels: [], datasets: [
					{ label: 'Average GPA', data: [], borderColor: 'blue', fill: false, tension: 0.2 },
					{ label: 'Average CGPA', data: [], borderColor: 'green', fill: false, tension: 0.2 }
				]},
				options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 4 } } }
			});

			const subjectCtx = document.getElementById('subjectChart').getContext('2d');
			subjectChart = new Chart(subjectCtx, {
				type: 'bar',
				data: { labels: [], datasets: [{ label: 'Grade', data: [], backgroundColor: 'rgba(54,162,235,0.6)', borderColor: 'rgba(54,162,235,1)', borderWidth: 1 }]},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							max: 11,
							ticks: {
								stepSize: 1,
								callback: v => ['A+','A','A-','B+','B','B-','C+','C','C-','D','F'][v-1] || ''
							},
							title: { display: true, text: 'Grade' }
						},
						x: { title: { display: true, text: 'Subjects' } }
					},
					plugins: {
						legend: { display: false },
						title: { display: true, text: 'Subjects Grades', font: { size: 16 } },
						tooltip: {
							callbacks: {
								label: function(context) {
									const numericValue = context.raw;
									const gradeLetter = ['A+','A','A-','B+','B','B-','C+','C','C-','D','F'][numericValue - 1] || '';
									return 'Grade: ' + gradeLetter;
								}
							}
						}
					}
				}
			});
		}

		async function updateCharts(program = '') {
			try {
				const gpaRes = await fetch('get_avg_gpa.php?program=' + encodeURIComponent(program));
				const gpaData = await gpaRes.json();

				const labels = gpaData.map(d => d.intake);
				const avgGPA = gpaData.map(d => d.avgGPA);
				const avgCGPA = gpaData.map(d => d.avgCGPA);

				gpaChart.data.labels = labels;
				gpaChart.data.datasets[0].data = avgGPA;
				gpaChart.data.datasets[1].data = avgCGPA;
				gpaChart.update();

				const subjectRes = await fetch('get_avg_subject_grade.php?program=' + encodeURIComponent(program));
				const subjectData = await subjectRes.json();

				const gradeOrder = ['A+','A','A-','B+','B','B-','C+','C','C-','D','F'];
				const numericData = subjectData.grades.map(g => gradeOrder.indexOf(g) + 1);

				subjectChart.data.labels = subjectData.subjects;
				subjectChart.data.datasets[0].data = numericData;
				subjectChart.update();
			} catch (err) {
				console.error(err);
			}
		}

		document.getElementById('programSelect').addEventListener('change', function() {
			const program = this.value;
			updateCharts(program);
		});

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

		initCharts();
		updateCharts(); 
		</script>

	</body>
</html> 
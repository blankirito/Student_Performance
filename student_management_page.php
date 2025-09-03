<?php
include 'database.php';

$students = [];
$result = $conn->query("SELECT ID, Name, Programme, Grade, Intake FROM student_detail__1_ ORDER BY Name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['ID'];
        if (!isset($students[$id])) {
            $students[$id] = [
                'ID' => $row['ID'],
                'Name' => $row['Name'],
                'Programme' => $row['Programme'],
				'Intake' => $row['Intake'],
                'status' => 'inactive',
            ];
        }
        if (strtoupper(trim($row['Grade'])) === 'N' || strtoupper(trim($row['Grade'])) === 'F') {
            $students[$id]['status'] = 'active';
        }
    }
}
$students = array_values($students);

$programmeSql = "SELECT DISTINCT Programme FROM student_detail__1_ ORDER BY Programme ASC";
$programmeRes = $conn->query($programmeSql);
$programmes = [];
if ($programmeRes && $programmeRes->num_rows > 0) {
    while ($row = $programmeRes->fetch_assoc()) {
        $programmes[] = $row['Programme'];
    }
}

$intakes = [];
$intakeSql = "SELECT DISTINCT Intake FROM student_detail__1_ ORDER BY Intake ASC";
$intakeRes = $conn->query($intakeSql);
if ($intakeRes && $intakeRes->num_rows > 0) {
    while ($row = $intakeRes->fetch_assoc()) {
        $intakes[] = $row['Intake'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Student & Course</title>
		<link rel = "stylesheet" href = "page.css">
		<meta charset="utf-8">
		<style>
			.student-card[data-status="inactive"] {
				background-color: #f8d7da; 
				border: 1px solid #f5c2c7;
				color: #842029;
			}

			.student-card[data-status="active"] {
				background-color: #d1e7dd;
				border: 1px solid #badbcc;
				color: #0f5132;
			}

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
				<div class = "top-bar-pl">
					<h1 class = "page-title-pl">Student Management</h1>
					
					<div class="search-wide-pl">
					  <input type="text" placeholder="Type to search...">
					</div>
					
					<div class = "top-controls-pl">
						<select id="programmeFilter">
							<option value="">Select Programme</option>
							<?php foreach ($programmes as $programme): ?>
								<option value="<?php echo htmlspecialchars($programme); ?>"><?php echo htmlspecialchars($programme); ?></option>
							<?php endforeach; ?>
						</select>
						
						<select id="semesterFilter">
							<option value="">Select Semester</option>
							<?php foreach($intakes as $intake): ?>
								<option value="<?php echo strtolower($intake); ?>"><?php echo htmlspecialchars($intake); ?></option>
							<?php endforeach; ?>
						</select>
					</div>	
				</div>	
				
				<div class="content-wrapper">
					<div class = "inner-wrapper">
						<div class="nav-links">
							<a href="student_management_page.php" class="active">Student Management</a>
							<a href="course_management_page.php">Course Management</a>
						</div>
							
						<div class="rounded-box content-box-sc">
							<div class = "top-actions">
								<button class="action-btn add">Add Student</button>
								<button class="action-btn edit">Edit/Delete Student</button>
							</div>
							
							<div class="status-filters">
								<label><input type="radio" name="status" value="all" checked> All</label>
								<label><input type="radio" name="status" value="active"> Active</label>
								<label><input type="radio" name="status" value="inactive"> Inactive</label>
							</div>
							
							<div class="student-cards">
								<?php if (!empty($students)): ?>
									<?php foreach ($students as $student): ?>
										<a class="student-card" href="student_profile_academic_status_page.php?id=<?php echo urlencode($student['ID']); ?>" 
										   data-status="<?php echo $student['status']; ?>"
										   data-programme="<?php echo strtolower($student['Programme']); ?>"
										   data-semester="<?php echo strtolower($student['Intake']); ?>">
											<div class="student-name"><?php echo htmlspecialchars($student['Name']); ?></div>
											<div class="student-info">
												ID: <?php echo htmlspecialchars($student['ID']); ?><br>
												Programme: <?php echo htmlspecialchars($student['Programme']); ?><br>
												Intake: <?php echo htmlspecialchars($student['Intake']); ?><br>
												Status: <?php echo $student['status']; ?>
											</div>
										</a>

									<?php endforeach; ?>
								<?php else: ?>
									<p>No students found.</p>
								<?php endif; ?>
							</div>

						</div>
					</div>
				</div>									
			</div>		
		</div>
		
		<div id="addStudentModal" class="modal" style="display:none;">
			<div class="modal-content">
				<h2>Add Student</h2>

				<div class="modal-tabs">
					<button type="button" class="tab-btn active" data-tab="manual">Manual Add</button>
					<button type="button" class="tab-btn" data-tab="csv">Import CSV</button>
				</div>

				<div class="tab-content" data-tab="manual" style="display:block;">
					<form id="addStudentForm">
						<label>Student ID:</label>
						<input type="text" name="id" required>

						<label>Name:</label>
						<input type="text" name="name" required>

						<label>Programme:</label>
						<select name="programme" required>
							<?php foreach ($programmes as $programme): ?>
								<option value="<?php echo htmlspecialchars($programme); ?>"><?php echo htmlspecialchars($programme); ?></option>
							<?php endforeach; ?>
						</select>

						<label>Intake Date:</label>
						<input type="date" name="intake" required>

						<div class="modal-buttons">
							<button type="button" id="cancelBtn">Cancel</button>
							<button type="submit">Save</button>
						</div>
					</form>
				</div>

				<div class="tab-content" data-tab="csv" style="display:none;">
					<form id="importCSVForm" enctype="multipart/form-data">
						<label>Select CSV File:</label>
						<input type="file" name="csv_file" accept=".csv" required>

						<div class="modal-buttons">
							<button type="button" id="cancelBtnCSV">Cancel</button>
							<button type="submit">Upload</button>
						</div>
					</form>
				</div>

			</div>
		</div>
		
		<div id="editStudentModal" class="modal" style="display:none;">
		  <div class="modal-content">
			<h2>Edit Student</h2>
			<form id="editStudentForm">
			  <label>ID:</label>
			  <input type="text" name="id" readonly> 
			  
			  <label>Name:</label>
			  <input type="text" name="name" required>

			  <label>Programme:</label>
			  <select name="programme" required>
				<?php foreach ($programmes as $programme): ?>
				  <option value="<?php echo htmlspecialchars($programme); ?>"><?php echo htmlspecialchars($programme); ?></option>
				<?php endforeach; ?>
			  </select>

			  <label>Intake Date:</label>
			  <input type="date" name="intake" required>

			  <div class="modal-buttons">
				<button type="button" id="deleteStudentBtn" style="background:red;color:white;">Delete</button>
				<button type="button" id="cancelEditBtn">Cancel</button>
				<button type="submit">Save</button>
			  </div>
			</form>
		  </div>
		</div>



		<script>
		const modal = document.getElementById("addStudentModal");
		const addBtn = document.querySelector(".action-btn.add");
		const cancelBtns = document.querySelectorAll("#cancelBtn, #cancelBtnCSV");
		const tabBtns = document.querySelectorAll(".tab-btn");
		const tabContents = document.querySelectorAll(".tab-content");
		const addForm = document.getElementById("addStudentForm");
		const csvForm = document.getElementById("importCSVForm");

		addBtn.addEventListener("click", () => {
			modal.style.display = "flex";
		});

		cancelBtns.forEach(btn => {
			btn.addEventListener("click", () => {
				modal.style.display = "none";
			});
		});

		tabBtns.forEach(btn => {
			btn.addEventListener("click", () => {
				tabBtns.forEach(b => b.classList.remove("active"));
				tabContents.forEach(c => c.style.display = "none");

				btn.classList.add("active");
				document.querySelector(`.tab-content[data-tab="${btn.dataset.tab}"]`).style.display = "block";
			});
		});

		addForm.addEventListener("submit", e => {
			e.preventDefault();
			let formData = new FormData(addForm);
			fetch("add_student.php", {
				method: "POST",
				body: formData
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					alert("Student added successfully!");
					location.reload();
				} else {
					alert("Error: " + data.message);
				}
			})
			.catch(err => console.error(err));
		});

		csvForm.addEventListener("submit", e => {
			e.preventDefault();
			let formData = new FormData(csvForm);
			fetch("import_students.php", {
				method: "POST",
				body: formData
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					alert("CSV imported successfully!");
					location.reload();
				} else {
					alert("Error: " + data.message);
				}
			})
			.catch(err => console.error(err));
		});
		
		document.querySelector(".action-btn.edit").addEventListener("click", function(){
			document.querySelectorAll(".student-card").forEach(card => {
				card.addEventListener("click", function(e){
					e.preventDefault();
					let studentId = this.getAttribute("href").split("=")[1];
					fetch("get_student.php?id=" + studentId)
					.then(res => res.json())
					.then(data => {
						if(data.success){
							let form = document.getElementById("editStudentForm");
							form.id.value = data.student.ID;
							form.name.value = data.student.Name;
							form.programme.value = data.student.Programme;
							form.intake.value = data.student.Intake;
							document.getElementById("editStudentModal").style.display = "flex";
						} else {
							alert("Error: " + data.message);
						}
					});
				});
			});
		});

		document.getElementById("editStudentForm").addEventListener("submit", function(e){
			e.preventDefault();
			let formData = new FormData(this);
			fetch("update_student.php", {
				method: "POST",
				body: formData
			}).then(res => res.json())
			.then(data => {
				if(data.success){
					alert("Student updated successfully!");
					location.reload();
				} else {
					alert("Error: " + data.message);
				}
			});
		});

		document.getElementById("deleteStudentBtn").addEventListener("click", function(){
			if(confirm("Are you sure to delete this student?")){
				let id = document.getElementById("editStudentForm").id.value;
				fetch("delete_student.php?id=" + id, { method: "GET" })
				.then(res => res.json())
				.then(data => {
					if(data.success){
						alert("Student deleted successfully!");
						location.reload();
					} else {
						alert("Error: " + data.message);
					}
				});
			}
		});

		document.getElementById("cancelEditBtn").addEventListener("click", function(){
			document.getElementById("editStudentModal").style.display = "none";
		});

		const statusRadios = document.querySelectorAll('input[name="status"]');
		statusRadios.forEach(radio => {
			radio.addEventListener('change', () => {
				const value = radio.value; // all / active / inactive
				document.querySelectorAll('.student-card').forEach(card => {
					const status = card.dataset.status.toLowerCase();
					card.style.display = (value === 'all' || status === value) ? 'block' : 'none';
				});
			});
		});

		const searchInput = document.querySelector('.search-wide-pl input');

		const programmeFilter = document.getElementById('programmeFilter');
		const semesterFilter = document.getElementById('semesterFilter');

		function filterStudents() {
			const query = searchInput.value.toLowerCase();
			const statusValue = document.querySelector('input[name="status"]:checked').value;
			const programmeValue = programmeFilter.value.toLowerCase();
			const semesterValue = semesterFilter.value.toLowerCase();

			document.querySelectorAll('.student-card').forEach(card => {
				const name = card.querySelector('.student-name').textContent.toLowerCase();
				const id = card.querySelector('.student-info').textContent.match(/ID:\s*(\S+)/)[1].toLowerCase();
				const programme = card.dataset.programme.toLowerCase();
				const semester = card.dataset.semester.toLowerCase();
				const status = card.dataset.status.toLowerCase();

				const matchesSearch = name.includes(query) || id.includes(query);
				const matchesStatus = (statusValue === 'all' || status === statusValue);
				const matchesProgramme = (programmeValue === "" || programme === programmeValue);
				const matchesSemester = (semesterValue === "" || semester === semesterValue);

				card.style.display = (matchesSearch && matchesStatus && matchesProgramme && matchesSemester) ? 'block' : 'none';
			});
		}

		searchInput.addEventListener('input', filterStudents);
		statusRadios.forEach(radio => radio.addEventListener('change', filterStudents));
		programmeFilter.addEventListener('change', filterStudents);
		semesterFilter.addEventListener('change', filterStudents);


		</script>
	<div id="editModeOverlay" class="edit-mode-overlay" style="display:none;"></div>
	</body>
</html> 
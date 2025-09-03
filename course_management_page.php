<?php
include 'database.php';

$sql = "SELECT ID, Name, Subject_Code, Subject_Name, Programme, Intake, Grade 
        FROM student_detail__1_ 
        ORDER BY Subject_Name ASC, Name ASC";

$result = $conn->query($sql);

$courses = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $code = $row['Subject_Code'];
        if(!isset($courses[$code])){
            $courses[$code] = [
                'code' => $row['Subject_Code'],
                'name' => $row['Subject_Name'],
                'students' => [],
                'programmes' => [],
                'intakes' => [],
                'status' => 'inactive'
            ];
        }

        $courses[$code]['students'][] = [
            'ID' => $row['ID'],
            'Name' => $row['Name'],
            'Grade' => $row['Grade']
        ];

        if(!in_array($row['Programme'], $courses[$code]['programmes'])){
            $courses[$code]['programmes'][] = $row['Programme'];
        }
        if(!in_array($row['Intake'], $courses[$code]['intakes'])){
            $courses[$code]['intakes'][] = $row['Intake'];
        }

        if(strtoupper(trim($row['Grade'])) === 'N' || strtoupper(trim($row['Grade'])) === 'F'){
            $courses[$code]['status'] = 'active';
        }
    }
}

$courses = array_values($courses); 

$programmeSql = "SELECT DISTINCT Programme FROM student_detail__1_ ORDER BY Programme ASC";
$programmeRes = $conn->query($programmeSql);
$programmes = [];
if($programmeRes && $programmeRes->num_rows > 0){
    while($row = $programmeRes->fetch_assoc()){
        $programmes[] = $row['Programme'];
    }
}

$intakeSql = "SELECT DISTINCT Intake FROM student_detail__1_ ORDER BY Intake ASC";
$intakeRes = $conn->query($intakeSql);
$intakes = [];
if($intakeRes && $intakeRes->num_rows > 0){
    while($row = $intakeRes->fetch_assoc()){
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
	</head>
	<style>
		.course-card[data-status="inactive"] {
				background-color: #f8d7da; 
				border: 1px solid #f5c2c7;
				color: #842029;
			}

			.course-card[data-status="active"] {
				background-color: #d1e7dd;
				border: 1px solid #badbcc;
				color: #0f5132;
			}
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
				<div class = "top-bar-pl">
					<h1 class = "page-title-pl">Course Management</h1>
					
					<div class="search-wide-pl">
					  <input type="text" id="courseSearch" placeholder="Type to search...">
					  <ul id="courseDropdown"></ul>
					</div>
					
					<div class="top-controls-pl">
						<select id="programmeFilter">
							<option value="">All Programmes</option>
							<?php foreach($programmes as $prog): ?>
								<option value="<?php echo strtolower($prog); ?>"><?php echo $prog; ?></option>
							<?php endforeach; ?>
						</select>

						<select id="semesterFilter">
							<option value="">All Semesters</option>
							<?php foreach($intakes as $intk): ?>
								<option value="<?php echo strtolower($intk); ?>"><?php echo $intk; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
	
				</div>	
				
				<div class="content-wrapper">
					<div class = "inner-wrapper">
						<div class="nav-links">
							<a href="student_management_page.php">Student Management</a>
							<a href="course_management_page.php" class="active">Course Management</a>
						</div>
							
						<div class="rounded-box content-box-sc">
							<div class = "top-actions">
								<button class="action-btn add">Add Course</button>
								<button class="action-btn edit">Edit/Delete Course</button>
							</div>
							
							<div class="status-filters">
								<label><input type="radio" name="status" value="all" checked> All</label>
								<label><input type="radio" name="status" value="active"> Active</label>
								<label><input type="radio" name="status" value="inactive"> Inactive</label>
							</div>
							
							<div class="course-cards">
								<?php foreach($courses as $course): ?>
									<div class="course-card"
										 data-status="<?php echo $course['status']; ?>"
										 data-programme="<?php echo strtolower(implode(',', $course['programmes'])); ?>"
										 data-semester="<?php echo strtolower(implode(',', $course['intakes'])); ?>"
										 data-code="<?php echo $course['code']; ?>"
										 data-name="<?php echo $course['name']; ?>">
										<div class="course-name"><?php echo htmlspecialchars($course['name']); ?></div>
										<div class="course-info">
											Code: <?php echo htmlspecialchars($course['code']); ?><br>
											Status: <?php echo $course['status']; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>

						</div>
					</div>
				</div>									
			</div>		
		</div>

		<div id="addCourseModal" class="modal">
			<div class="modal-content">
				<span class = "close-btn">&times;</span>
				<h2>Add Course</h2>
				<div class="modal-tabs">
					<button type="button" class="tab-btn active" data-tab="manual">Manual Add</button>
					<button type="button" class="tab-btn" data-tab="csv">Import CSV</button>
				</div>

				<div class="tab-content" data-tab="manual" style="display:block;">
					<form id="addCourseForm">
						<label>Course Code:</label>
						<input type="text" name="code" required>

						<label>Course Name:</label>
						<input type="text" name="name" required>

						<div class="modal-buttons">
							<button type="button" class="cancel-btn">Cancel</button>
							<button type="submit">Save</button>
						</div>
					</form>
				</div>

				<div class="tab-content" data-tab="csv" style="display:none;">
					<form id="importCSVForm" enctype="multipart/form-data">
						<label>Select CSV File:</label>
						<input type="file" name="csv_file" accept=".csv" required>
						<div class="modal-buttons">
							<button type="button" class="cancel-btn">Cancel</button>
							<button type="submit">Upload</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		
		<div id="editCourseModal" class="modal" style="display:none;">
		  <div class="modal-content">
			<h2>Edit Course</h2>
			<form id="editCourseForm">
			  <label>Course Code:</label>
			  <input type="text" name="code" readonly>

			  <label>Course Name:</label>
			  <input type="text" name="name" required>

			  <div class="modal-buttons">
				<button type="button" id="deleteCourseBtn" style="background:red;color:white;">Delete</button>
				<button type="button" id="cancelEditCourseBtn">Cancel</button>
				<button type="submit">Save Changes</button>
			  </div>
			</form>
		  </div>
		</div>

		
		<div id="courseDetailModal" class="modal">
		  <div class="modal-content" style="width:80%; max-width:900px;">
			<span class="close-btn">&times;</span>
			<div class="modal-header">
			  <h2 id="courseTitle"></h2>
			  <div class="search-wide">
				<input type="text" placeholder="Type to search...">
			  </div>
			</div>

			<div class="status-filters">
			  <label><input type="radio" name="filter" value="all" checked> All</label>
			  <label><input type="radio" name="filter" value="registered"> Registered</label>
			  <label><input type="radio" name="filter" value="unregistered"> Unregistered</label>
			</div>

			<div class="student-lists" style="display:flex; gap:20px;">
			  <div class="column" style="flex:1;">
				<h3>Registered</h3>
				<div class="student-cards" id="registeredList"></div>
			  </div>
			  <div class="column" style="flex:1;">
				<h3>Unregistered</h3>
				<div class="student-cards" id="unregisteredList"></div>
			  </div>
			</div>

			<div class="modal-buttons" style="margin-top:20px;">
			  <button type="button" class="cancel-btn">Close</button>
			</div>
		  </div>
		</div>


		<script>
			let editMode = false;       
			let selectedCourse = null; 

			document.querySelector(".action-btn.edit").addEventListener("click", () => {
				editMode = true;
				selectedCourse = null;
			});

			document.querySelectorAll(".course-card").forEach(card => {
				card.addEventListener("click", function() {
					if(editMode){
						document.querySelectorAll(".course-card").forEach(c => c.classList.remove("selected"));
						this.classList.add("selected");
						selectedCourse = {
							code: this.dataset.code,
							name: this.dataset.name
						};
						const form = document.getElementById("editCourseForm");
						form.code.value = selectedCourse.code;
						form.name.value = selectedCourse.name;
						document.getElementById("editCourseModal").style.display = "flex";
					} else {
						const code = this.dataset.code;
						const name = this.dataset.name;
						const status = this.dataset.status; 

						document.getElementById("courseTitle").innerText = name;

						const detailModalContent = document.querySelector("#courseDetailModal .modal-content");
						if(status === "active"){
							detailModalContent.style.backgroundColor = "#e6ffe6"; 
							detailModalContent.style.border = "2px solid green";
						} else {
							detailModalContent.style.backgroundColor = "#ffe6e6";
							detailModalContent.style.border = "2px solid gray";
						}

						fetch("get_course_students.php?subject_code=" + encodeURIComponent(code))
							.then(res => res.json())
							.then(data => {
								if(data.success){
									registeredData = data.registered;
									unregisteredData = data.unregistered;
									filterStudents(); 
									document.getElementById("courseDetailModal").style.display = "flex";
								}
							});

					}
				});
			});

			function renderStudentList(list, container){
				container.innerHTML = "";
				list.forEach(stu => {
					const card = document.createElement("div");
					card.classList.add("student-card");
					card.innerHTML = `<strong>${stu.ID}</strong> - ${stu.Name} ${stu.Grade ? "(" + stu.Grade + ")" : ""}`;
					container.appendChild(card);
				});
			}

			document.getElementById("editCourseForm").addEventListener("submit", function(e){
				e.preventDefault();
				if(!selectedCourse) return;

				let formData = new FormData(this);
				fetch("update_subject.php", { method:"POST", body: formData })
					.then(res=>res.json())
					.then(data=>{
						if(data.success) location.reload();
						else alert("Error: " + data.message);
					});
			});

			document.getElementById("deleteCourseBtn").addEventListener("click", function(){
				if(!selectedCourse) return;
				if(!confirm("Are you sure you want to delete this course?")) return;

				let formData = new FormData();
				formData.append("code", selectedCourse.code);

				fetch("delete_subject.php", { method:"POST", body: formData })
					.then(res=>res.json())
					.then(data=>{
						if(data.success) location.reload();
						else alert("Error: " + data.message);
					});
			});

			document.getElementById("cancelEditCourseBtn").addEventListener("click", function(){
				document.getElementById("editCourseModal").style.display = "none";
				editMode = false;
				selectedCourse = null;
			});

			document.querySelector(".action-btn.add").addEventListener("click", () => {
				document.getElementById("addCourseModal").style.display = "flex";
			});

			document.querySelectorAll(".cancel-btn, .close-btn").forEach(btn => {
				btn.addEventListener("click", () => {
					btn.closest(".modal").style.display = "none";
					editMode = false;
					selectedCourse = null;
				});
			});

			document.getElementById("addCourseForm").addEventListener("submit", function(e){
				e.preventDefault();
				let formData = new FormData(this);
				fetch("add_course.php", { method:"POST", body: formData })
					.then(res=>res.json())
					.then(data=>{
						if(data.success) location.reload();
						else alert("Error: " + data.message);
					});
			});
			
			const studentFilterRadios = document.querySelectorAll('#courseDetailModal input[name="filter"]');
			const registeredList = document.getElementById("registeredList");
			const unregisteredList = document.getElementById("unregisteredList");

			let registeredData = [];
			let unregisteredData = [];

			function renderStudentList(list, container){
				container.innerHTML = "";
				list.forEach(stu => {
					const card = document.createElement("div");
					card.classList.add("student-card");
					card.innerHTML = `<strong>${stu.ID}</strong> - ${stu.Name} ${stu.Grade ? "(" + stu.Grade + ")" : ""}`;
					container.appendChild(card);
				});
			}

			function filterStudents() {
				const value = document.querySelector('#courseDetailModal input[name="filter"]:checked').value;
				if (value === "all") {
					renderStudentList(registeredData, registeredList);
					renderStudentList(unregisteredData, unregisteredList);
				} else if (value === "registered") {
					renderStudentList(registeredData, registeredList);
					unregisteredList.innerHTML = ""; 
				} else if (value === "unregistered") {
					registeredList.innerHTML = "";
					renderStudentList(unregisteredData, unregisteredList);
				}
			}

			studentFilterRadios.forEach(radio => {
				radio.addEventListener("change", filterStudents);
			});

			const searchInput = document.querySelector('#courseSearch');
			const statusRadios = document.querySelectorAll('input[name="status"]');
			const programmeFilter = document.getElementById('programmeFilter');
			const semesterFilter = document.getElementById('semesterFilter');

			function filterCourses(){
				const query = searchInput.value.toLowerCase();
				const statusValue = document.querySelector('input[name="status"]:checked').value;
				const programmeValue = programmeFilter.value.toLowerCase();
				const semesterValue = semesterFilter.value.toLowerCase();

				document.querySelectorAll('.course-card').forEach(card => {
					const name = card.dataset.name.toLowerCase();
					const code = card.dataset.code.toLowerCase();
					const status = card.dataset.status.toLowerCase();
					const programmes = card.dataset.programme.split(',');
					const intakes = card.dataset.semester.split(',');

					const matchesSearch = name.includes(query) || code.includes(query);
					const matchesStatus = (statusValue === 'all' || status === statusValue);
					const matchesProgramme = (programmeValue === "" || programmes.includes(programmeValue));
					const matchesSemester = (semesterValue === "" || intakes.includes(semesterValue));

					card.style.display = (matchesSearch && matchesStatus && matchesProgramme && matchesSemester) ? 'block' : 'none';
				});
			}

			searchInput.addEventListener('input', filterCourses);
			statusRadios.forEach(r => r.addEventListener('change', filterCourses));
			programmeFilter.addEventListener('change', filterCourses);
			semesterFilter.addEventListener('change', filterCourses);

			document.getElementById("importCSVForm").addEventListener("submit", function(e){
			e.preventDefault();
			let formData = new FormData(this);

			fetch("import_course_csv.php", { method: "POST", body: formData })
				.then(res => res.json())
				.then(data => {
					if(data.success){
						alert("CSV imported successfully!");
						location.reload();
					} else {
						alert("Error: " + data.message);
					}
				});
		});

		document.querySelectorAll(".tab-btn").forEach(btn => {
			btn.addEventListener("click", () => {
				const tab = btn.dataset.tab;

				document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
				btn.classList.add("active");

				document.querySelectorAll(".tab-content").forEach(content => {
					content.style.display = content.dataset.tab === tab ? "block" : "none";
				});
			});
		});

		</script>
	</body>
</html> 
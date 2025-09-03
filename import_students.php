<?php
include 'database.php';

if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($fileTmp, 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            $id = $conn->real_escape_string($row[0]);
            $name = $conn->real_escape_string($row[1]);
            $programme = $conn->real_escape_string($row[2]);
            $intake = $conn->real_escape_string($row[3]);

            $check = $conn->query("SELECT ID FROM student_detail__1_ WHERE ID='$id'");
            if ($check->num_rows === 0) {
                $conn->query("INSERT INTO student_detail__1_ (ID, Name, Programme, Intake) 
                              VALUES ('$id', '$name', '$programme', '$intake')");
            }
        }
        fclose($handle);
        echo json_encode(["status" => "success", "message" => "CSV imported successfully"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Upload failed"]);
}
?>

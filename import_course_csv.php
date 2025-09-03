<?php
include 'database.php';

$response = ['success' => false, 'message' => ''];

if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0){
    $file = $_FILES['csv_file']['tmp_name'];

    if(($handle = fopen($file, "r")) !== FALSE){
        $header = fgetcsv($handle);
        while(($data = fgetcsv($handle)) !== FALSE){
            $subject_code = $conn->real_escape_string($data[0]);
            $subject_name = $conn->real_escape_string($data[1]);
            $programme = $conn->real_escape_string($data[2]);
            $intake = $conn->real_escape_string($data[3]);

            $sql = "INSERT INTO student_detail__1_ (Subject_Code, Subject_Name, Programme, Intake) 
                    VALUES ('$subject_code', '$subject_name', '$programme', '$intake')
                    ON DUPLICATE KEY UPDATE Subject_Name='$subject_name', Programme='$programme', Intake='$intake'";

            if(!$conn->query($sql)){
                $response['message'] = "Database error: ".$conn->error;
                echo json_encode($response);
                exit;
            }
        }
        fclose($handle);
        $response['success'] = true;
    } else {
        $response['message'] = "Failed to open CSV file.";
    }
} else {
    $response['message'] = "No CSV file uploaded.";
}

echo json_encode($response);
?>

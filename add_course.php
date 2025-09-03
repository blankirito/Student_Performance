<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);

    if ($code && $name) {
        $check = $conn->prepare("SELECT 1 FROM student_detail__1_ WHERE Subject_Code = ?");
        $check->bind_param("s", $code);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Subject code already exists"]);
            $check->close();
            exit;
        }
        $check->close();

        $stmt = $conn->prepare("INSERT INTO student_detail__1_ (Subject_Code, Subject_Name) VALUES (?, ?)");
        $stmt->bind_param("ss", $code, $name);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Missing fields"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}
?>
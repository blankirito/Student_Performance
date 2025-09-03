<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id']);
    $name = trim($_POST['name']);
    $programme = trim($_POST['programme']);
    $intake = $_POST['intake'];

    if ($id && $name && $programme && $intake) {
        $stmt = $conn->prepare("INSERT INTO student_detail__1_ (ID, Name, Programme, Intake) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $id, $name, $programme, $intake);

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

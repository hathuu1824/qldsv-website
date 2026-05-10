<?php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_id = $_POST['assignment_id'];
    $student_id = $_POST['student_id'];
    $submission_text = $_POST['submission_text'];
    $file_path = NULL;

    // Xử lý upload file
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        $target_dir = "uploads/submissions/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["submission_file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $target_file)) {
            $file_path = $target_file;
        }
    }

    // Lưu vào database
    $sql = "INSERT INTO submissions (assignment_id, student_id, submission_text, file_path) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $assignment_id, $student_id, $submission_text, $file_path);

    if ($stmt->execute()) {
        header("Location: submit_assignment.php?id=$assignment_id&status=success");
    } else {
        echo "Lỗi: " . $conn->error;
    }
}
?>
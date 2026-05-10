<?php
session_start();
require '../../db_connection.php';

// Bật báo lỗi SQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Lấy faculty_id để khi xong thì quay về đúng Khoa đang xem
$faculty_id = isset($_POST['faculty_id']) ? mysqli_real_escape_string($conn, $_POST['faculty_id']) : '';
$redirect_url = "../class.php?faculty=" . $faculty_id; // Giả sử file chính của bạn là course.php

// --- TRƯỜNG HỢP 1: THÊM LỚP HỌC MỚI ---
if (isset($_POST['btn_add'])) {
    try {
        $subject_id = mysqli_real_escape_string($conn, $_POST['subject_id']);
        $class_code = mysqli_real_escape_string($conn, $_POST['class_code']);
        $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
        $group_id   = mysqli_real_escape_string($conn, $_POST['group_id']);
        $semester   = mysqli_real_escape_string($conn, $_POST['semester']);
        $status     = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Xử lý giảng viên (có thể để trống)
        $account_id = !empty($_POST['account_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['account_id']) . "'" : "NULL";

        // Thực hiện Insert
        $sql_add = "INSERT INTO classes (subject_id, class_code, class_name, semester, group_id, account_id, status) 
                    VALUES ('$subject_id', '$class_code', '$class_name', '$semester', '$group_id', $account_id, '$status')";
        
        if (mysqli_query($conn, $sql_add)) {
            header("Location: $redirect_url&msg=add_success");
        } else {
            throw new Exception("Lỗi thêm lớp");
        }
    } catch (Exception $e) {
        // Có thể trùng class_code + group_id nếu bạn đặt UNIQUE KEY
        header("Location: $redirect_url&msg=error");
    }
    exit();
}

// --- TRƯỜNG HỢP 2: CẬP NHẬT LỚP HỌC ---
if (isset($_POST['btn_edit'])) {
    try {
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
        $semester   = mysqli_real_escape_string($conn, $_POST['semester']);
        $status     = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Xử lý giảng viên (cho phép đổi giảng viên)
        $account_id = !empty($_POST['account_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['account_id']) . "'" : "NULL";

        /** * QUAN TRỌNG: 
         * Không cập nhật class_code và group_id vì chúng là định danh duy nhất (Readonly)
         */
        $sql_update = "UPDATE classes SET 
                        class_name = '$class_name', 
                        semester = '$semester', 
                        status = '$status', 
                        account_id = $account_id 
                      WHERE id = '$id'";

        if (mysqli_query($conn, $sql_update)) {
            header("Location: $redirect_url&msg=update_success");
        } else {
            throw new Exception("Lỗi cập nhật");
        }
    } catch (Exception $e) {
        header("Location: $redirect_url&msg=error");
    }
    exit();
}

// Redirect nếu truy cập bất hợp pháp
header("Location: ../course.php");
exit();
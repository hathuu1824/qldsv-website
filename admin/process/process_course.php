<?php
session_start();
// Kết nối DB
require '../../db_connection.php';

// Bật chế độ báo lỗi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Lấy faculty_id để redirect về đúng trang đang xem (nếu có)
$faculty_id = isset($_POST['faculty_id_redirect']) ? $_POST['faculty_id_redirect'] : '';

// --- TRƯỜNG HỢP 1: THÊM HỌC PHẦN MỚI ---
if (isset($_POST['btn_add'])) {
    try {
        // 1. Lấy dữ liệu từ FORM
        $major_id = mysqli_real_escape_string($conn, $_POST['major_id']);
        $subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
        $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
        $credit = mysqli_real_escape_string($conn, $_POST['credit']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $is_e = mysqli_real_escape_string($conn, $_POST['is_e']); // 0: Bắt buộc, 1: Tự chọn

        // 2. Thực hiện chèn vào bảng subjects
        $query_add = "INSERT INTO subjects (major_id, subject_code, subject_name, credit, description, is_e) 
                      VALUES ('$major_id', '$subject_code', '$subject_name', '$credit', '$description', '$is_e')";
        
        if (mysqli_query($conn, $query_add)) {
            header("Location: ../course.php?faculty=$faculty_id&msg=add_success");
        } else {
            throw new Exception("Lỗi thêm học phần");
        }
        exit();

    } catch (Exception $e) {
        header("Location: ../course.php?faculty=$faculty_id&msg=error");
        exit();
    }
}

// --- TRƯỜNG HỢP 2: CẬP NHẬT HỌC PHẦN ---
if (isset($_POST['btn_edit'])) {
    try {
        // 1. Lấy dữ liệu
        $subject_id = mysqli_real_escape_string($conn, $_POST['id']); 
        $major_id = mysqli_real_escape_string($conn, $_POST['major_id']);
        $subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
        $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
        $credit = mysqli_real_escape_string($conn, $_POST['credit']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $is_e = mysqli_real_escape_string($conn, $_POST['is_e']);

        // 2. Thực hiện UPDATE
        $sql_update = "UPDATE subjects SET 
                        major_id = '$major_id', 
                        subject_code = '$subject_code', 
                        subject_name = '$subject_name', 
                        credit = '$credit', 
                        description = '$description', 
                        is_e = '$is_e' 
                      WHERE id = '$subject_id'";

        if (mysqli_query($conn, $sql_update)) {
            header("Location: ../course.php?faculty=$faculty_id&msg=update_success");
        } else {
            throw new Exception("Lỗi cập nhật");
        }
        exit();

    } catch (Exception $e) {
        header("Location: ../course.php?faculty=$faculty_id&msg=error");
        exit();
    }
}

// Nếu truy cập trực tiếp file này mà không qua form
header("Location: ../course.php");
exit();
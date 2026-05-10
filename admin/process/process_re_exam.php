<?php
session_start();
require '../../db_connection.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// --- TRƯỜNG HỢP 1: THÊM LỊCH THI PHÚC KHẢO (BẢNG 1) ---
if (isset($_POST['btn_add_session'])) {
    $faculty_id = mysqli_real_escape_string($conn, $_POST['faculty_id']);
    $subject_id = mysqli_real_escape_string($conn, $_POST['subject_id']);
    $exam_date  = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $exam_time  = mysqli_real_escape_string($conn, $_POST['exam_time']);
    $room       = mysqli_real_escape_string($conn, $_POST['room']);
    $reg_start  = mysqli_real_escape_string($conn, $_POST['reg_start']);
    $reg_end    = mysqli_real_escape_string($conn, $_POST['reg_end']);

    // Chèn vào bảng re_exam_sessions
    $sql = "INSERT INTO re_exam_sessions (subject_id, exam_date, exam_time, room, reg_start, reg_end) 
            VALUES ('$subject_id', '$exam_date', '$exam_time', '$room', '$reg_start', '$reg_end')";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../grade.php?faculty=$faculty_id&msg=add_success");
    } else {
        header("Location: ../grade.php?faculty=$faculty_id&msg=system_error");
    }
    exit();
}

// --- TRƯỜNG HỢP 2: DUYỆT HOẶC TỪ CHỐI YÊU CẦU (BẢNG 2) ---
// Sử dụng phương thức GET để xử lý nhanh từ nút bấm
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $request_id = mysqli_real_escape_string($conn, $_GET['request_id']);
    $faculty_id = mysqli_real_escape_string($conn, $_GET['faculty']);
    $action     = $_GET['action']; // 'approve' hoặc 'reject'
    
    $new_status = ($action === 'approve') ? 'Duyệt' : 'Bi từ chối';

    $sql_update = "UPDATE re_exam_requests SET status = '$new_status' WHERE id = '$request_id'";

    if (mysqli_query($conn, $sql_update)) {
        header("Location: ../grade.php?faculty=$faculty_id&msg=update_success");
    } else {
        header("Location: ../grade.php?faculty=$faculty_id&msg=system_error");
    }
    exit();
}

// Nếu không có hành động nào phù hợp, quay lại trang chính
header("Location: ../grade.php");
exit();
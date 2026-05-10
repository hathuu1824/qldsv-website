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

// Lấy faculty_id để redirect về đúng Khoa đang xem
$faculty_id = isset($_POST['faculty_id']) ? mysqli_real_escape_string($conn, $_POST['faculty_id']) : '';
$redirect_url = "../calendar.php?faculty=" . $faculty_id;

// --- TRƯỜNG HỢP 1: THÊM LỊCH THI MỚI ---
if (isset($_POST['btn_add'])) {
    try {
        $class_id  = mysqli_real_escape_string($conn, $_POST['class_id']);
        $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
        $exam_time = mysqli_real_escape_string($conn, $_POST['exam_time']);
        $room      = mysqli_real_escape_string($conn, $_POST['room']);

        $sql_add = "INSERT INTO exam_sessions (class_id, exam_date, exam_time, room) 
                    VALUES ('$class_id', '$exam_date', '$exam_time', '$room')";
        
        if (mysqli_query($conn, $sql_add)) {
            header("Location: $redirect_url&msg=add_success");
        } else {
            throw new Exception("Lỗi thêm lịch thi");
        }
    } catch (Exception $e) {
        header("Location: $redirect_url&msg=error");
    }
    exit();
}

// --- TRƯỜNG HỢP 2: CẬP NHẬT LỊCH THI ---
if (isset($_POST['btn_edit'])) {
    try {
        $id        = mysqli_real_escape_string($conn, $_POST['id']);
        $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
        $exam_time = mysqli_real_escape_string($conn, $_POST['exam_time']);
        $room      = mysqli_real_escape_string($conn, $_POST['room']);

        // Cập nhật thông tin ca thi (Không cho sửa class_id để đảm bảo tính nhất quán)
        $sql_update = "UPDATE exam_sessions SET 
                        exam_date = '$exam_date', 
                        exam_time = '$exam_time', 
                        room = '$room' 
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

header("Location: ../calendar.php");
exit();
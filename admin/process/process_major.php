<?php
session_start();
require '../../db_connection.php';

// Bật báo lỗi để kiểm tra trong quá trình code
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Lấy faculty_id để khi chuyển hướng quay lại đúng trang Khoa đang xem
$faculty_id = isset($_POST['faculty_id']) ? mysqli_real_escape_string($conn, $_POST['faculty_id']) : '';
$redirect_url = "../major.php?faculty=" . $faculty_id; // Giả sử file chính của bạn là major.php

// --- TRƯỜNG HỢP 1: THÊM CHUYÊN NGÀNH MỚI ---
if (isset($_POST['btn_add'])) {
    try {
        $major_code = mysqli_real_escape_string($conn, $_POST['major_code']);
        $major_name = trim(mysqli_real_escape_string($conn, $_POST['major_name']));

        if (empty($major_name)) {
            header("Location: $redirect_url&msg=empty_name");
            exit();
        }

        // Thực hiện chèn dữ liệu
        $sql_add = "INSERT INTO majors (faculty_id, major_code, major_name) 
                    VALUES ('$faculty_id', '$major_code', '$major_name')";
        
        if (mysqli_query($conn, $sql_add)) {
            header("Location: $redirect_url&msg=add_success");
        } else {
            throw new Exception("Lỗi thêm chuyên ngành");
        }
    } catch (Exception $e) {
        // Lỗi thường gặp: Trùng mã chuyên ngành
        header("Location: $redirect_url&msg=error");
    }
    exit();
}

// --- TRƯỜNG HỢP 2: CẬP NHẬT TÊN CHUYÊN NGÀNH ---
if (isset($_POST['btn_edit'])) {
    try {
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $major_name = trim(mysqli_real_escape_string($conn, $_POST['major_name']));

        if (empty($major_name)) {
            header("Location: $redirect_url&msg=empty_name");
            exit();
        }
        
        $sql_update = "UPDATE majors SET major_name = '$major_name' WHERE id = '$id'";

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

// Thiết lập lịch đăng ký chuyên ngành
if (isset($_POST['btn_add_schedule'])) {
    $faculty_id = mysqli_real_escape_string($conn, $_POST['faculty_id']);
    $major_id   = mysqli_real_escape_string($conn, $_POST['major_id']);
    $date_start = $_POST['date_start'];
    $date_end   = $_POST['date_end'];
    $note       = mysqli_real_escape_string($conn, $_POST['note']);

    // Kiểm tra xem chuyên ngành này đã được thiết lập lịch chưa
    $check_sql = "SELECT id FROM major_registrations WHERE major_id = '$major_id' AND account_id = 0";
    $check_res = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        // Nếu đã có lịch rồi thì thực hiện UPDATE thay vì INSERT
        $sql = "UPDATE major_registrations 
                SET date_start = '$date_start', 
                    date_end = '$date_end', 
                    note = '$note',
                    status = 'Mở đăng ký'
                WHERE major_id = '$major_id' AND account_id = 0";
    } else {
        // Nếu chưa có thì INSERT mới
        $sql = "INSERT INTO major_registrations (account_id, major_id, note, status, date_start, date_end) 
                VALUES (0, '$major_id', '$note', 'Mở đăng ký', '$date_start', '$date_end')";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: ../major.php?faculty=$faculty_id&msg=add_success");
    } else {
        // Debug lỗi nếu cần: echo mysqli_error($conn); die();
        header("Location: ../major.php?faculty=$faculty_id&msg=system_error");
    }
    exit();
}

// Nếu truy cập file trực tiếp mà không qua form
header("Location: ../major.php");
exit();
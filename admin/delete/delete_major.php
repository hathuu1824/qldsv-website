<?php
session_start();
require '../../db_connection.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// 2. Lấy ID chuyên ngành cần xóa
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Lấy faculty_id để khi xóa xong quay về đúng Khoa đang xem
    $faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';
    $redirect_url = "../major.php?faculty=" . $faculty_id;

    try {
        $sql_del = "DELETE FROM majors WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql_del)) {
            header("Location: $redirect_url&msg=delete_success");
        } else {
            throw new Exception("Lỗi thực thi");
        }
        exit();

    } catch (Exception $e) {
        header("Location: $redirect_url&msg=error_constrain");
        exit();
    }
} else {
    header("Location: ../major.php");
    exit();
}
<?php
session_start();
require '../../db_connection.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// 2. Lấy ID lớp học cần xóa
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Lấy faculty_id để redirect về đúng trang đang xem
    $faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';
    
    // Xây dựng URL quay lại
    $redirect_url = "../class.php?faculty=" . $faculty_id;

    try {
        $sql_del = "DELETE FROM classes WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql_del)) {
            // Xóa thành công
            header("Location: $redirect_url&msg=delete_success");
        } else {
            throw new Exception("Lỗi thực thi SQL");
        }
        exit();

    } catch (Exception $e) {
        header("Location: $redirect_url&msg=error");
        exit();
    }
} else {
    // Nếu không có ID thì quay về trang danh sách chung
    header("Location: ../class.php");
    exit();
}
?>
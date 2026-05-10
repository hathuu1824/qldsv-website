<?php
session_start();
require '../../db_connection.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Lấy ID sinh viên cần xóa
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // 3. Sử dụng Transaction để đảm bảo xóa sạch cả 2 bảng
    mysqli_begin_transaction($conn);

    try {
        // Xóa trong bảng profile trước (bảng con)
        $sql_del_profile = "DELETE FROM profile WHERE account_id = '$id'";
        mysqli_query($conn, $sql_del_profile);

        // Xóa trong bảng account sau (bảng cha)
        $sql_del_account = "DELETE FROM account WHERE id = '$id'";
        mysqli_query($conn, $sql_del_account);

        // Nếu mọi thứ ổn thì xác nhận lưu thay đổi
        mysqli_commit($conn);
        header("Location: ../student.php?msg=delete_success");
        exit();

    } catch (Exception $e) {
        // Nếu có lỗi, hoàn tác lại để tránh mất dữ liệu nửa chừng
        mysqli_rollback($conn);
        header("Location: ../student.php?msg=error");
        exit();
    }
} else {
    // Nếu không có ID thì quay về trang danh sách
    header("Location: ../student.php");
    exit();
}
?>
<?php
session_start();
require '../../db_connection.php';

// Kiểm tra quyền
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    exit("Quyền truy cập bị từ chối.");
}

if (isset($_GET['id'])) {
    $account_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Mã hóa mật khẩu mặc định 123456
    $new_password = password_hash('123456', PASSWORD_DEFAULT);

    $sql = "UPDATE account SET password = '$new_password' WHERE id = '$account_id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../account.php?success=reset_done");
    } else {
        header("Location: ../account.php?error=reset_failed");
    }
} else {
    header("Location: ../account.php");
}
?>
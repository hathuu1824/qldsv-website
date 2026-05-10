<?php
session_start();
require '../db_connection.php';

if (!isset($_SESSION['id'])) {
    exit("Bạn chưa đăng nhập.");
}

if (isset($_POST['btn_change_pwd'])) {
    $user_id = $_SESSION['id'];
    $current_pwd = $_POST['current_password'];
    $new_pwd = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];

    // 1. Kiểm tra mật khẩu mới và xác nhận có khớp không
    if ($new_pwd !== $confirm_pwd) {
        header("Location: ../change_password.php?error=not_match");
        exit();
    }

    // 2. Lấy mật khẩu hiện tại trong DB để so sánh
    $query = "SELECT password FROM account WHERE id = '$user_id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($current_pwd, $user['password'])) {
        // 3. Nếu mật khẩu cũ đúng -> Tiến hành băm mật khẩu mới và cập nhật
        $hashed_new_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
        $update_sql = "UPDATE account SET password = '$hashed_new_pwd' WHERE id = '$user_id'";

        if (mysqli_query($conn, $update_sql)) {
            header("Location: ../change_password.php?success=1");
        } else {
            header("Location: ../change_password.php?error=failed");
        }
    } else {
        // Mật khẩu cũ sai
        header("Location: ../change_password.php?error=wrong_current");
    }
} else {
    header("Location: ../change_password.php");
}
?>
<?php
session_start();
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Truy vấn thông tin tài khoản
    $stmt = $conn->prepare("SELECT id, username, password, role FROM account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $db_password = $user['password'];

        // Kiểm tra mật khẩu 
        $is_hashed = (strpos($db_password, '$') === 0);

        if ($is_hashed) {
            $verify = password_verify($password, $db_password);
        } else {
            $verify = ($password === $db_password);
        }

        // Xử lý kết quả đăng nhập
        if ($verify) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['role']       = $user['role'];

            if ($user['role'] === 'admin'){
                header("Location: admin/student.php");
            } else if ($user['role'] === 'teacher'){
                header("Location: teacher/home.php");
            } else{
                header("Location: student/home.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Mật khẩu không chính xác!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Tên đăng nhập không tồn tại!";
        header("Location: login.php");
        exit();
    }
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
    <title>Đăng nhập</title>
</head>
<body>
    <div class="main">
        <div class="wallpaper-container">
            <img src="images/logo.png" alt="logo" class="logo">
            <p>Chào mừng trở lại. Vui lòng đăng nhập để sử dụng hệ thống</p>
        </div>
        <div class="login-container">
            <div class="login-card">
                <h2>Đăng nhập</h2>
                <?php if (isset($_SESSION['error'])): ?>
                    <div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;">
                        <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']); 
                        ?>
                    </div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="detail">
                        <div class="remember">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Ghi nhớ đăng nhập</label>
                        </div>
                        <div class="links">
                            <a href="forgot.php" class="forget">Quên mật khẩu?</a>
                            <a href="register.php" class="register">Chưa có tài khoản?</a>
                        </div>
                    </div>
                    <button type="submit">Đăng nhập</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
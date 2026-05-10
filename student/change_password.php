<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Kiểm tra nếu chưa đăng nhập thì đá về trang login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin.css">
    <title>Đổi mật khẩu</title>
    <style>
        .change-pwd-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .msg { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; }
        .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <main class="main-container">
        <div class="change-pwd-container">
            <h2 style="text-align: center; margin-bottom: 20px;">Đổi mật khẩu</h2>

            <!-- Hiển thị thông báo nếu có -->
            <?php if(isset($_GET['error'])): ?>
                <div class="msg msg-error">
                    <?php 
                        if($_GET['error'] == 'wrong_current') echo "Mật khẩu hiện tại không đúng!";
                        elseif($_GET['error'] == 'not_match') echo "Mật khẩu mới không khớp nhau!";
                        elseif($_GET['error'] == 'failed') echo "Có lỗi xảy ra, vui lòng thử lại!";
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="msg msg-success">Đổi mật khẩu thành công!</div>
            <?php endif; ?>

            <form action="process/process_change.php" method="POST">
                <div class="form-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="current_password" required>
                </div>
                <hr>
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" name="btn_change_pwd" class="btn-submit" style="width: 100%;">Cập nhật mật khẩu</button>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="javascript:history.back()" style="color: #666; text-decoration: none;">← Quay lại</a>
                </p>
            </form>
        </div>
    </main>
</body>
</html>
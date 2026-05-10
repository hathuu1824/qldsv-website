<?php
session_start();
require 'db_connection.php'; 
$show_popup = false;
if (isset($_SESSION['dky_thanh_cong'])) {
    $show_popup = true;
    unset($_SESSION['dky_thanh_cong']); 
}

function generateUserCode($conn, $role) {
    // Xác định tiền tố dựa trên role
    $prefix = ($role === 'teacher') ? 'GV' : 'SV';
    
    // Tìm mã lớn nhất hiện có của tiền tố đó
    $query = "SELECT code FROM account WHERE code LIKE '$prefix%' ORDER BY code DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastCode = $row['code'];
        
        $number = (int)substr($lastCode, 2); 
        $newNumber = $number + 1;
        
        return $prefix . str_pad($newNumber, 2, "0", STR_PAD_LEFT);
    }
    
    return $prefix . "01";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Lấy role trước để sinh mã cho đúng
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $code = generateUserCode($conn, $role);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 2. Lấy thông tin họ tên để đưa vào bảng profile
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Kiểm tra độ dài mật khẩu
    if (strlen($password) < 8) {
        echo "<script>alert('Mật khẩu phải từ 8 ký tự trở lên!'); window.history.back();</script>";
        exit();
    }

    // Kiểm tra username đã tồn tại chưa
    $check_user = "SELECT * FROM account WHERE username='$username'";
    $res = mysqli_query($conn, $check_user);
    if (mysqli_num_rows($res) > 0) {
        echo "<script>alert('Tên đăng nhập này đã tồn tại!'); window.history.back();</script>";
        exit();
    }

    // Mã hóa & Lưu hint 
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $password_hint = mysqli_real_escape_string($conn, $password); 

    // INSERT vào bảng 
    $sql = "INSERT INTO account (code, username, password, password_hint, role) 
            VALUES ('$code','$username', '$hashed_password', '$password_hint', '$role')";

    if (mysqli_query($conn, $sql)) {
        $sql_profile = "INSERT INTO profile (first_name, last_name) 
                        VALUES ('$first_name', '$last_name')";
        
        if (mysqli_query($conn, $sql_profile)) {
            $_SESSION['dky_thanh_cong'] = true;
            header("Location: register.php");
            exit();
        } else {
            echo "Lỗi khi tạo profile: " . mysqli_error($conn);
        }
    } else {
        echo "Lỗi: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/register.css">
    <title>Đăng ký</title>
</head>
<body>
    <div class="main">
        <div class="register-container">
            <div class="register-card">
                <h2>Đăng ký</h2>
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Họ và tên đệm:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Tên:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Tên đăng nhập:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Chọn vai trò:</label>
                        <div class="role-group">
                            <input type="radio" id="teacher" name="role" value="teacher" required>
                            <label for="teacher" class="role-item">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Giảng viên</span>
                            </label>
                            <input type="radio" id="student" name="role" value="student">
                            <label for="student" class="role-item">
                                <i class="fas fa-user-graduate"></i>
                                <span>Sinh viên</span>
                            </label>
                        </div>
                    </div>
                    <div class="detail">
                        <a href="login.php">Đã có tài khoản?</a>
                    </div>
                    <button type="submit">Đăng ký</button>
                </form>
            </div>
        </div>
        <div class="wallpaper-container">
            <img src="images/logo.png" alt="logo" class="logo">
            <p>Chào mừng đến với hệ thống. Vui lòng đăng ký để sử dụng</p>
        </div>
    </div>
    <?php if ($show_popup): ?>
    <script type="text/javascript">
        if (confirm('Chúc mừng bạn đã đăng ký thành công!')) {
            window.location.href = 'login.php';
        }
    </script>
    <?php endif; ?>
</body>
</html>
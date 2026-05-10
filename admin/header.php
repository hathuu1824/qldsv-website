<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../db_connection.php'; 

$user_id  = $_SESSION['id'] ?? 0; 
$username = $_SESSION['username'] ?? 'user';

$full_name = "Chưa cập nhật";
$avatar_from_db = ""; 

if ($user_id > 0) {
    $sql = "SELECT p.first_name, p.last_name, p.avatar
            FROM account a 
            LEFT JOIN profile p ON a.id = p.account_id 
            WHERE a.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $fname = trim($row['first_name'] ?? '');
        $lname = trim($row['last_name'] ?? '');

        if ($fname !== '' || $lname !== '') {
            $full_name = trim($lname . ' ' . $fname);
        }
        
        if (!empty($row['avatar'])) {
            $avatar_from_db = "../" . $row['avatar']; 
        }
    }
    $stmt->close();
}

$first_letter = mb_strtoupper(mb_substr($full_name !== "Chưa cập nhật" ? $full_name : $username, 0, 1, 'UTF-8'), 'UTF-8');
$palette = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c', '#34495e'];
$color_index = $user_id % count($palette);
$bg_color = $palette[$color_index];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/header.css">
    <title>Header + Sidebar</title>
</head>
<body>
    <div class="header">
        <div class="left-container">
            <img src="../images/logo.png" alt="" class="logo">
            <div class="content">
                <h1>Hệ thống quản lý điểm sinh viên</h1>
                <p>Cổng quản trị viên</p>
            </div>
        </div>
        <div class="right-container">
            <div class="user-profile">
                <div class="avatar-circle" style="background-color: <?php echo $bg_color; ?>;">
                    <?php if (!empty($avatar_from_db) && file_exists($avatar_from_db)): ?>
                        <img src="<?php echo $avatar_from_db; ?>" alt="User Avatar">
                    <?php else: ?>
                        <span><?php echo $first_letter; ?></span>
                    <?php endif; ?>
                </div>
                <p class="greeting-text">
                    Xin chào, <strong><?php echo htmlspecialchars($full_name); ?></strong>
                </p>
            </div>
        </div>
    </div>

    <div id="userPopup" class="popup user-popup">
        <a href="../admin/profile.php">Thông tin cá nhân</a>
        <a href="../logout.php" class="logout">Đăng xuất</a>
    </div>

    <div class="sidebar">
        <ul>
            <li><a href="../admin/student.php">Quản lý sinh viên</a></li>
            <li><a href="../admin/teacher.php">Quản lý giảng viên</a></li>
            <li><a href="../admin/course.php">Quản lý học phần</a></li>
            <li><a href="../admin/class.php">Quản lý lớp học</a></li>
            <li><a href="../admin/calendar.php">Quản lý lịch thi</a></li>
            <li><a href="../admin/grade.php">Quản lý phúc khảo</a></li>
            <li><a href="../admin/major.php">Quản lý chuyên ngành</a></li>
            <li><a href="../admin/profile.php">Thông tin cá nhân</a></li>
        </ul>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const avatar = document.querySelector('.avatar-circle');
            const userPopup = document.getElementById('userPopup');
            const notiBtn = document.querySelector('.noti-btn');
            const notiPopup = document.getElementById('notiPopup');

            // Click avatar
            avatar.addEventListener('click', function (e) {
                e.stopPropagation();
                userPopup.classList.toggle('show');
                notiPopup.classList.remove('show');
            });

            // Click chuông
            notiBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                notiPopup.classList.toggle('show');
                userPopup.classList.remove('show');
            });

            // Click ra ngoài → đóng popup
            document.addEventListener('click', function () {
                userPopup.classList.remove('show');
                notiPopup.classList.remove('show');
            });

            // Ngăn đóng khi click bên trong popup
            userPopup.addEventListener('click', e => e.stopPropagation());
            notiPopup.addEventListener('click', e => e.stopPropagation());
        });
    </script>
</body>
</html>
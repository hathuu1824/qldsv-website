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
                <p>Cổng giảng viên</p>
            </div>
        </div>
        <div class="right-container">
            <div class="notification">
                <button class="noti-btn"><i class="fa-regular fa-bell"></i></button>
            </div>
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
        <a href="../teacher/profile.php">Thông tin cá nhân</a>
        <a href="../teacher/class.php">Lớp học</a>
        <a href="../logout.php" class="logout">Đăng xuất</a>
    </div>

    <div id="notiPopup" class="popup noti-popup">
        <div class="popup-header">
            <span class="popup-title">Thông báo</span>
            <button class="mark-as-read-btn" onclick="markAllAsRead()">Đánh dấu đã đọc</button>
        </div>
        <div class="popup-content">
            <?php 
            if (isset($result_popup) && $result_popup && $result_popup->num_rows > 0): 
                while($row = $result_popup->fetch_assoc()): 
            ?>
                <div class="noti-item" onclick="window.location.href='view_notification.php?id=<?= $row['id'] ?>'">
                    <div class="noti-item-body">
                        <p class="noti-item-title"><strong><?= htmlspecialchars($row['title']) ?></strong></p>
                        <p class="noti-item-time"><?= date('H:i - d/m/Y', strtotime($row['created_at'])) ?></p>
                    </div>
                </div>
            <?php 
                endwhile; 
            else: 
            ?>
                <div class="no-noti">Chưa có thông báo mới nào.</div>
            <?php endif; ?>
        </div>
        <div class="popup-footer">
            <a href="../student/notifications.php" class="view-all-link">Xem tất cả</a>
        </div>
     </div>

    <div class="sidebar">
        <ul>
            <li><a href="../teacher/home.php">Trang chủ</a></li>
            <li><a href="../teacher/profile.php">Thông tin cá nhân</a></li>
            <li><a href="../teacher/class.php">Quản lý lớp học</a></li>
            <li><a href="../teacher/student.php">Quản lý sinh viên</a></li>
            <li><a href="../teacher/grade.php">Quản lý phúc khảo</a></li>
            <li><a href="../teacher/calendar.php">Lịch & Sự kiện</a></li>
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
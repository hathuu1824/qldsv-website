<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require '../db_connection.php';
$account_id = $_SESSION['id'];
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tab = $_GET['tab'] ?? 'general'; 

$sql_class = "SELECT 
                c.id AS class_id, 
                c.class_code, 
                c.group_id,
                s.subject_name, 
                s.credit,
                s.total_sessions, 
                c.semester,
                CONCAT(p.last_name, ' ', p.first_name) AS lecturer_name,
                c.goals, c.weights, c.materials, c.online_link,

                -- 1. TRẠNG THÁI TỰ ĐỘNG (Tính toán real-time từ bảng attendance)
                CASE 
                    WHEN (SELECT COUNT(*) FROM attendance att 
                          JOIN class_sessions cs ON att.session_id = cs.id 
                          WHERE cs.class_id = c.id AND att.student_id = ? AND att.status = 'Có mặt') >= s.total_sessions THEN 'Hoàn thành'
                    WHEN (SELECT COUNT(*) FROM attendance att 
                          JOIN class_sessions cs ON att.session_id = cs.id 
                          WHERE cs.class_id = c.id AND att.student_id = ? AND att.status = 'Có mặt') > 0 THEN 'Đang học'
                    ELSE 'Chưa học'
                END AS status,

                -- 2. CÁC CHỈ SỐ ĐIỂM DANH
                (SELECT COUNT(*) FROM attendance att 
                 JOIN class_sessions cs ON att.session_id = cs.id 
                 WHERE cs.class_id = c.id AND att.student_id = ? AND att.status = 'Có mặt') AS attended_sessions,
                
                (SELECT COUNT(*) FROM attendance att 
                 JOIN class_sessions cs ON att.session_id = cs.id 
                 WHERE cs.class_id = c.id AND att.student_id = ? AND att.status = 'Muộn') AS late_sessions,
                
                (SELECT COUNT(*) FROM attendance att 
                 JOIN class_sessions cs ON att.session_id = cs.id 
                 WHERE cs.class_id = c.id AND att.student_id = ? AND att.status = 'Vắng có phép') AS excused_absences,
                
                (SELECT COUNT(*) FROM attendance att 
                 JOIN class_sessions cs ON att.session_id = cs.id 
                 WHERE cs.class_id = c.id AND att.student_id = ? AND att.status = 'Vắng không phép') AS unexcused_absences

              FROM classes c 
              JOIN subjects s ON c.subject_id = s.id 
              LEFT JOIN account a ON c.account_id = a.id AND a.role = 'teacher'
              LEFT JOIN profile p ON a.id = p.account_id
              WHERE c.id = ?";

$stmt = $conn->prepare($sql_class);

$stmt->bind_param("iiiiiii", $account_id, $account_id, $account_id, $account_id, $account_id, $account_id, $class_id); 
$stmt->execute();
$class_data = $stmt->get_result()->fetch_assoc();

if (!$class_data) {
    die("Lớp học không tồn tại!");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/class_detail.css">
    <title><?= htmlspecialchars($class_data['class_code']) ?> - Lớp học</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="main-container">
        <div class="class-header">
            <div class="class-title">
                <a href="class.php"><-</a>
                <h2><?= htmlspecialchars($class_data['subject_name']) ?></h2>
                <div class="class-status"><?= htmlspecialchars($class_data['status'] ?? '') ?></div>
            </div>
            <div class="class-info">
                <h4><strong>Mã học phần:</strong> <?= htmlspecialchars($class_data['class_code']) ?></h4>
                <h4><strong>Nhóm lớp:</strong> <?= htmlspecialchars($class_data['group_id'] ?? '1') ?></h4>
                <?php 
                    $sem_code = (int)$class_data['semester'];
                    $year_start = floor($sem_code / 10); 
                    $year_end = $year_start + 1;
                    $sem_num = $sem_code % 10;
                ?>
                <h4><strong>Kỳ học:</strong> HK<?= $sem_num ?> năm <?= $year_start ?> - <?= $year_end ?></h4>
                <h4><strong>Giảng viên:</strong> <?= htmlspecialchars($class_data['lecturer_name']) ?></h4>
            </div>
            <?php
            function generate_decor_svg($color = "#007bff") {
                return '
                <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="150" cy="50" r="40" fill="' . $color . '" fill-opacity="0.1" />
                    <rect x="20" y="80" width="100" height="100" rx="15" fill="' . $color . '" fill-opacity="0.05" transform="rotate(-15 70 130)" />
                    <path d="M100 20 L130 70 L70 70 Z" fill="' . $color . '" fill-opacity="0.1" />
                </svg>';
            }
            ?>
            <div class="header-decoration">
                <?= generate_decor_svg("#007bff") ?>
            </div>
        </div>

        <div class="class-nav">
            <a href="?id=<?= $class_id ?>&tab=general" class="<?= $tab == 'general' ? 'active' : '' ?>">Thông tin chung</a>
            <a href="?id=<?= $class_id ?>&tab=schedule" class="<?= $tab == 'schedule' ? 'active' : '' ?>">Lịch học</a>
            <a href="?id=<?= $class_id ?>&tab=notify" class="<?= $tab == 'notify' ? 'active' : '' ?>">Thông báo</a>
            <a href="?id=<?= $class_id ?>&tab=forum" class="<?= $tab == 'forum' ? 'active' : '' ?>">Diễn đàn</a>
            <a href="?id=<?= $class_id ?>&tab=homework" class="<?= $tab == 'homework' ? 'active' : '' ?>">Bài tập, bài kiểm tra</a>
            <a href="?id=<?= $class_id ?>&tab=result" class="<?= $tab == 'result' ? 'active' : '' ?>">Kết quả học tập</a>
        </div>
        <div class="tab-content">
            <?php 
                $allowed_tabs = [
                    'general'  => 'general.php',
                    'schedule' => 'schedule.php',
                    'notify'   => 'notifications.php',
                    'forum'    => 'forums.php',
                    'homework' => 'assignments.php',
                    'result'   => 'result.php'
                ];

                $tab_key = in_array($tab, array_keys($allowed_tabs)) ? $tab : 'general';
                $file_path = "tabs/" . $allowed_tabs[$tab_key];

                if (file_exists($file_path)) {
                    include $file_path;
                } else {
                    echo "<div class='alert alert-warning'>Hệ thống đang cập nhật nội dung cho mục này.</div>";
                }
            ?>
        </div>
    </main>
</body>
</html>
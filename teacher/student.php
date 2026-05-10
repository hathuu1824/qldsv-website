<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền truy cập của Giảng viên
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

require '../db_connection.php'; 
date_default_timezone_set('Asia/Ho_Chi_Minh');

$current_user_id = $_SESSION['id']; 

// 2. Lấy danh sách lớp học dựa trên account_id của giảng viên
$sql_classes = "SELECT id, class_name FROM classes WHERE account_id = '$current_user_id' ORDER BY class_name ASC";
$classes_res = mysqli_query($conn, $sql_classes);
$my_classes = [];
while($c = mysqli_fetch_assoc($classes_res)) {
    $my_classes[] = $c;
}

// 3. Xử lý bộ lọc Lớp và Tìm kiếm
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$class_filter = isset($_GET['class_id']) ? mysqli_real_escape_string($conn, $_GET['class_id']) : '';

if (empty($class_filter) && !empty($my_classes)) {
    $class_filter = $my_classes[0]['id'];
}

// 4. Truy vấn sinh viên (JOIN account + profile + class_members)
$sql_sv = "SELECT a.id, a.code, p.first_name, p.last_name, p.email, p.phone, p.gender, p.avatar,
               CONCAT_WS(' ', p.last_name, p.first_name) AS fullname 
        FROM account a
        INNER JOIN profile p ON a.id = p.account_id 
        INNER JOIN class_members cm ON a.id = cm.student_id 
        WHERE a.role = 'student' AND cm.class_id = '$class_filter'"; 

if ($search !== '') {
    $sql_sv .= " AND (p.first_name LIKE '%$search%' 
                OR p.last_name LIKE '%$search%' 
                OR a.code LIKE '%$search%')";
}

$sql_sv .= " ORDER BY p.first_name ASC, p.last_name ASC";
$result_sv = mysqli_query($conn, $sql_sv);
$total_students = mysqli_num_rows($result_sv);

$query_total_ss = "SELECT COUNT(*) as total FROM class_sessions WHERE class_id = '$class_filter'";
$res_total_ss = mysqli_query($conn, $query_total_ss) or die(mysqli_error($conn));
$total_sessions = mysqli_fetch_assoc($res_total_ss)['total'] ?: 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Quản lý sinh viên</title>
    <style>
        /* CSS bổ sung cho phần cảnh báo */
        .badge-danger { background: #ff4757; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; animation: pulse 2s infinite; }
        .badge-success { background: #2ed573; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .text-danger { color: #ff4757; font-weight: bold; }
        .row-warning { background-color: #fff5f5 !important; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        .info-note { font-size: 13px; color: #666; margin-bottom: 10px; font-style: italic; }
    </style>
</head>
<body>
    <?php include 'header.php' ?>
    <main class="main-container">
        <div class="title-container">
            <h2>Quản lý sinh viên</h2>
            <div class="filter-group">
                <select id="class-select" onchange="window.location.href='?class_id=' + this.value" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                    <?php if(empty($my_classes)): ?>
                        <option value="">Không có lớp phân công</option>
                    <?php else: ?>
                        <?php foreach($my_classes as $class): ?>
                            <option value="<?= $class['id'] ?>" <?= ($class_filter == $class['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="table-container">
            <div class="table-card">
                <div class="table-title">
                    <div class="left">
                        <h3>Sĩ số: <span class="sv-count"><?= $total_students ?></span></h3>
                    </div>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="hidden" name="class_id" value="<?= $class_filter ?>">
                            <input type="text" name="search" placeholder="Tìm tên hoặc mã SV..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm kiếm</button>
                        </form>
                        <button class="btn-add" onclick="window.print()" style="background: #2f3542;">In danh sách</button>
                    </div>
                </div>

                <div class="sv-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>MSV</th>
                                <th>Họ và tên</th>
                                <th style="text-align: center;">Vắng / Muộn</th>
                                <th style="text-align: center;">Tỉ lệ nghỉ</th>
                                <th style="text-align: center;">Tình trạng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($total_students > 0): 
                                $stt = 1; 
                                while ($row = mysqli_fetch_assoc($result_sv)): 
                                    $sid = $row['id'];
                                    $sql_count = "SELECT 
                                                    SUM(CASE WHEN a.status LIKE 'Vắng%' THEN 1 ELSE 0 END) as v,
                                                    SUM(CASE WHEN a.status LIKE 'Muộn%' OR a.status LIKE '%Về sớm%' THEN 1 ELSE 0 END) as m
                                                FROM attendance a
                                                INNER JOIN class_sessions cs ON a.session_id = cs.id
                                                WHERE a.student_id = '$sid' AND cs.class_id = '$class_filter'";
                                    $res_count = mysqli_query($conn, $sql_count);
                                    if (!$res_count) {
                                        die("Lỗi SQL dòng 134: " . mysqli_error($conn));
                                    }
                                    $c = mysqli_fetch_assoc($res_count);
                                    $so_vắng = $c['v'] ?: 0;
                                    $so_muộn = $c['m'] ?: 0;
                                    $vắng_quy_đổi = $so_vắng + floor($so_muộn / 2);
                                    $tile = ($total_sessions > 0) ? ($vắng_quy_đổi / $total_sessions) * 100 : 0;
                                    $is_danger = ($tile >= 20);
                            ?>
                                <tr class="<?= $is_danger ? 'row-warning' : '' ?>">
                                    <td style="text-align: center;"><?= $stt++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['code']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    
                                    <td style="text-align: center;">
                                        <b style="color: #ff4757;"><?= $so_vắng ?>V</b> - <b style="color: #ffa502;"><?= $so_muộn ?>M</b>
                                    </td>

                                    <td style="text-align: center;" class="<?= $is_danger ? 'text-danger' : '' ?>">
                                        <?= $vắng_quy_đổi ?> buổi (<?= round($tile, 1) ?>%)
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($is_danger): ?>
                                            <span class="badge-danger">Cấm thi</span>
                                        <?php else: ?>
                                            <span class="badge-success">Đủ điều kiện</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="student_profile.php?id=<?= $row['id'] ?>" class="btn-edit" style="background: #57606f;">Hồ sơ</a>
                                            <button class="btn-edit" onclick="window.location.href='mailto:<?= $row['email'] ?>'">Gửi Mail</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #888;">
                                        Không tìm thấy sinh viên nào trong lớp này.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="info-note" style="margin-top: 10px;">* Quy ước: 2 buổi muộn = 1 buổi vắng. Cảnh báo khi nghỉ >= 20%.</p>
            </div>
        </div>
    </main>
</body>
</html>
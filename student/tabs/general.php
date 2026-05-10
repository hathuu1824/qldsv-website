<?php
$sql_members = "SELECT 
                    a.username, p.avatar, a.id AS account_id,
                    CONCAT(p.last_name, ' ', p.first_name) AS full_name,
                    a.code AS account_code
                FROM account a
                JOIN class_members cm ON a.id = cm.student_id
                JOIN profile p ON a.id = p.account_id
                WHERE cm.class_id = ? AND a.role = 'student'
                ORDER BY p.first_name ASC, p.last_name ASC";
$stmt_members = $conn->prepare($sql_members);
if (!$stmt_members) {
    die("Lỗi SQL Danh sách SV: " . $conn->error); 
}
$stmt_members->bind_param("i", $class_id);
$stmt_members->execute();
$members = $stmt_members->get_result();

$sql_assign = "SELECT * FROM assignments WHERE class_id = ? ORDER BY deadline ASC";
$stmt_assign = $conn->prepare($sql_assign);
if (!$stmt_assign) {
    die("Lỗi tại SQL Assignments: " . $conn->error . ". Vui lòng kiểm tra lại tên cột trong bảng assignments.");
}
$stmt_assign->bind_param("i", $class_id);
$stmt_assign->execute();
$assignments = $stmt_assign->get_result();

$sql_notif = "SELECT * FROM notifications WHERE class_id = ? ORDER BY created_at DESC";
$stmt_notif = $conn->prepare($sql_notif);
if (!$stmt_notif) {
    die("Lỗi tại SQL Notifications: " . $conn->error);
}
$stmt_notif->bind_param("i", $class_id);
$stmt_notif->execute();
$notifications = $stmt_notif->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="class-content">
        <div class="wrapper-left">
            <div class="class-detail">
                <div class="detail-title">
                    <h3>Giới thiệu</h3>
                    <a href="javascript:void(0)" onclick="openClassModal('tab-links')">Học trực tuyến</a>
                </div>
                <hr>
                <div class="detail-content">
                    <div class="goal-title">
                        <h4>Mục tiêu</h4>
                        <a href="javascript:void(0)" onclick="openClassModal('tab-goals')">Xem chi tiết</a>
                    </div>
                    <div class="goal-content" id="goal-content-text"><?= nl2br(htmlspecialchars($class_data['goals'] ?? '')) ?></div>
                </div>
                <div class="detail-content">
                    <div class="weight-title">
                        <h4>Trọng số</h4>
                        <a href="javascript:void(0)" onclick="openClassModal('tab-weights')">Xem chi tiết</a>
                    </div>
                    <div class="weight-content" id="weight-content-text"><?= nl2br(htmlspecialchars($class_data['weights'] ?? '')) ?></div>
                </div>
                <div class="detail-content">
                    <div class="ref-title">
                        <h4>Học liệu</h4>
                        <a href="javascript:void(0)" onclick="openClassModal('tab-materials')">Xem chi tiết</a>
                    </div>
                    <div class="ref-content" id="ref-content-text"><?= nl2br(htmlspecialchars($class_data['materials'] ?? '')) ?></div>
                </div>
            </div>
            <div class="class-activities">
                <div class="class-assignments">
                    <h3>Bài tập</h3>
                    <div class="activity-card">
                        <?php if ($assignments->num_rows > 0): ?>
                            <?php 
                                $index = 0; 
                                while($assign = $assignments->fetch_assoc()): 
                            ?>
                                <div class="assignment-item" 
                                    data-index="<?= $index; ?>" 
                                    style="display: <?= $index === 0 ? 'block' : 'none'; ?>; cursor: pointer;"
                                    onclick="window.location.href='assignments.php?id=<?= $assign['id']; ?>'">
                                    
                                    <div class="assign-info">
                                        <strong style="display: block; color: #2c3e50; font-size: 1.1em; margin-bottom: 5px;">
                                            <?= htmlspecialchars($assign['title']); ?>
                                        </strong>
                                        <div class="assign-meta" style="font-size: 0.85em; color: #e74c3c; font-weight: bold;">
                                            <i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($assign['deadline'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php $index++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="padding: 20px; color: #999; font-style: italic;">Chưa có bài tập nào.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="class-notifications">
                    <h3>Thông báo mới</h3>
                    <div class="activity-card">
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while($row = $notifications->fetch_assoc()): ?>
                                <div class="notif-item">
                                    <strong><?= $row['created_at'] ?>:</strong> <?= htmlspecialchars($row['content']) ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Không có thông báo nào.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="wrapper-right">
            <div class="class-members">
                <h3>Danh sách sinh viên</h3>
                <div class="members-card">
                    <?php while($row = $members->fetch_assoc()): ?>
                        <?php 
                            $display_name = !empty($row['full_name']) ? $row['full_name'] : ($row['code'] ?? 'S');
                            $first_letter = mb_strtoupper(mb_substr($display_name, 0, 1, 'UTF-8'), 'UTF-8');

                            $palette = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c', '#34495e'];
                            $color_index = $row['account_id'] % count($palette);
                            $bg_color = $palette[$color_index];

                            $avatar_url = "../uploads/" . $row['avatar'];
                            $has_avatar = !empty($row['avatar']) && file_exists($avatar_url);
                        ?>
                        <div class="members-info">
                            <div class="avatar-circle" style="background-color: <?= $bg_color; ?>;">
                                <?php if (!empty($avatar_from_db) && file_exists($avatar_from_db)): ?>
                                    <img src="<?= $avatar_from_db; ?>" alt="User Avatar">
                                <?php else: ?>
                                    <span><?= $first_letter; ?></span>
                                <?php endif; ?>
                            </div>
                            <ul>
                                <li><?= htmlspecialchars($row['full_name']) ?></li>
                                <li><?= htmlspecialchars($row['account_code']) ?></li>
                            </ul>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include 'modal_class.php'; ?>
</body>
</html>
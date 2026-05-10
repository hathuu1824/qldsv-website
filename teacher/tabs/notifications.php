<?php 
$sql_notif = "SELECT * FROM notifications WHERE class_id = ? ORDER BY created_at DESC";
$stmt_notif = $conn->prepare($sql_notif);

if (!$stmt_notif) {
    die("<div class='alert'>Lỗi hệ thống: " . htmlspecialchars($conn->error) . "</div>");
}

$stmt_notif->bind_param("i", $class_id);
$stmt_notif->execute();
$notif_result = $stmt_notif->get_result();
$total_notif = $notif_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=`device-width`, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="noti-content">
        <div class="noti-card">
            <div class="noti-title">
                <h3>Tổng số thông báo: <span class="notif-count"><?= $total_notif ?></span></h3>
                <div class="notify-btn">
                    <button onclick="window.location.reload();" class="btn-reload">Tải lại</button>
                    <button class="btn-read-all">Đánh dấu đã đọc</button>
                </div>
            </div>
            <div class="noti-table">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">STT</th>
                            <th>Tiêu đề</th>
                            <th>Mô tả</th>
                            <th style="width: 200px;">Thời gian gửi</th>
                            <th style="width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($total_notif > 0): ?>
                        <?php 
                        $stt = 1; 
                        while($row = $notif_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td style="text-align: center;"><?= $stt++ ?></td>
                                <td class="notif-subject"><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                <td class="notif-desc">
                                    <?= mb_strimwidth(htmlspecialchars($row['content']), 0, 100, "...") ?>
                                </td>
                                <td class="notif-time">
                                    <?= date('H:i | d/m/Y', strtotime($row['created_at'])) ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="view_notification.php?id=<?= $row['id'] ?>" class="btn-view">Xem chi tiết</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: #64748b;">
                                Hiện tại chưa có thông báo nào cho lớp học này.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$sql = "SELECT cs.*, IFNULL(att.status, 'Chưa điểm danh') AS attendance_status 
        FROM class_sessions cs
        LEFT JOIN attendance att ON cs.id = att.session_id AND att.student_id = ?
        WHERE cs.class_id = ?
        ORDER BY cs.session_number ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $account_id, $class_id);
$stmt->execute();
$tab_data = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="schedule-content">
        <div class="session-card">
            <div class="card-title">
                <h3>Thống kê buổi học</h3>
            </div>
            <div class="stat-grid">
                <div class="stat-item">
                    <h4>Tổng số buổi</h4>
                    <div class="stat-number"><?= $class_data['total_sessions'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <h4>Có mặt</h4>
                    <div class="stat-number" style="color: #20c60d;"><?= $class_data['attended_sessions'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <h4>Muộn/Về sớm</h4>
                    <div class="stat-number" style="color: #2980b9;"><?= $class_data['late_sessions'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <h4>Nghỉ có phép</h4>
                    <div class="stat-number" style="color: #f2e372;"><?= $class_data['excused_absences'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <h4>Nghỉ không phép</h4>
                    <div class="stat-number" style="color: #f39c12;"><?= $class_data['unexcused_absences'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <h4>Chưa điểm danh</h4>
                    <div class="stat-number" style="color: #e74c3c;">
                        <?php 
                        $total = $class_data['total_sessions'] ?? 0;
                        $att = $class_data['attended_sessions'] ?? 0;
                        $late = $class_data['late_sessions'] ?? 0;
                        $excused = $class_data['excused_absences'] ?? 0;
                        $unexcused = $class_data['unexcused_absences'] ?? 0;
                        $recorded = $att + $late + $excused + $unexcused;
                        echo max(0, $total - $recorded);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="session-table">
            <div class="class-members">
                <h3>Danh sách buổi học</h3>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Ngày học</th>
                            <th>Tiết học</th>
                            <th>Hình thức</th>
                            <th>Trạng thái</th>
                            <th>Nội dung</th>
                            <th>Học liệu</th>
                            <th>Nghỉ phép</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tab_data->num_rows > 0): ?>
                            <?php while($row = $tab_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['session_number']) ?></td>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                    <td><?= htmlspecialchars($row['attendance_status']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3">Chưa có buổi học nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$sql = "SELECT 
            cs.*, 
            -- Đếm số lượng sinh viên đã được lưu điểm danh trong buổi này
            (SELECT COUNT(*) FROM attendance WHERE session_id = cs.id) as total_attended,
            -- Đếm số đơn nghỉ phép của sinh viên trong ngày học này
            (SELECT COUNT(*) FROM leave_requests 
             WHERE class_id = cs.class_id 
             AND date = cs.date 
             AND status = 'Approved') as total_leave
        FROM class_sessions cs
        WHERE cs.class_id = ?
        ORDER BY cs.session_number ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
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
        <div class="session-table">
            <div class="class-members">
                <div class="noti-title">
                    <h3>Danh sách điểm danh</h3>
                    <div class="notify-btn">
                        <button onclick="window.location.reload();" class="btn-reload">Tải lại</button>
                    </div>
                </div>
                <table style="margin-top: 0;">
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
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align: center;"><?= $row['session_number'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                                    <td>Tiết <?= htmlspecialchars($row['period']) ?></td>
                                    <td>
                                        <span class="badge <?= $row['mode'] == 'Online' ? 'bg-info' : 'bg-secondary' ?>">
                                            <?= $row['mode'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['total_attended'] > 0): ?>
                                            <span style="color: #28a745; font-weight: bold;">Đã điểm danh</span>
                                        <?php else: ?>
                                            <span style="color: #dc3545; font-weight: bold;">Chưa điểm danh</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['content'] ?? 'Chưa cập nhật') ?></td>
                                    <td>
                                        <?php if(!empty($row['document_link'])): ?>
                                            <a href="<?= $row['document_link'] ?>" target="_blank" title="Xem học liệu">Xem</a>
                                            <span style="cursor:pointer; color: #007bff;" onclick="openEditSessionModal(<?= $row['id'] ?>, '<?= addslashes($row['content']) ?>', '<?= $row['document_link'] ?>')"> (Sửa)</span>
                                        <?php else: ?>
                                            <button class="btn-add-doc" onclick="openEditSessionModal(<?= $row['id'] ?>)" style="color: #007bff; border: none; background: none; cursor: pointer;">
                                                Thêm
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($row['total_leave'] > 0): ?>
                                            <button onclick="loadStudentList(<?= $row['id'] ?>, <?= $row['session_number'] ?>, '<?= $row['date'] ?>', <?= $class_id ?>)" style="background: #ffc107; border: none; border-radius: 4px; padding: 2px 8px; cursor: pointer;">
                                                <?= $row['total_leave'] ?> đơn
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #999999;">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-take-attendance" 
                                                onclick="loadStudentList(<?= $row['id'] ?>, <?= $row['session_number'] ?>, '<?= $row['date'] ?>', <?= $class_id ?>)"
                                                style="background: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                            Điểm danh
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align: center;">Chưa có lịch trình buổi học.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="attendance-detail-section" class="schedule-content" style="display: none;">
        <input type="hidden" id="current-session-id" name="session_id">
        <input type="hidden" id="current-session-date"> <input type="hidden" id="current-class-id">
        <div class="session-table">
            <div class="class-members">
                <div class="noti-title">
                    <h3 id="attendance-title">Điểm danh sinh viên</h3>
                    <div class="notify-btn">
                        <button type="button" class="btn-reload" onclick="viewLeaveRequests()">Đơn nghỉ phép</button>
                        <button type="button" class="btn-read-all" onclick="submitAttendance()">Lưu</button>
                    </div>
                </div>
                <form id="attendance-form">
                    <input type="hidden" id="current-session-id" name="session_id">
                    <table style="margin-top: 0;">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>MSV</th>
                                <th>Họ và tên</th>
                                <th>Lớp</th>
                                <th>Hình thức</th>
                                <th>Trạng thái</th>
                                <th style="text-align: center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="student-list-body">
                            </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEditSession" class="modal-new" style="display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center;">
        <div style="background: white; margin: 10% auto; padding: 25px; width: 450px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); position: relative;">
            <h3 style="margin-top: 0; color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px;">Cập nhật buổi học</h3>
            <form action="process/update_session.php" method="POST">
                <input type="hidden" name="session_id" id="edit_session_id">
                <div style="margin-top: 15px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Nội dung bài học:</label>
                    <textarea name="content" id="edit_content" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;" rows="3"></textarea>
                </div>
                <div style="margin-top: 15px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Link học liệu:</label>
                    <input type="url" name="document_link" id="edit_document_link" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;" placeholder="https://drive.google.com/...">
                </div>
                <div style="margin-top: 25px; text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeSessionModal()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Hủy</button>
                    <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
    <?php include 'modal_class.php'; ?>                        
</body>
</html>
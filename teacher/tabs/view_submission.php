<?php
require_once '../db_connection.php'; // Điều chỉnh đường dẫn cho đúng
date_default_timezone_set('Asia/Ho_Chi_Minh');

$assignment_id = $_GET['id'] ?? 0;

// 1. Lấy thông tin chi tiết bài tập
$sql_info = "SELECT a.*, c.class_name 
             FROM assignments a 
             JOIN classes c ON a.class_id = c.id 
             WHERE a.id = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $assignment_id);
$stmt_info->execute();
$assign = $stmt_info->get_result()->fetch_assoc();

if (!$assign) {
    die("Không tìm thấy bài tập!");
}

// 2. Lấy danh sách sinh viên đã nộp bài (Sử dụng bảng nguoidung hoặc sinhvien tùy DB của bạn)
// Giả sử bảng người dùng của bạn là 'nguoidung'
$sql_list = "SELECT s.*, u.hoten, u.id as user_code
             FROM submissions s
             JOIN nguoidung u ON s.student_id = u.id
             WHERE s.assignment_id = ?
             ORDER BY s.submitted_at DESC";
$stmt_list = $conn->prepare($sql_list);
$stmt_list->bind_param("i", $assignment_id);
$stmt_list->execute();
$list_result = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chấm bài: <?= htmlspecialchars($assign['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; }
        .header-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .back-link { text-decoration: none; color: #007bff; font-weight: bold; }
        
        .table-container { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .status-graded { background: #e2f3e5; color: #1d7c33; }
        .status-pending { background: #fff4e5; color: #b06904; }
        
        .btn-grade { background: #007bff; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-size: 14px; }
        .btn-grade:hover { background: #0056b3; }
        
        .file-link { color: #d93025; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-card">
        <a href="class_detail.php?id=<?= $assign['class_id'] ?>&tab=homework" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại lớp học
        </a>
        <h2 style="margin: 15px 0 5px 0;"><?= htmlspecialchars($assign['title']) ?></h2>
        <p style="color: #666; margin: 0;">Lớp: <strong><?= htmlspecialchars($assign['class_name']) ?></strong> | Hạn chót: <?= date('d/m/Y H:i', strtotime($assign['deadline'])) ?></p>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Sinh viên</th>
                    <th>Thời gian nộp</th>
                    <th>File đính kèm</th>
                    <th>Trạng thái</th>
                    <th>Điểm</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($list_result->num_rows > 0): ?>
                    <?php $stt = 1; while($row = $list_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $stt++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['hoten']) ?></strong><br>
                                <small style="color: #888;">ID: <?= $row['user_code'] ?></small>
                            </td>
                            <td><?= date('H:i d/m/Y', strtotime($row['submitted_at'])) ?></td>
                            <td>
                                <?php if ($row['file_path']): ?>
                                    <a href="<?= $row['file_path'] ?>" target="_blank" class="file-link">
                                        <i class="fas fa-file-download"></i> Tải bài
                                    </a>
                                <?php else: ?>
                                    <span style="color: #ccc;">Không có file</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['grade'] !== NULL): ?>
                                    <span class="status-badge status-graded">Đã chấm</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Chưa chấm</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: bold; color: #d93025;">
                                <?= ($row['grade'] !== NULL) ? $row['grade'] : '--' ?>
                            </td>
                            <td>
                                <a href="grade_detail.php?submission_id=<?= $row['id'] ?>" class="btn-grade">
                                    <i class="fas fa-edit"></i> Chấm bài
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #888;">
                            <i class="fas fa-inbox" style="font-size: 30px; display: block; margin-bottom: 10px;"></i>
                            Chưa có sinh viên nào nộp bài.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
<?php
require 'db_connection.php'; // Chỉnh đường dẫn cho đúng
session_start();

// Giả sử bạn đã lưu student_id vào session khi đăng nhập
$student_id = $_SESSION['user_id'] ?? 1; 
$assignment_id = $_GET['id'] ?? 0;

// 1. Lấy thông tin đề bài
$sql = "SELECT a.*, c.class_name 
        FROM assignments a 
        JOIN classes c ON a.class_id = c.id 
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    die("Không tìm thấy bài tập!");
}

// 2. Kiểm tra xem đã nộp bài chưa
$sql_check = "SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $assignment_id, $student_id);
$stmt_check->execute();
$already_submitted = $stmt_check->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nộp bài: <?= htmlspecialchars($assignment['title']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .submit-container { max-width: 800px; margin: 30px auto; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .assign-info { border-bottom: 2px solid #f0f0f0; margin-bottom: 20px; padding-bottom: 15px; }
        .deadline-tag { color: #dc3545; font-weight: bold; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; resize: vertical; }
        .btn-submit { background: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #218838; }
        .submitted-msg { padding: 15px; background: #e2f3e5; color: #155724; border-radius: 8px; }
    </style>
</head>
<body>

<div class="submit-container">
    <div class="assign-info">
        <a href="student_dashboard.php" style="text-decoration: none; color: #007bff;">← Quay lại</a>
        <h2 style="color: #333; margin-top: 15px;"><?= htmlspecialchars($assignment['title']) ?></h2>
        <p><strong>Lớp:</strong> <?= htmlspecialchars($assignment['class_name']) ?></p>
        <p><strong>Yêu cầu:</strong> <?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
        <p class="deadline-tag">Hạn chót: <?= date('H:i | d/m/Y', strtotime($assignment['deadline'])) ?></p>
    </div>

    <?php if ($already_submitted): ?>
        <div class="submitted-msg">
            <p><strong>Trạng thái:</strong> Bạn đã nộp bài này vào lúc <?= date('H:i d/m/Y', strtotime($already_submitted['submitted_at'])) ?>.</p>
            <?php if ($already_submitted['grade']): ?>
                <p><strong>Điểm:</strong> <?= $already_submitted['grade'] ?>/10</p>
                <p><strong>Phản hồi:</strong> <?= htmlspecialchars($already_submitted['feedback']) ?></p>
            <?php else: ?>
                <p><em>Bài làm đang chờ giáo viên chấm.</em></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <form action="process_submission.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
            <input type="hidden" name="student_id" value="<?= $student_id ?>">

            <div class="form-group">
                <label>Nội dung bài làm (văn bản):</label>
                <textarea name="submission_text" rows="6" placeholder="Nhập câu trả lời của bạn tại đây..."></textarea>
            </div>

            <div class="form-group">
                <label>Đính kèm File (nếu có):</label>
                <input type="file" name="submission_file">
                <p style="font-size: 12px; color: #666;">Hỗ trợ: pdf, docx, zip, jpg, png (Max 5MB)</p>
            </div>

            <button type="submit" class="btn-submit">Nộp bài tập</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
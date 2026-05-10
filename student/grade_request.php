<?php
require_once 'config/cf_class.php';
$semesters_list = $semesters_list ?? [];
$selected_sem = $_GET['semester'] ?? ($semesters_list[0]['semester'] ?? 1);

$sql_user_info = "SELECT p.*, a.username, m.major_name, f.faculty_name, a.code
                  FROM profile p 
                  JOIN account a ON p.account_id = a.id 
                  JOIN majors m ON p.major_id = m.id
                  JOIN faculties f ON p.faculty_id = f.id
                  WHERE p.account_id = ?";
$stmt_user = $conn->prepare($sql_user_info);
$stmt_user->bind_param("i", $account_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();

$today = date('Y-m-d');

$sql_pk_list = "SELECT s.subject_code, s.subject_name, cr.score_final, c.deadline 
                FROM classes c
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_results cr ON c.id = cr.class_id
                WHERE cr.account_id = ? AND c.semester = ?";

$stmt_pk = $conn->prepare($sql_pk_list);
$stmt_pk->bind_param("ii", $account_id, $selected_sem);
$stmt_pk->execute();
$pk_list = $stmt_pk->get_result()->fetch_all(MYSQLI_ASSOC);

$sql_reexam = "SELECT r.*, s.subject_name, g.score_final, e.exam_date 
               FROM re_exam_requests r
               JOIN subjects s ON r.subject_id = s.id
               JOIN exam_sessions e ON r.exam_date
               JOIN class_results g ON r.grade_id = g.id
               WHERE r.account_id = ?
               ORDER BY r.created_at DESC";
$stmt_re = $conn->prepare($sql_reexam);
$stmt_re->bind_param("i", $account_id);
$stmt_re->execute();
$history_list = $stmt_re->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/grade_request.css">
    <title>Đăng ký phúc khảo</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="main-container">
        <div class="pk-title">
            <h2>Đăng ký phúc khảo</h2>
            <div class="filter-group">
                <form method="GET" action="">
                    <select name="semester" id="semester-select" onchange="this.form.submit()">
                        <?php 
                        foreach ($semesters_list as $sem_item): 
                            $sem_code = (int)$sem_item['semester']; 
                            $year_start = floor($sem_code / 10); 
                            $year_end = $year_start + 1;
                            $sem_display = $sem_code % 10;
                        ?>
                            <option value="<?= $sem_code ?>" <?= ($selected_sem == $sem_code) ? 'selected' : '' ?>>
                                Học kỳ <?= $sem_display ?> - Năm học <?= $year_start ?> - <?= $year_end ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="pk-content">
            <div class="pk-left">
                <?php 
                $active_pk_list = [];
                if (!empty($pk_list)) {
                    foreach ($pk_list as $item) {
                        if (strtotime($today) <= strtotime($item['deadline'])) {
                            $active_pk_list[] = $item;
                        }
                    }
                }
                ?>
                <div class="card">
                    <?php if (empty($active_pk_list)): ?>
                        <div style="padding: 20px; text-align: center; color: #666666;">
                            <i class="fas fa-calendar-times"></i> Hiện tại chưa có lịch phúc khảo.
                        </div>
                    <?php else: ?>
                        <table class="pk-table">
                            <thead>
                                <tr>
                                    <th>Mã học phần</th>
                                    <th>Tên học phần</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_pk_list as $item): 
                                    $is_score_ok = ($item['score_final'] < 5);
                                ?>
                                    <tr class="<?= $is_score_ok ? 'clickable-row' : 'disabled-row' ?>" 
                                        <?php if ($is_score_ok): ?>
                                            onclick="selectForAppeal('<?= htmlspecialchars($item['subject_name']) ?>', '<?= $item['score_final'] ?>', '<?= $item['deadline'] ?>')"
                                        <?php endif; ?>>
                                        <td><?= htmlspecialchars($item['subject_code']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($item['subject_name']) ?>
                                            <?php if (!$is_score_ok): ?>
                                                <span style="color: #999999; font-size: 0.8rem; margin-left: 5px;">(Không đủ ĐK)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <?php 
                $has_ineligible_score = false;
                foreach ($active_pk_list as $item) {
                    if ($item['score_final'] >= 5) {
                        $has_ineligible_score = true;
                        break;
                    }
                }
                if ($has_ineligible_score): ?>
                    <div class="pk-alert" style="margin-top: 10px; padding: 10px; border-radius: 5px; background-color: #fff4f4; border: 1px solid #ffebeb; color: #d9534f; font-size: 0.85rem;">
                        <i class="fas fa-info-circle"></i> Một số môn học không đủ điều kiện phúc khảo do điểm thi của bạn từ 5.0 trở lên.
                    </div>
                <?php endif; ?>
                <div id="form-section" class="hcard">
                    <h3>Đơn xin phúc khảo</h3>
                    <form id="pk-form" action="grade_record.php" method="POST">
                        <div class="user-static-info">
                            <div class="user-info">
                                <p><strong>Họ và tên:</strong> <?= $user_info['last_name'] . ' ' . $user_info['first_name'] ?></p>
                                <p><strong>Mã sinh viên:</strong> <?= $user_info['code'] ?></p>
                            </div>
                            <p><strong>Chuyên ngành:</strong> <?= $user_info['major_name'] ?? 'N/A' ?></p>
                            <p><strong>Khoa:</strong> <?= $user_info['faculty_name'] ?? 'N/A' ?></p>
                        </div>
                        <div class="form-group">
                            <label>Học phần:</label>
                            <input type="text" id="selected-subject" readonly placeholder="Vui lòng chọn môn bên trái">
                            <label>Điểm:</label>
                            <input type="text" id="current-grade" readonly placeholder="Điểm hiện tại">
                        </div>
                        <div class="form-group">
                            <label>Ngày thi:</label>
                            <input type="date" id="exam-date">
                        </div>
                        <div class="form-group">
                            <label>Lý do phúc khảo:</label>
                            <textarea rows="5" placeholder="Nhập lý do cụ thể..." name="reason"></textarea>
                        </div>
                        <div class="form-button">
                            <button type="submit" class="btn-submit">Xác nhận</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="pk-right">
                <div class="card">
                    <h3>Lịch sử phúc khảo</h3>
                    <table class="h-table">
                        <thead>
                            <tr>
                                <th>Học phần</th>
                                <th>Điểm</th>
                                <th>Ngày thi</th>
                                <th>Lý do</th>
                                <th>Trạng thái</th>
                                <th>Ngày ĐK</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history_list)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #999; padding: 20px;">
                                        Bạn chưa đăng ký phúc khảo học phần nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history_list as $h): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($h['subject_name']) ?></td>
                                        <td><b><?= $h['score'] ?></b></td>
                                        <td><?= date('d/m/Y', strtotime($h['exam_date'])) ?></td>
                                        <td style="max-width: 200px; font-style: italic;">
                                            <?= htmlspecialchars($h['reason'] ?? $h['note']) ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $status_class = ($h['status'] == 'Đã xử lý') ? 'text-success' : (($h['status'] == 'Bị từ chối') ? 'text-danger' : 'text-warning');
                                            ?>
                                            <span class="<?= $status_class ?>" style="font-weight: bold;">
                                                <?= htmlspecialchars($h['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script src="../javascript/request.js"></script>
</body>
</html>
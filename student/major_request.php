<?php
require_once 'config/cf_class.php';

$config_reg = ['min_year' => 3];

$sql_user = "SELECT p.*, a.username, f.faculty_name, f.id as f_id, a.code
             FROM profile p 
             JOIN account a ON p.account_id = a.id 
             JOIN faculties f ON p.faculty_id = f.id
             WHERE p.account_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $account_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();

$faculty_id = $user_info['f_id'] ?? 0;
$student_faculty = $user_info['faculty_name'] ?? "Chưa xác định";

$student_year = date('Y') - ($user_info['entry_year'] ?? 2023) + 1;
$is_year_ok = ($student_year >= $config_reg['min_year']);

$sql_majors = "SELECT m.*, f.date_start, f.date_end 
               FROM majors m 
               JOIN faculties f ON m.faculty_id = f.id 
               WHERE m.faculty_id = ?";
$stmt_majors = $conn->prepare($sql_majors);
$stmt_majors->bind_param("i", $faculty_id);
$stmt_majors->execute();
$majors_list = $stmt_majors->get_result()->fetch_all(MYSQLI_ASSOC);

$sql_history = "SELECT mr.*, m.major_name, m.major_code 
                FROM major_registrations mr
                JOIN majors m ON mr.major_id = m.id
                WHERE mr.account_id = ?
                ORDER BY mr.created_at DESC";
$stmt_h = $conn->prepare($sql_history);
$stmt_h->bind_param("i", $account_id);
$stmt_h->execute();
$history_list = $stmt_h->get_result()->fetch_all(MYSQLI_ASSOC);

$today = date('Y-m-d');
$can_submit_global = $is_year_ok; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/grade_request.css"> 
    <title>Đăng ký chuyên ngành</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="main-container">
        <div class="pk-title">
            <h2>Đăng ký chuyên ngành</h2>
            <div class="filter-group">
                <select id="faculty-select">
                    <option value=""><?php echo htmlspecialchars($student_faculty); ?></option>
                </select>
            </div>
        </div>

        <div class="pk-content">
            <div class="pk-left">
                <div class="card">
                    <?php if (!$is_year_ok): ?>
                        <div style="padding: 20px; text-align: center; color: #f39c12; background: #fff9f0; border: 1px solid #ffeeba; border-radius: 5px;">
                            <i class="fas fa-user-graduate"></i> 
                            Bạn hiện là sinh viên năm <?= $student_year ?>. 
                            Đăng ký chuyên ngành chỉ dành cho sinh viên từ năm <?= $config_reg['min_year'] ?> trở lên.
                        </div>
                    <?php elseif (empty($majors_list)): ?>
                        <div style="padding: 20px; text-align: center; color: #666;">
                            <i class="fas fa-folder-open"></i> Chưa có danh sách chuyên ngành cho Khoa này.
                        </div>
                    <?php else: ?>
                        <table class="pk-table">
                            <thead>
                                <tr>
                                    <th>Mã chuyên ngành</th>
                                    <th>Tên chuyên ngành</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($majors_list)): ?>
                                    <?php foreach ($majors_list as $major): 
                                        $is_time_ok = ($today >= $major['date_start'] && $today <= $major['date_end']);
                                        $can_click = $is_time_ok && $is_year_ok; 
                                    ?>
                                        <tr class="<?= $can_click ? 'clickable-row' : 'disabled-row' ?>" 
                                            <?php if ($can_click): ?>
                                                onclick="selectMajor('<?= $major['id'] ?>', '<?= htmlspecialchars($major['major_name']) ?>', '<?= htmlspecialchars($major['major_code']) ?>')"
                                            <?php endif; ?>>
                                            <td><?= htmlspecialchars($major['major_code']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($major['major_name']) ?>
                                                <?php if (!$is_time_ok): ?>
                                                    <br>
                                                    <small style="color: #d9534f;">(Ngoài thời gian đăng ký)</small>
                                                <?php endif; ?>
                                                <?php if (!$is_year_ok): ?>
                                                    <br>
                                                    <small style="color: #f0ad4e;">(Không thuộc đối tượng đăng ký khóa này)</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" align="center" style="padding: 30px; color: #999; font-style: italic;">
                                            Không có lịch đăng ký chuyên ngành
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="pk-alert" style="margin-top: 15px; font-size: 0.85rem; background: #e7f3ff; border: 1px solid #b3d7ff; color: #004085; padding: 10px; border-radius: 5px;">
                    <i class="fas fa-info-circle"></i> Sinh viên chỉ được chọn các chuyên ngành thuộc khoa đang theo học. 
                </div>
                <div id="form-section" class="hcard">
                    <h3>Xác nhận đăng ký</h3>
                    <form action="major_record.php" method="POST">
                        <div class="user-static-info">
                            <p><strong>Sinh viên:</strong> <?= $user_info['last_name'] . ' ' . $user_info['first_name'] ?></p>
                            <p><strong>Mã SV:</strong> <?= $user_info['code'] ?></p>
                            <p><strong>Khoa:</strong> <?= $user_info['faculty_name'] ?></p>
                        </div>
                        <div class="form-group">
                            <label>Chuyên ngành lựa chọn:</label>
                            <input type="hidden" name="major_id" id="selected-major-id">
                            <input type="text" id="display-major-name" readonly placeholder="Chọn chuyên ngành từ danh sách bên trái" style="background: #f8f9fa;">
                        </div>
                        <div class="form-group">
                            <label>Ghi chú:</label>
                            <textarea rows="5" placeholder="Đổi chuyên ngành, kỳ trước chưa đăng ký,..." name="reason"></textarea>
                        </div>
                        <div class="form-button">
                            <?php if ($is_year_ok): ?>
                                <button type="submit" class="btn-submit">Xác nhận</button>
                            <?php else: ?>
                                <button type="button" class="btn-submit" disabled style="background: #ccc; cursor: not-allowed;">Đã khóa đăng ký</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            <div class="pk-right">
                <div class="card">
                    <h3>Lịch sử đăng ký</h3>
                    <table class="h-table">
                        <thead>
                            <tr>
                                <th>Ngày ĐK</th>
                                <th>Chuyên ngành</th>
                                <th>Ghi chú</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history_list)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #999; padding: 20px;">
                                        Bạn chưa có lịch sử đăng ký nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history_list as $h): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                                        <td><strong><?= htmlspecialchars($h['major_code']) ?></strong> - <?= htmlspecialchars($h['major_name']) ?></td>
                                        <td><?= htmlspecialchars($h['note']) ?></td>
                                        <td>
                                            <?php 
                                                $status_class = ($h['status'] == 'Thành công') ? 'text-success' : (($h['status'] == 'Bị từ chối') ? 'text-danger' : 'text-warning');
                                            ?>
                                            <span class="<?= $status_class ?>" style="font-weight: bold;">
                                                <?= htmlspecialchars($h['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script>
        function selectMajor(id, name, code) {
            document.getElementById('major_id').value = id;
            document.getElementById('display_major').value = code + " - " + name;
            document.getElementById('form-section').style.display = 'block';
            document.getElementById('form-section').scrollIntoView({ behavior: 'smooth' });
        }
        function hideForm() {
            document.getElementById('form-section').style.display = 'none';
            document.getElementById('major_id').value = '';
            document.getElementById('display_major').value = '';
        }
    </script>
</body>
</html>
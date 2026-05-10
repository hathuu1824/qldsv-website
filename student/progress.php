<?php
require_once 'config/cf_progress.php';
$percent = ($all_count > 0) ? round(($done_count / $all_count) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tiến trình học tập</title>
    <link rel="stylesheet" href="../css/progress.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="main-container">
        <div class="progress-title">
            <h2>Tiến trình học tập</h2>
            <div class="filter-group">
                <select id="faculty-select">
                    <option value=""><?php echo htmlspecialchars($student_faculty); ?></option>
                </select>
            </div>
        </div>

        <div class="progress-bar">
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <span class="progress-percentage">
                <?php echo "$total_earned / $total_required"; ?> tín chỉ
            </span>
        </div>

        <section class="progress-content">
            <?php if (empty($subjects_by_semester) && $total_required == 0): ?>
                <p class="no-data">Chưa có dữ liệu môn học.</p>
            <?php else: ?>
                <?php 
                for ($i = 1; $i <= 8; $i++): 
                    $k = ceil($i / 2); 
                    $year_start = $entry_year + ($k - 1);
                    $year_end = $year_start + 1;
                    $sem_in_year = ($i % 2 != 0) ? 1 : 2; 

                    $list = $subjects_by_semester[$i] ?? []; 

                    $mandatory = array_filter($list, function($s) { return ($s['is_e'] ?? 0) == 0; });
                    $electives = array_filter($list, function($s) { return ($s['is_e'] ?? 0) == 1; });
                    $graduation = array_filter($list, function($s) { return ($s['is_e'] ?? 0) == 2; });
                ?>
                    <article class="semester-section">
                        <h3 class="semester-title">
                            Học kỳ <?= $sem_in_year ?> - Năm học <?= $year_start ?> - <?= $year_end ?>
                        </h3>
                        <?php if (empty($list)): ?>
                            <div class="subject-grid">
                                <p class="empty-sem-msg" style="grid-column: 1/-1; color: #888; font-style: italic; font-size: 0.9rem;">
                                    Chưa có môn học phân bổ cho kỳ này.
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- MÔN BẮT BUỘC -->
                            <div class="subject-grid">
                                <?php foreach ($mandatory as $sub): 
                                    $status_class = match($sub['status']) {
                                        'Hoàn thành' => 'done',
                                        'Đang học'   => 'started',
                                        'Chưa qua'   => 'not-passed',
                                        default      => 'waiting'
                                    };
                                ?>
                                    <div class="subject-card <?= $status_class ?>" 
                                        onclick='showPopup(<?= json_encode($sub, JSON_HEX_APOS) ?>)'>
                                        <div class="card-body">
                                            <h4><?= htmlspecialchars($sub['subject_name']) ?></h4>
                                            <span class="credit-label"><?php echo $sub['credit']; ?> tín chỉ</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- MÔN TỰ CHỌN -->
                            <?php if (!empty($electives)): ?>
                                <div class="elective-header">
                                    <h4>Môn tự chọn</h4>
                                    <p class="elective-note">
                                        *(Yêu cầu học tối thiểu 4 tín chỉ trong nhóm này)
                                    </p>
                                </div>
                                <div class="subject-grid elective-group">
                                    <?php foreach ($electives as $sub): 
                                        $status_class = match($sub['status']) {
                                            'Hoàn thành' => 'done',
                                            'Đang học'   => 'started',
                                            'Chưa qua'   => 'not-passed',
                                            default      => 'waiting'
                                        };
                                    ?>
                                        <div class="subject-card elective <?= $status_class ?>" 
                                            onclick='showPopup(<?= json_encode($sub, JSON_HEX_APOS) ?>)'>
                                            <div class="card-body">
                                                <h4><?= htmlspecialchars($sub['subject_name']) ?></h4>
                                                <span class="credit-label"><?php echo $sub['credit']; ?> tín chỉ</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <!-- KHỐI TỐT NGHIỆP -->
                            <?php if (!empty($graduation)): ?>
                                <div class="elective-header">
                                    <h4>Khối tốt nghiệp</h4>
                                    <p class="elective-note">
                                        *(Chỉ chọn khóa luận tốt nghiệp nếu GPA tích lũy đạt từ 3.0 trở lên)
                                    </p>
                                </div>
                                <div class="subject-grid elective-group">
                                    <?php foreach ($graduation as $sub): 
                                        $status_class = match($sub['status']) {
                                            'Hoàn thành' => 'done',
                                            'Đang học'   => 'started',
                                            'Chưa qua'   => 'not-passed',
                                            default      => 'waiting'
                                        };
                                    ?>
                                        <div class="subject-card elective <?= $status_class ?>" 
                                            onclick='showPopup(<?= json_encode($sub, JSON_HEX_APOS) ?>)'>
                                            <div class="card-body">
                                                <h4><?= htmlspecialchars($sub['subject_name']) ?></h4>
                                                <span class="credit-label"><?php echo $sub['credit']; ?> tín chỉ</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </article>
                <?php endfor; ?>
            <?php endif; ?>
        </section>
    </main>

    <div id="subjectPopup" class="modal">
        <div class="modal-content">
            <header class="modal-header">
                <h2 id="pop-title">Tên học phần</h2>
                <span class="close-btn" onclick="closePopup()">&times;</span>
            </header>
            <hr>
            <div class="modal-body">
                <h4>Thông tin học phần</h4>
                <div class="info-grid">
                    <p><strong>Mã học phần:</strong> <span id="pop-code"></span></p>
                    <p><strong>Số tín chỉ:</strong> <span id="pop-credit"></span></p>
                </div>
                <hr>
                <div class="grade-tables">
                    <h4>Kết quả học tập</h4>
                    <section class="grade-group">
                        <h5>Điểm thành phần & ĐKDT</h5>
                        <table class="grade-table">
                            <thead>
                                <tr>
                                    <th>Chuyên cần (10%)</th>
                                    <th>Kiểm tra (30%)</th>
                                    <th>Điều kiện dự thi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="score-process">-</td>
                                    <td id="score-midterm">-</td>
                                    <td id="score-status">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="grade-group">
                        <h5>Điểm thi kết thúc học phần</h5>
                        <table class="grade-table">
                            <thead>
                                <tr>
                                    <th>Thi lần 1</th>
                                    <th>Thi lần 2</th>
                                    <th>Cuối kỳ (60%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="score-fterm">-</td>
                                    <td id="score-retake">-</td>
                                    <td id="score-final">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="grade-group">
                        <h5>Tổng kết</h5>
                        <table class="grade-table summary-table">
                            <thead>
                                <tr>
                                    <th>Điểm hệ 10</th>
                                    <th>Điểm hệ 4</th>
                                    <th>Điểm chữ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="score-total" class="bold">-</td>
                                    <td id="score-gpa">-</td>
                                    <td class="grade-cell">
                                        <span id="score-letter" class="grade-none">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <script src="../javascript/progress.js"></script>
    </body>
</html>
<?php
require_once 'config/cf_progress.php';

include 'config/cf_scores.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả học tập</title>
    <link rel="stylesheet" href="../css/progress.css">
    <link rel="stylesheet" href="../css/score.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="main-container">
        <div class="progress-title">
            <h2>Kết quả học tập</h2>
            <div class="filter-group">
                <select id="faculty-select">
                    <option value=""><?php echo htmlspecialchars($student_faculty); ?></option>
                </select>
            </div>
        </div>

        <div class="charts-slider-container">
            <button class="slider-btn prev-btn" onclick="changeSlide(-1)">&#10094;</button>
            <button class="slider-btn next-btn" onclick="changeSlide(1)">&#10095;</button>
            <div class="chart-slide active">
                <h4>Tín chỉ theo từng học kỳ</h4>
                <div class="chart-wrapper">
                    <canvas id="creditsChart"></canvas>
                </div>
            </div>
            <div class="chart-slide">
                <h4>Theo dõi điểm GPA (Hệ 4)</h4>
                <div class="chart-wrapper">
                    <canvas id="gpaChart"></canvas>
                </div>
            </div>
        </div>

        <section class="progress-content">
            <?php if (empty($subjects_by_semester)): ?>
                <p class="no-data">Chưa có dữ liệu kết quả học tập.</p>
            <?php else: ?>
                <?php 
                foreach ($subjects_by_semester as $sem_num => $list): 
                    $k = ceil($sem_num / 2);
                    $year_start = $entry_year + ($k - 1);
                    $year_end = $year_start + 1;
                    $sem_in_year = ($sem_num % 2 != 0) ? 1 : 2;
                ?>
                    <article class="semester-section">
                        <h3 class="semester-title">
                            Học kỳ <?= $sem_in_year ?> - Năm học <?= $year_start ?> - <?= $year_end ?>
                        </h3>
                        <div class="results-table-container">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th class="text-left">Tên học phần</th>
                                        <th>Tín chỉ</th>
                                        <th>CC</th>
                                        <th>KT</th>
                                        <th>Thi 1</th>
                                        <th>Thi 2</th>
                                        <th>Tổng kết</th>
                                        <th>Hệ 4</th>
                                        <th>Điểm chữ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $count = 1;
                                    foreach ($list as $sub): 
                                        $row_class = match($sub['status']) {
                                            'Hoàn thành' => 'row-done',
                                            'Đang học'   => 'row-started',
                                            'Chưa qua'   => 'row-not-passed',
                                            default      => 'row-waiting'
                                        };

                                        $p = $sub['score_process'];
                                        $m = $sub['score_midterm'];
                                        $f = $sub['score_final'];
                                        $f2 = $sub['score_retake'];

                                        $total = "-"; $gpa = "-"; $letter = "-"; $grade_class = "grade-none";
                                        
                                        if ($p === null && $m === null && $f === null && $f2 === null) {
                                            continue; 
                                        }

                                        if ($f !== null || $f2 !== null) {
                                            $max_f = max($f ?? 0, $f2 ?? 0);
                                            $total_num = ($p * 0.1) + ($m * 0.3) + ($max_f * 0.6);
                                            $total_num = round($total_num, 1);
                                            $total = number_format($total_num, 1);

                                            if ($total_num >= 8.5) { $gpa = "4.0"; $letter = "A"; $grade_class = "grade-a"; }
                                            elseif ($total_num >= 8.0) { $gpa = "3.5"; $letter = "B+"; $grade_class = "grade-b"; }
                                            elseif ($total_num >= 7.0) { $gpa = "3.0"; $letter = "B"; $grade_class = "grade-b"; }
                                            elseif ($total_num >= 6.5) { $gpa = "2.5"; $letter = "C+"; $grade_class = "grade-c"; }
                                            elseif ($total_num >= 5.5) { $gpa = "2.0"; $letter = "C"; $grade_class = "grade-c"; }
                                            elseif ($total_num >= 5.0) { $gpa = "1.5"; $letter = "D+"; $grade_class = "grade-d"; }
                                            elseif ($total_num >= 4.0) { $gpa = "1.0"; $letter = "D"; $grade_class = "grade-d"; }
                                            else { $gpa = "0.0"; $letter = "F"; $grade_class = "grade-f"; }
                                        }
                                    ?>
                                        <tr class="<?= $row_class ?>">
                                            <td><?= $count++ ?></td>
                                            <td class="text-left bold"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                            <td><?= $sub['credit'] ?></td>
                                            <td><?= ($p !== null) ? $p : "-" ?></td>
                                            <td><?= ($m !== null) ? $m : "-" ?></td>
                                            <td><?= ($f !== null) ? $f : "-" ?></td>
                                            <td><?= ($f2 !== null) ? $f2 : "-" ?></td>
                                            <td class="bold"><?= $total ?></td>
                                            <td><?= $gpa ?></td>
                                            <td>
                                                <span class="grade-badge <?= $grade_class ?>"><?= $letter ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <script>
        const chartData = {
            labels: <?= json_encode($chart_labels) ?>,
            semGpa: <?= json_encode($chart_semester_gpa) ?>,
            cumGpa: <?= json_encode($chart_cumulative_gpa) ?>,
            passSem: <?= json_encode($chart_pass_sem) ?>,
            passTotal: <?= json_encode($chart_pass_total) ?>,
            failSem: <?= json_encode($chart_fail_sem) ?>,
            failTotal: <?= json_encode($chart_fail_total) ?>,
            totalGoal: <?= (int)($student_total_goal ?? 120) ?>
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof initProgressCharts === 'function') {
                initProgressCharts(chartData);
            }
        });
    </script>
    <script src="../javascript/chart.js"></script>
</body>
</html>
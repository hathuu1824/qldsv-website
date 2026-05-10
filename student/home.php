<?php
require_once 'config/cf_class.php';

include 'config/cf_chart.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php"); 
    exit();
}
if ($_SESSION['role'] !== 'student') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }

$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

$sql_calendar = "
    SELECT cs.date AS event_date, s.subject_name AS title, 'hoc' AS type 
    FROM class_sessions cs
    JOIN classes c ON cs.class_id = c.id
    JOIN subjects s ON c.subject_id = s.id
    JOIN class_members cm ON c.id = cm.class_id
    WHERE cm.student_id = ? AND MONTH(cs.date) = ? AND YEAR(cs.date) = ?

    UNION ALL

    SELECT es.exam_date AS event_date, CONCAT(s.subject_name, ' (P.', es.room, ')') AS title, 'thi' AS type 
    FROM exam_sessions es
    JOIN exam_participants ep ON es.id = ep.exam_session_id
    JOIN classes c ON es.class_id = c.id
    JOIN subjects s ON c.subject_id = s.id
    WHERE ep.student_id = ? AND MONTH(es.exam_date) = ? AND YEAR(es.exam_date) = ?

    UNION ALL

    SELECT DATE(a.deadline) AS event_date, a.title, 'bai' AS type
    FROM assignments a
    JOIN class_members cm ON a.class_id = cm.class_id
    WHERE cm.student_id = ? AND MONTH(a.deadline) = ? AND YEAR(a.deadline) = ?
";

$events_by_date = [];
$stmt = $conn->prepare($sql_calendar);
$stmt->bind_param("iiiiiiiii", 
    $account_id, $month, $year, 
    $account_id, $month, $year, 
    $account_id, $month, $year
);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $day = (int)date('j', strtotime($row['event_date']));
    $events_by_date[$day][] = $row;
}

$first_day_ts = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day_ts);
$start_day_of_week = date('N', $first_day_ts);
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/home.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Cổng sinh viên</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-container">
        <div class="first-row">
            <h2>Kết quả học tập</h2>
            <div class="chart-card">
                <div class="chart-item">
                    <h4>GPA & CPA</h4>
                    <div class="chart-wrapper">
                        <canvas id="overviewChart"></canvas>
                    </div>
                </div>
                <div class="chart-item">
                    <h4>Tín chỉ tích lũy</h4>
                    <div class="chart-wrapper">
                        <canvas id="creditsChart"></canvas>
                    </div>
                </div>
                <div class="chart-item">
                    <h4>Tiến độ học tập</h4>
                    <div class="chart-wrapper">
                        <canvas id="gpaLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="second-row">
            <div class="class-container">
                <div class="section-header">
                    <h2>Lớp học</h2>
                    <div class="nav-buttons">
                        <button>&lt;</button>
                        <button>&gt;</button>
                    </div>
                </div>
                <div class="class-grid">
                    <?php if (empty($list)): ?>
                        <p>Không có lớp học nào trong học kỳ này.</p>
                    <?php else: ?>
                        <?php foreach ($list as $sub): 
                            $attended = $sub['attended_sessions'] ?? 0; 
                            $total_sessions = $sub['total_sessions'] ?? 10;
                            $percent = ($total_sessions > 0) ? round(($attended / $total_sessions) * 100) : 0;
                            $progress_color = ($percent >= 100) ? '#2ecc71' : '#3498db'; 
                        ?>
                            <div class="class-card" onclick="window.location.href='class_detail.php?id=<?= $sub['id'] ?>'">
                                <div class="card-header">
                                    <span class="subject-code"><?= htmlspecialchars($sub['subject_code']) ?></span>
                                    <span class="credit-label"><?= $sub['credit'] ?> tín chỉ</span>
                                </div>
                                <div class="card-body">
                                    <h4><?= htmlspecialchars($sub['subject_name']) ?></h4>
                                </div>
                                <div class="card-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Bài tập</span>
                                        <span class="stat-value"><?= $sub['assignment_count'] ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Buổi học</span>
                                        <span class="stat-value"><?= $attended ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Diễn đàn</span>
                                        <span class="stat-value"><?= $sub['forum_count'] ?></span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="progress-mini-container">
                                        <div class="progress-mini-bar">
                                            <div class="progress-mini-fill" style="width: <?= $percent ?>%; background-color: <?= $progress_color ?>;"></div>
                                        </div>
                                        <span class="progress-mini-text">Đã học: <?= $attended ?>/<?= $total_sessions ?> buổi</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>  
            </div>
            <div class="calendar-container">
                <div class="section-header">
                    <h2>Lịch</h2>
                    <a href="calendar.php">Xem tất cả</a>
                </div>
                <div class="calendar-content">
                    <div class="calendar-header">
                        <h4>Tháng <?= sprintf('%02d', $month) ?>, <?= $year ?></h4>
                    </div>
                    <div class="calendar-grid">
                        <div class="day-header">T2</div><div class="day-header">T3</div>
                        <div class="day-header">T4</div><div class="day-header">T5</div>
                        <div class="day-header">T6</div><div class="day-header">T7</div>
                        <div class="day-header">CN</div>
                        <?php
                        for ($i = 1; $i < $start_day_of_week; $i++) {
                            echo '<div class="day-cell empty"></div>';
                        }
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $current_date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $is_today = ($current_date == $today) ? 'highlight-today' : '';

                            echo "<div class='day-cell $is_today' onclick='showDetail(\"$current_date\")'>";
                            echo "<b>$d</b>";
                            
                            echo "<div class='event-dots'>";
                            if (isset($events_by_date[$d])) {
                                foreach ($events_by_date[$d] as $event) {
                                    $class_type = "event-" . $event['type'];
                                    echo "<span class='event-tag $class_type' title='" . htmlspecialchars($event['title']) . "'></span>";
                                }
                            }
                            echo "</div>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="last-row">
            <div class="assign-card">
                <div class="section-header">
                    <h2>Bài tập, bài kiểm tra</h2>
                    <a href="">Xem tất cả</a>
                </div>
                <div class="assignment-list">
                    <?php if(empty($assignments)): ?>
                        <div class="empty-item">Không có bài tập</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="forum-card">
                <div class="section-header">
                    <h2>Diễn đàn</h2>
                    <a href="">Xem tất cả</a>
                </div>
                <div class="forum-list">
                    <?php if(empty($forums)): ?>
                        <div class="empty-item">Không có chủ đề thảo luận nào</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        const chartData = {
            labels: <?= json_encode($final_labels ?? []) ?>,
            credits: <?= json_encode($final_credits_data ?? []) ?>, 
            semCredits: <?= json_encode($final_sem_credits_data ?? []) ?>,
            gpaHistory: <?= json_encode($final_gpa_data ?? []) ?>,
            cpaHistory: <?= json_encode($final_cpa_data ?? []) ?>,
            currentGpa: <?= (float)($current_gpa ?? 0) ?>,
            cpa: <?= (float)($cpa ?? 0) ?>,
            totalCredits: <?= (int)($total_earned_credits ?? 0) ?>,
            totalGoal: <?= (int)($graduation_target_credits ?? 120) ?>
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof initDashboardCharts === 'function') {
                initDashboardCharts(chartData);
            }
        });
    </script>
    <script src="../javascript/homechart.js"></script>
</body>
</html>
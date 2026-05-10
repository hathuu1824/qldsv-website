<?php
require_once 'config/cf_class.php';
$semesters_list = $semesters_list ?? [];
$selected_sem = $_GET['semester'] ?? ($semesters_list[0]['semester'] ?? 1);

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

$sql_upcoming = "
    SELECT * FROM (
        SELECT cs.date AS event_date, s.subject_name AS title, 'hoc' AS type 
        FROM class_sessions cs
        JOIN classes c ON cs.class_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        JOIN class_members cm ON c.id = cm.class_id
        WHERE cm.student_id = ? AND cs.date >= CURDATE()

        UNION ALL

        SELECT es.exam_date AS event_date, s.subject_name AS title, 'thi' AS type 
        FROM exam_sessions es
        JOIN exam_participants ep ON es.id = ep.exam_session_id
        JOIN classes c ON es.class_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE ep.student_id = ? AND es.exam_date >= CURDATE()

        UNION ALL

        SELECT DATE(a.deadline) AS event_date, a.title, 'bai' AS type
        FROM assignments a
        JOIN class_members cm ON a.class_id = cm.class_id
        WHERE cm.student_id = ? AND a.deadline >= NOW()
    ) AS results
    ORDER BY event_date ASC
    LIMIT 5
";

$stmt_up = $conn->prepare($sql_upcoming);
$stmt_up->bind_param("iii", $account_id, $account_id, $account_id);
$stmt_up->execute();
$res_upcoming = $stmt_up->get_result();

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
    <link rel="stylesheet" href="../css/calendar.css">
    <title>Lịch & Sự kiện</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="main-container">
        <div class="calendar-title">
            <h2>Lịch & Sự kiện</h2>
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

        <div class="calendar-content">
            <div class="calendar-container">
                <div class="calendar-header">
                    <h3>Tháng <?= sprintf('%02d', $month) ?>, <?= $year ?></h3>
                    <div>
                        <a href="?id=<?= $class_id ?>&tab=schedule" class="btn-nav">Hôm nay</a>
                        <a href="?id=<?= $class_id ?>&tab=schedule&month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn-nav"><</a>
                        <a href="?id=<?= $class_id ?>&tab=schedule&month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn-nav">></a>
                    </div>
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
                        
                        if (isset($events_by_date[$d])) {
                            foreach ($events_by_date[$d] as $event) {
                                $class_type = "event-" . $event['type'];
                                echo "<span style='margin-bottom: 5px; font-size: 10px;' class='event-tag $class_type'>" . htmlspecialchars($event['title']) . "</span>";
                            }
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="detail-container">
                <div class="detail-card" style="padding-bottom: 5px;">
                    <h3>Chi tiết sự kiện</h3>
                    <div class="detail-list">
                        <div class="legend" style="margin-bottom: 20px;">
                            <p><span class="dot event-hoc"></span> Lịch học</p>
                            <p><span class="dot event-thi"></span> Lịch thi</p>
                            <p><span class="dot event-bai"></span> Bài tập</p>
                        </div>
                    </div>
                </div>
                <div class="detail-card">
                    <h3 style="margin-bottom: 15px;">Lịch sắp tới</h3>
                    <div class="detail-calendar">
                        <?php if ($res_upcoming->num_rows > 0): ?>
                            <ul class="upcoming-list" style="list-style: none; padding: 0;">
                                <?php while($up = $res_upcoming->fetch_assoc()): ?>
                                    <li style="padding: 10px; border-left: 4px solid <?= ($up['type'] == 'thi' ? '#e74c3c' : ($up['type'] == 'hoc' ? '#007bff' : '#f39c12')) ?>; margin-bottom: 10px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        <div style="font-size: 0.8rem; color: #888;">
                                            <?= date('d/m/Y', strtotime($up['event_date'])) ?>
                                        </div>
                                        <div style="font-weight: bold; font-size: 0.9rem;">
                                            <?= htmlspecialchars($up['title']) ?>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p>Không có sự kiện nào sắp tới.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        const allEvents = <?= json_encode($events_by_date) ?>;

        function showDetail(dateStr) {
            const day = parseInt(dateStr.split('-')[2]);
            const infoBox = document.getElementById('event-info');
            const events = allEvents[day];

            if (!events || events.length === 0) {
                infoBox.innerHTML = `<p style="color: #999;">Ngày ${day}/${month} không có sự kiện nào.</p>`;
                return;
            }

            let html = `<h4>Sự kiện ngày ${day}:</h4><ul class="detail-ul">`;
            events.forEach(ev => {
                html += `<li><span class="dot event-${ev.type}"></span> ${ev.title}</li>`;
            });
            html += '</ul>';
            infoBox.innerHTML = html;
        }
    </script>
</body>
</html>
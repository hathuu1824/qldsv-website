<?php
// 1. Cấu hình và Kết nối
require_once 'config/cf_class.php'; 
include 'config/cf_chart.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kiểm tra quyền truy cập
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php"); 
    exit();
}
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

$teacher_id = $_SESSION['id'];

// 3. Xử lý thời gian cho Lịch
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }

$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

// 4. LOGIC THỐNG KÊ 
$stmt_c = $conn->prepare("SELECT COUNT(*) as total FROM classes WHERE account_id = ?");
$stmt_c->bind_param("i", $teacher_id);
$stmt_c->execute();
$total_classes = $stmt_c->get_result()->fetch_assoc()['total'];

// Tổng số sinh viên quản lý
$stmt_s = $conn->prepare("SELECT COUNT(DISTINCT cm.student_id) as total FROM class_members cm 
                          JOIN classes c ON cm.class_id = c.id 
                          WHERE c.account_id = ?");
$stmt_s->bind_param("i", $teacher_id);
$stmt_s->execute();
$total_students = $stmt_s->get_result()->fetch_assoc()['total'];

// Số bài tập chờ chấm
$stmt_p = $conn->prepare("SELECT COUNT(*) as total FROM submissions s 
                          JOIN assignments a ON s.assignment_id = a.id 
                          JOIN classes c ON a.class_id = c.id 
                          WHERE c.account_id = ? AND s.grade IS NULL");
$stmt_p->bind_param("i", $teacher_id);
$stmt_p->execute();
$pending_grades = $stmt_p->get_result()->fetch_assoc()['total'];

// Số yêu cầu phúc khảo đã được Admin duyệt
$stmt_r = $conn->prepare("SELECT COUNT(*) as total FROM re_exam_requests rr
                          JOIN subjects s ON rr.subject_id = s.id
                          JOIN classes c ON s.id = c.subject_id
                          WHERE c.account_id = ? AND rr.status = 'Đã duyệt'");
$stmt_r->bind_param("i", $teacher_id);
$stmt_r->execute();
$pending_reexam = $stmt_r->get_result()->fetch_assoc()['total'];


// 5. LOGIC LỊCH DẠY 
$sql_calendar = "
    -- 1. Lịch dạy (Từ bảng class_sessions)
    SELECT cs.date AS event_date, s.subject_name AS title, 'hoc' AS type 
    FROM class_sessions cs
    JOIN classes c ON cs.class_id = c.id
    JOIN subjects s ON c.subject_id = s.id
    WHERE c.account_id = ? AND MONTH(cs.date) = ? AND YEAR(cs.date) = ?

    UNION ALL

    -- 2. Lịch thi (Từ bảng exam_sessions)
    SELECT es.exam_date AS event_date, CONCAT('Thi: ', s.subject_name) AS title, 'thi' AS type 
    FROM exam_sessions es
    JOIN classes c ON es.class_id = c.id
    JOIN subjects s ON c.subject_id = s.id
    WHERE c.account_id = ? AND MONTH(es.exam_date) = ? AND YEAR(es.exam_date) = ?

    UNION ALL

    -- 3. Hạn chót bài tập (Từ bảng assignments)
    SELECT DATE(a.deadline) AS event_date, CONCAT('Hạn: ', a.title) AS title, 'bai' AS type
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    WHERE c.account_id = ? AND MONTH(a.deadline) = ? AND YEAR(a.deadline) = ?
";

$events_by_date = [];
$stmt_cal = $conn->prepare($sql_calendar);

// Kiểm tra nếu SQL bị sai cú pháp (Tránh lỗi Call to a member function bind_param() on bool)
if (!$stmt_cal) {
    die("Lỗi SQL Lịch: " . $conn->error); 
}

// Truyền đúng 9 tham số cho 9 dấu '?' ở trên
$stmt_cal->bind_param("iiiiiiiii", 
    $teacher_id, $month, $year, 
    $teacher_id, $month, $year, 
    $teacher_id, $month, $year
);
$stmt_cal->execute();
$res = $stmt_cal->get_result();

while ($row = $res->fetch_assoc()) {
    $day = (int)date('j', strtotime($row['event_date']));
    $events_by_date[$day][] = $row;
}

// 6. Thông số phụ trợ cho giao diện Lịch
$first_day_ts = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day_ts);
$start_day_of_week = date('N', $first_day_ts); 
$today = date('Y-m-d');

// Truy vấn lấy danh sách lớp và tính tỉ lệ chuyên cần trung bình của mỗi lớp
$class_names = [];
$attendance_rates = [];

$sql_chart = "SELECT 
                c.id,
                c.class_name,
                SUM(CASE WHEN a.status = 'Có mặt' THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN a.status LIKE 'Muộn%' THEN 1 ELSE 0 END) as total_late,
                COUNT(a.id) as total_records
              FROM classes c
              JOIN class_sessions cs ON c.id = cs.class_id
              JOIN attendance a ON cs.id = a.session_id
              WHERE c.account_id = ?
              GROUP BY c.id";

$stmt_chart = $conn->prepare($sql_chart);
$stmt_chart->bind_param("i", $teacher_id);
$stmt_chart->execute();
$res_chart = $stmt_chart->get_result();

while ($row = $res_chart->fetch_assoc()) {
    $class_names[] = $row['class_name'];
    
    $present = $row['total_present'];
    $late = $row['total_late'];
    $total = $row['total_records'];

    if ($total > 0) {
        $effective_present = $present + ($late * 0.5);
        $rate = ($effective_present / $total) * 100;
    } else {
        $rate = 0;
    }
    
    $attendance_rates[] = round($rate, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/teacher.css">
    <link rel="stylesheet" href="../css/home.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Cổng giảng viên</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-container">
        <div class="first-row" style="margin-bottom: 0;">
            <h2>Chào mừng trở lại!</h2>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-info">
                    <h3><?= $total_classes ?></h3>
                    <p>Lớp đang dạy</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8f5e9; color: #388e3c;"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h3><?= $total_students ?></h3>
                    <p>Sinh viên quản lý</p>
                </div>
            </div>
            <div class="stat-card urgent" onclick="location.href='grade_management.php'">
                <div class="stat-icon" style="background: #fff3e0; color: #f57c00;"><i class="fas fa-file-signature"></i></div>
                <div class="stat-info">
                    <h3><?= $pending_grades ?></h3>
                    <p>Bài tập chờ chấm</p>
                </div>
                <?php if($pending_grades > 0): ?><span class="notify-dot"></span><?php endif; ?>
            </div>
            <div class="stat-card" onclick="location.href='re_exam.php'">
                <div class="stat-icon" style="background: #fce4ec; color: #c2185b;"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-info">
                    <h3><?= $pending_reexam ?></h3>
                    <p>Yêu cầu phúc khảo</p>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="chart-section">
                <div class="card-header">
                    <h4>Tỉ lệ chuyên cần trung bình</h4>
                </div>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
            <div class="calendar-section">
                <div class="calendar-header">
                    <h4>Lịch dạy tháng <?= $month ?>/<?= $year ?></h4>
                    <div class="cal-nav">
                        <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>"><i class="fas fa-chevron-left"></i></a>
                        <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
                <div class="calendar-grid">
                    <?php 
                        for($i=1; $i<$start_day_of_week; $i++) echo '<div class="cal-day empty"></div>';
                        for($d=1; $d<=$days_in_month; $d++): 
                            $is_today = (date('Y-m-d', mktime(0,0,0,$month,$d,$year)) == $today) ? 'today' : '';
                            $has_event = isset($events_by_date[$d]) ? 'has-event' : '';
                    ?>
                        <div class="cal-day <?= $is_today ?> <?= $has_event ?>">
                            <span><?= $d ?></span>
                            <?php if(isset($events_by_date[$d])): ?>
                                <div class="event-dots">
                                    <?php foreach($events_by_date[$d] as $ev): ?>
                                        <span class="dot <?= $ev['type'] ?>" title="<?= $ev['title'] ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
    const labels = <?php echo json_encode($class_names); ?>;
    const dataRates = <?php echo json_encode($attendance_rates); ?>;

    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: labels,
            datasets: [{
                label: 'Tỉ lệ đi học (%)',
                data: dataRates,
                backgroundColor: 'rgba(78, 115, 223, 0.5)', 
                borderColor: 'rgba(78, 115, 223, 1)',      
                borderWidth: 2,
                borderRadius: 5,
                barPercentage: 0.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100, 
                    ticks: {
                        callback: function(value) { return value + "%"; }
                    }
                }
            },
            plugins: {
                legend: { display: false } 
            }
        }
    });
    </script>
</body>
</html>
<?php
require_once '../db_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lecturer_id = $_SESSION['id'] ?? 0;

// 1. Lấy danh sách học kỳ 
$sem_sql = "SELECT DISTINCT semester FROM classes ORDER BY semester DESC";
$sem_res = mysqli_query($conn, $sem_sql);
$semesters_list = [];
if ($sem_res) {
    while ($row = mysqli_fetch_assoc($sem_res)) {
        $semesters_list[] = $row;
    }
}

$selected_sem = $_GET['semester'] ?? ($semesters_list[0]['semester'] ?? "");

// 2. Truy vấn danh sách lớp với LOGIC MỚI
$sql_list = "SELECT 
                c.id, 
                c.class_code,
                s.subject_code, 
                s.subject_name, 
                s.credit,
                s.total_sessions, 
                
                -- Đếm số buổi đã dạy (những buổi đã có dữ liệu điểm danh)
                (SELECT COUNT(DISTINCT cs.id) 
                 FROM class_sessions cs 
                 JOIN attendance att ON cs.id = att.session_id 
                 WHERE cs.class_id = c.id) as attended_sessions,
                
                (SELECT COUNT(*) FROM assignments WHERE class_id = c.id) as assignment_count,
                (SELECT COUNT(*) FROM forums WHERE class_id = c.id) as forum_count,
                
                -- Tính trạng thái động giống logic sinh viên
                CASE 
                    WHEN (SELECT COUNT(DISTINCT cs2.id) 
                          FROM class_sessions cs2 
                          JOIN attendance att2 ON cs2.id = att2.session_id 
                          WHERE cs2.class_id = c.id) >= s.total_sessions THEN 'Đã kết thúc'
                    WHEN (SELECT COUNT(DISTINCT cs2.id) 
                          FROM class_sessions cs2 
                          JOIN attendance att2 ON cs2.id = att2.session_id 
                          WHERE cs2.class_id = c.id) > 0 THEN 'Đang dạy'
                    ELSE 'Chưa bắt đầu'
                END AS calculated_status
             FROM classes c
             JOIN subjects s ON c.subject_id = s.id
             WHERE c.account_id = ? 
             AND c.semester = ?
             ORDER BY s.subject_name ASC";

$stmt = $conn->prepare($sql_list);
$stmt->bind_param("ii", $lecturer_id, $selected_sem);
$stmt->execute();
$res_list = $stmt->get_result();

$list = $res_list->fetch_all(MYSQLI_ASSOC);

// 3. Xử lý Bài tập và Lịch dạy sắp tới (7 ngày tới)
$assignments = [];
$forums = []; // Đảm bảo khởi tạo mảng forums để không lỗi undefined
$calendars = [];

if (!empty($list)) {
    $class_ids = array_column($list, 'id');
    $placeholders = implode(',', array_fill(0, count($class_ids), '?'));
    $types = str_repeat('i', count($class_ids));

    // Lấy 5 bài tập mới nhất - BỔ SUNG a.id
    $sql_as = "SELECT a.id, a.title, a.deadline, s.subject_name 
               FROM assignments a
               JOIN classes c ON a.class_id = c.id
               JOIN subjects s ON c.subject_id = s.id
               WHERE a.class_id IN ($placeholders) 
               ORDER BY a.deadline ASC LIMIT 5";
    $stmt_as = $conn->prepare($sql_as);
    $stmt_as->bind_param($types, ...$class_ids);
    $stmt_as->execute();
    $assignments = $stmt_as->get_result()->fetch_all(MYSQLI_ASSOC);

    // Lấy lịch dạy trong vòng 7 ngày tới 
    $sql_cal = "SELECT cs.id, cs.date, cs.period, s.subject_name, s.subject_code 
                FROM class_sessions cs 
                JOIN classes c ON cs.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                WHERE cs.class_id IN ($placeholders) 
                  AND cs.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY cs.date ASC, cs.period ASC";
    $stmt_cal = $conn->prepare($sql_cal);
    $stmt_cal->bind_param($types, ...$class_ids);
    $stmt_cal->execute();
    $calendars = $stmt_cal->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Lấy danh sách diễn đàn (nếu trang class.php cần hiển thị list diễn đàn)
    $sql_forum = "SELECT f.*, s.subject_name FROM forums f 
                  JOIN classes c ON f.class_id = c.id 
                  JOIN subjects s ON c.subject_id = s.id
                  WHERE f.class_id IN ($placeholders) ORDER BY f.id DESC LIMIT 5";
    $stmt_f = $conn->prepare($sql_forum);
    $stmt_f->bind_param($types, ...$class_ids);
    $stmt_f->execute();
    $forums = $stmt_f->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<?php 
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require '../db_connection.php';
$account_id = $_SESSION['id'];

// 1. Lấy major_id của sinh viên
$sql_info = "SELECT major_id FROM profile WHERE account_id = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $account_id);
$stmt_info->execute();
$res_info = $stmt_info->get_result();
$row_user = $res_info->fetch_assoc();
$major_id = $row_user['major_id'] ?? 0;

// 2. Lấy danh sách học kỳ sinh viên có học
$sql_sem = "SELECT DISTINCT c.semester 
            FROM classes c 
            JOIN class_members cm ON c.id = cm.class_id 
            WHERE cm.student_id = ? 
            ORDER BY c.semester DESC";

$stmt_sem = $conn->prepare($sql_sem);
$stmt_sem->bind_param("i", $account_id);
$stmt_sem->execute();
$semesters_list = $stmt_sem->get_result()->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['semester'])) {
    $selected_sem = (int)$_GET['semester'];
} else {
    $selected_sem = !empty($semesters_list) ? (int)$semesters_list[0]['semester'] : 0;
}

$list = [];
$assignments = [];
$forums = [];
$calendars = [];

// 4. Lấy danh sách lớp học tổng hợp (Assignment, Forum, Attendance, Status)
$semester_condition = ($selected_sem > 0) ? "AND c.semester = ?" : "";

$sql_class = "SELECT 
                c.id AS id, 
                s.subject_name, 
                s.subject_code, 
                s.credit, 
                s.total_sessions, 
                c.semester,
                c.group_id,
                -- Khôi phục bộ đếm bài tập và diễn đàn của bạn
                (SELECT COUNT(*) FROM assignments WHERE class_id = c.id) as assignment_count,
                (SELECT COUNT(*) FROM forums WHERE class_id = c.id) as forum_count,
                
                -- Tính số buổi đi học thực tế (đếm trực tiếp từ bảng attendance)
                (SELECT COUNT(*) 
                 FROM attendance att 
                 JOIN class_sessions cs ON att.session_id = cs.id 
                 WHERE cs.class_id = c.id 
                   AND att.student_id = cm.student_id 
                   AND att.status = 'Có mặt'
                ) AS attended_sessions,
                
                -- Tự động tính Status dựa trên chuyên cần
                CASE 
                    WHEN (SELECT COUNT(*) 
                          FROM attendance att2 
                          JOIN class_sessions cs2 ON att2.session_id = cs2.id 
                          WHERE cs2.class_id = c.id 
                            AND att2.student_id = cm.student_id 
                            AND att2.status = 'Có mặt') >= s.total_sessions THEN 'Đã hoàn thành'
                    WHEN (SELECT COUNT(*) 
                          FROM attendance att2 
                          JOIN class_sessions cs2 ON att2.session_id = cs2.id 
                          WHERE cs2.class_id = c.id 
                            AND att2.student_id = cm.student_id 
                            AND att2.status = 'Có mặt') > 0 THEN 'Đang học'
                    ELSE 'Chưa học'
                END AS status
              FROM classes c
              JOIN subjects s ON c.subject_id = s.id
              JOIN class_members cm ON c.id = cm.class_id
              WHERE cm.student_id = ? 
              AND (s.major_id = ? OR s.major_id = 0)
              $semester_condition
              ORDER BY c.semester DESC, s.subject_name ASC";

$stmt_class = $conn->prepare($sql_class);

if ($selected_sem > 0) {
    $stmt_class->bind_param("iii", $account_id, $major_id, $selected_sem);
} else {
    $stmt_class->bind_param("ii", $account_id, $major_id);
}

$stmt_class->execute();
$list = $stmt_class->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Lấy dữ liệu phụ 
$class_ids = array_column($list, 'id');

if (!empty($class_ids)) {
    $id_placeholders = implode(',', array_fill(0, count($class_ids), '?'));
    $types = str_repeat('i', count($class_ids));

    // Lấy bài tập
    $sql_assign = "SELECT a.*, s.subject_name 
                   FROM assignments a 
                   JOIN classes c ON a.class_id = c.id
                   JOIN subjects s ON c.subject_id = s.id
                   WHERE a.class_id IN ($id_placeholders) 
                   ORDER BY a.deadline ASC";
    $stmt_assign = $conn->prepare($sql_assign);
    $stmt_assign->bind_param($types, ...$class_ids);
    $stmt_assign->execute();
    $assignments = $stmt_assign->get_result()->fetch_all(MYSQLI_ASSOC);

    // Lấy diễn đàn (5 tin mới nhất)
    $sql_forum = "SELECT f.*, s.subject_name 
                  FROM forums f 
                  JOIN classes c ON f.class_id = c.id
                  JOIN subjects s ON c.subject_id = s.id
                  WHERE f.class_id IN ($id_placeholders) 
                  ORDER BY f.created_at DESC LIMIT 5";
    $stmt_forum = $conn->prepare($sql_forum);
    $stmt_forum->bind_param($types, ...$class_ids);
    $stmt_forum->execute();
    $forums = $stmt_forum->get_result()->fetch_all(MYSQLI_ASSOC);

    // Lấy lịch học
    $sql_cal = "SELECT cs.*, s.subject_name, s.subject_code 
            FROM class_sessions cs 
            JOIN classes c ON cs.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE cs.class_id IN ($id_placeholders) 
              AND cs.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY cs.date ASC, cs.period ASC";
    $stmt_cal = $conn->prepare($sql_cal);
    $stmt_cal->bind_param($types, ...$class_ids);
    $stmt_cal->execute();
    $calendars = $stmt_cal->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require '../db_connection.php';
$account_id = $_SESSION['id'];

// Phần cho trang Tiến trình học tập
$subjects_by_semester = [];
$student_major = "Chưa xác định";
$student_faculty = "Chưa xác định";
$total_required = 0;
$total_earned = 0;
$entry_year = 2022;

$sql_info = "SELECT p.major_id, m.major_name, p.faculty_id, f.faculty_name, p.academic_year 
             FROM profile p 
             LEFT JOIN majors m ON p.major_id = m.id 
             LEFT JOIN faculties f ON p.faculty_id = f.id 
             WHERE p.account_id = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $account_id);
$stmt_info->execute();
$res_info = $stmt_info->get_result();

if ($row_info = $res_info->fetch_assoc()) {
    $major_id = $row_info['major_id'];
    $student_major = $row_info['major_name'] ?? "Chưa xác định";
    $student_faculty = $row_info['faculty_name'] ?? "Chưa xác định";
    $raw_year = $row_info['academic_year'] ?? "2022";
    $entry_year = (int)substr($raw_year, 0, 4);

    if ($major_id) {
        // Lấy tổng số tín chỉ yêu cầu từ khung chương trình
        $sql_curr = "SELECT total_credits FROM curriculum WHERE major_id = ? AND years = ?";
        $stmt_curr = $conn->prepare($sql_curr);
        $stmt_curr->bind_param("ii", $major_id, $entry_year);
        $stmt_curr->execute();
        $res_curr = $stmt_curr->get_result();
        if ($row_curr = $res_curr->fetch_assoc()) {
            $total_required = (int)$row_curr['total_credits'];
        }

        // Logic lấy TOÀN BỘ môn học trong ngành (is_e = 0 và is_e = 1)
        $sql_subs = "SELECT s.id, s.subject_name, s.subject_code, s.credit, s.semester, s.is_e,
                            res.score_process, res.score_midterm, res.score_final, res.score_retake,
                            -- Tự động xác định trạng thái dựa trên điểm số vừa nhập
                            CASE 
                                WHEN (res.score_final IS NOT NULL OR res.score_retake IS NOT NULL) THEN
                                    CASE 
                                        WHEN (res.score_process * 0.1 + res.score_midterm * 0.3 + GREATEST(IFNULL(res.score_final,0), IFNULL(res.score_retake,0)) * 0.6) >= 4.0 
                                        THEN 'Hoàn thành' 
                                        ELSE 'Học lại' 
                                    END
                                -- Nếu chưa có điểm nhưng đã có trong class_members thì coi là đang học
                                WHEN res.class_id IS NOT NULL THEN 'Đang học'
                                ELSE 'Chưa học'
                            END AS status
                    FROM subjects s
                    LEFT JOIN (
                        /* Subquery lấy kết quả học tập và thông tin lớp của sinh viên này */
                        SELECT r.score_process, r.score_midterm, r.score_final, r.score_retake, c.subject_id, c.id as class_id
                        FROM class_results r
                        JOIN classes c ON r.class_id = c.id
                        WHERE r.account_id = ?
                    ) res ON s.id = res.subject_id
                    WHERE (s.major_id = ? OR s.major_id = 0)
                    ORDER BY s.semester ASC, s.subject_name ASC";

        $stmt_subs = $conn->prepare($sql_subs);
        $stmt_subs->bind_param("ii", $account_id, $major_id); // Chỉ cần 2 tham số: account_id cho subquery và major_id cho filter
        $stmt_subs->execute();
        $result_subs = $stmt_subs->get_result();

        while ($row = $result_subs->fetch_assoc()) {
            // Group môn học theo số học kỳ trong khung chương trình (1, 2, 3...)
            $subjects_by_semester[$row['semester']][] = $row;
            
            // Chỉ cộng vào tín chỉ tích lũy nếu trạng thái là Hoàn thành
            if ($row['status'] == 'Hoàn thành') {
                $total_earned += (int)$row['credit'];
            }
        }
    }
}

//Phần cho trang Kết quả học tập
$student_total_goal = ($total_required > 0) ? $total_required : 120;

$chart_labels = [];
$chart_credits = [];
$chart_semester_gpa = [];
$chart_cumulative_gpa = [];

$all_count = 0; 
$done_count = 0;
$total_earned = 0; 

foreach ($subjects_by_semester as $list) {
    foreach ($list as $s) {
        $all_count++;
        if ($s['status'] == 'Hoàn thành') {
            $done_count++;
            $total_earned += (int)$s['credit'];
        }
    }
} 

$total_weighted_score = 0;     
$total_credits_accumulated = 0; 

$sorted_semesters = $subjects_by_semester;
ksort($sorted_semesters);

foreach ($sorted_semesters as $sem_num => $list) {
    $sem_credits = 0;         
    $sem_weighted_score = 0;  
    $sem_valid_credits = 0;      

    foreach ($list as $sub) {
        $p = $sub['score_process'];
        $m = $sub['score_midterm'];
        $f = $sub['score_final'];
        $f2 = $sub['score_retake'];

        // Chỉ tính GPA nếu môn đó đã có điểm thi (Cuối kỳ hoặc Thi lại)
        if ($f !== null || $f2 !== null) {
            // Lấy điểm thi cao nhất giữa thi lần 1 và thi lại
            $max_f = max(($f ?? 0), ($f2 ?? 0));
            
            // Tính điểm tổng kết hệ 10
            $total_num = ($p * 0.1) + ($m * 0.3) + ($max_f * 0.6);
            
            // Quy đổi sang hệ 4.0 theo thang điểm của bạn
            $gpa_4 = 0;
            if ($total_num >= 8.5) $gpa_4 = 4.0;
            elseif ($total_num >= 8.0) $gpa_4 = 3.5;
            elseif ($total_num >= 7.0) $gpa_4 = 3.0;
            elseif ($total_num >= 6.5) $gpa_4 = 2.5;
            elseif ($total_num >= 5.5) $gpa_4 = 2.0;
            elseif ($total_num >= 5.0) $gpa_4 = 1.5;
            elseif ($total_num >= 4.0) $gpa_4 = 1.0;
            else $gpa_4 = 0.0;

            // Cộng dồn để tính GPA học kỳ
            $sem_weighted_score += ($gpa_4 * $sub['credit']);
            $sem_valid_credits += $sub['credit'];
        }
        $sem_credits += $sub['credit'];
    }

    $semester_gpa = ($sem_valid_credits > 0) ? round($sem_weighted_score / $sem_valid_credits, 2) : 0;
    
    $total_weighted_score += $sem_weighted_score;
    $total_credits_accumulated += $sem_valid_credits;
    $cumulative_gpa = ($total_credits_accumulated > 0) ? round($total_weighted_score / $total_credits_accumulated, 2) : 0;

    $display_year = $entry_year + floor(($sem_num - 1) / 2);
    $chart_labels[] = "Kỳ " . $sem_num . " năm " . $display_year;

    $chart_credits[] = $sem_credits;
    $chart_semester_gpa[] = $semester_gpa;
    $chart_cumulative_gpa[] = $cumulative_gpa;
}

if (!empty($subjects_by_semester)) {
    krsort($subjects_by_semester); 
}
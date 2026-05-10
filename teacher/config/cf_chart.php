<?php
// 1. Khởi tạo các mảng dữ liệu mặc định
$final_labels = [];
$final_gpa_data = [];
$final_cpa_data = [];      // Mảng mới: CPA cộng dồn qua từng kỳ cho biểu đồ đường
$final_credits_data = [];  // Mảng cộng dồn cho biểu đồ cột
$total_earned_credits = 0; // Số hiển thị ở giữa hình tròn
$current_gpa = 0;
$cpa = 0;
$graduation_target_credits = 120; // Mặc định 120 để tránh lỗi trục tung = 0

$account_id = $_SESSION['id'] ?? 0;

if (isset($conn) && $account_id > 0) {

    // 2. Truy vấn lấy mục tiêu tín chỉ từ chương trình đào tạo
    $sql_target = "SELECT c.total_credits 
                   FROM curriculum c 
                   JOIN profile p ON p.academic_year = c.years 
                   WHERE p.account_id = ? 
                   LIMIT 1";
    
    if ($stmt_target = $conn->prepare($sql_target)) {
        $stmt_target->bind_param("i", $account_id);
        $stmt_target->execute();
        $res_target = $stmt_target->get_result();
        if ($row_target = $res_target->fetch_assoc()) {
            if ((int)$row_target['total_credits'] > 0) {
                $graduation_target_credits = (int)$row_target['total_credits'];
            }
        }
        $stmt_target->close();
    }

    // 3. Truy vấn lấy điểm chi tiết
    $sql = "SELECT 
                c.semester, 
                sub.credit,
                cr.score_process,
                cr.score_midterm,
                cr.score_final,
                cr.score_retake
            FROM classes c
            JOIN subjects sub ON c.subject_id = sub.id
            JOIN class_results cr ON c.id = cr.class_id
            WHERE cr.account_id = ?
            ORDER BY c.semester ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $temp_semesters = [];

        while ($row = $result->fetch_assoc()) {
            // Tính điểm hệ 10 (10% - 30% - 60%)
            $max_f = max($row['score_final'] ?? 0, $row['score_retake'] ?? 0);
            $total_10 = round(($row['score_process'] * 0.1) + ($row['score_midterm'] * 0.3) + ($max_f * 0.6), 1);

            // Quy đổi sang hệ 4
            $gpa4 = 0;
            if ($total_10 >= 8.5) $gpa4 = 4.0;
            else if ($total_10 >= 8.0) $gpa4 = 3.5;
            else if ($total_10 >= 7.0) $gpa4 = 3.0;
            else if ($total_10 >= 6.5) $gpa4 = 2.5;
            else if ($total_10 >= 5.5) $gpa4 = 2.0;
            else if ($total_10 >= 5.0) $gpa4 = 1.5;
            else if ($total_10 >= 4.0) $gpa4 = 1.0;

            // Tích lũy tổng tín chỉ đạt được (cho con số ở giữa Doughnut)
            if ($gpa4 > 0) {
                $total_earned_credits += $row['credit'];
            }

            $sem_key = $row['semester'];
            if (!isset($temp_semesters[$sem_key])) {
                $temp_semesters[$sem_key] = ['w_sum' => 0, 'c_sum' => 0];
            }
            $temp_semesters[$sem_key]['w_sum'] += ($gpa4 * $row['credit']);
            $temp_semesters[$sem_key]['c_sum'] += $row['credit'];
        }
        $stmt->close();

        // 4. Tổng hợp và tính TOÁN CỘNG DỒN
        $running_w_sum = 0; 
        $running_c_sum = 0;
        $running_credits_bar = 0; 
        
        $final_sem_credits_data = []; // Thêm mảng này để lưu tín chỉ riêng từng kỳ

        foreach ($temp_semesters as $sem => $data) {
            $final_labels[] = "Kỳ " . $sem;
            
            // Lưu tín chỉ riêng của kỳ hiện tại
            $final_sem_credits_data[] = $data['c_sum'];

            // Tính tín chỉ cộng dồn (Lũy kế)
            $running_credits_bar += $data['c_sum'];
            $final_credits_data[] = $running_credits_bar; 
            
            // ... (Phần tính GPA, CPA giữ nguyên)
            $sem_gpa = ($data['c_sum'] > 0) ? round($data['w_sum'] / $data['c_sum'], 2) : 0;
            $final_gpa_data[] = $sem_gpa;

            $running_w_sum += $data['w_sum'];
            $running_c_sum += $data['c_sum'];
            $final_cpa_data[] = ($running_c_sum > 0) ? round($running_w_sum / $running_c_sum, 2) : 0;
        }

        // 5. Gán giá trị cuối cùng cho biểu đồ tròn (Doughnut)
        $cpa = !empty($final_cpa_data) ? end($final_cpa_data) : 0;
        
        // current_gpa lấy kỳ gần nhất có điểm (khác 0) để vòng tròn ngoài không bị rỗng
        $valid_gpas = array_filter($final_gpa_data, function($v) { return $v > 0; });
        $current_gpa = !empty($valid_gpas) ? end($valid_gpas) : 0;
    }   
}

// 6. Ép kiểu dữ liệu an toàn cho JavaScript
$current_gpa = (float)$current_gpa;
$cpa = (float)$cpa;
$total_earned_credits = (int)$total_earned_credits;
$graduation_target_credits = (int)$graduation_target_credits;
?>
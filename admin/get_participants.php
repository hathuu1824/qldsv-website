<?php
require '../db_connection.php';

// 1. Lấy session_id từ yêu cầu AJAX
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

if ($session_id > 0) {
    /**
     * 2. TRUY VẤN SQL:
     * - Lấy class_id từ bảng exam_sessions.
     * - Nối với bảng class_members để tìm sinh viên.
     * - Nối với bảng account để lấy Mã SV (code).
     * - Nối với bảng profile để lấy Họ tên.
     */
    $sql = "SELECT a.code as student_code, p.first_name, p.last_name 
            FROM exam_sessions es
            JOIN class_members cm ON es.class_id = cm.class_id
            JOIN account a ON cm.student_id = a.id
            JOIN profile p ON a.id = p.account_id
            WHERE es.id = $session_id
            ORDER BY p.first_name ASC";
    
    $res = mysqli_query($conn, $sql);
    
    if ($res && mysqli_num_rows($res) > 0) {
        echo "<div class='table-responsive' style='overflow-x: auto; width: 100%;'>";
        echo "<table class='student-list-table' style='width: 100%; border-collapse: collapse; min-width: 400px;'>";
        echo "<thead>
                <tr style='background: #f1f4f9; color: #444;'>
                    <th style='border: 1px solid #dee2e6; padding: 12px 8px; text-align: center;'>STT</th>
                    <th style='border: 1px solid #dee2e6; padding: 12px 8px; text-align: center;'>Mã SV</th>
                    <th style='border: 1px solid #dee2e6; padding: 12px 8px; text-align: left;'>Họ tên</th>
                </tr>
            </thead>";
        echo "<tbody>";
        
        $stt = 1;
        while ($row = mysqli_fetch_assoc($res)) {
            $fullName = $row['last_name'] . " " . $row['first_name'];
            echo "<tr style='border-bottom: 1px solid #eee;'>";
            echo "<td align='center' style='padding: 10px; border: 1px solid #dee2e6;'>" . $stt++ . "</td>";
            echo "<td align='center' style='padding: 10px; border: 1px solid #dee2e6;'>" . htmlspecialchars($row['student_code']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>" . htmlspecialchars($fullName) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "</div>"; 
        echo "<p style='margin-top: 15px; font-size: 0.85rem; color: #777; font-style: italic;'>* Tổng số sinh viên: " . ($stt - 1) . "</p>";
    } else {
        // Trường hợp lớp chưa có sinh viên
        echo "<div style='padding: 30px; text-align: center; color: #999;'>";
        echo "📭 Hiện tại chưa có sinh viên nào tham gia lớp học phần này.";
        echo "</div>";
    }
} else {
    echo "<p style='color: red; text-align: center;'>Yêu cầu không hợp lệ (Thiếu ID ca thi).</p>";
}
?>
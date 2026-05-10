<?php
require '../../../db_connection.php'; // Đảm bảo đường dẫn đúng đến file kết nối DB

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

if ($session_id === 0) {
    echo "<tr><td colspan='7' style='text-align:center;'>Thiếu thông tin buổi học.</td></tr>";
    exit;
}

// 1. Lấy class_id từ session_id trước để biết cần gọi danh sách SV lớp nào
$sql_session = "SELECT class_id, mode FROM class_sessions WHERE id = ?";
$stmt_session = $conn->prepare($sql_session);
$stmt_session->bind_param("i", $session_id);
$stmt_session->execute();
$session_info = $stmt_session->get_result()->fetch_assoc();
$class_id = $session_info['class_id'];
$mode = $session_info['mode'];

// 2. Lấy danh sách SV lớp đó và trạng thái điểm danh hiện tại (nếu đã điểm danh rồi)
$sql = "SELECT 
            p.account_id, 
            a.code, 
            CONCAT(p.last_name, ' ', p.first_name) as full_name, 
            p.year, 
            att.status
        FROM class_results cr
        JOIN profile p ON cr.account_id = p.account_id
        JOIN account a ON p.account_id = a.id
        LEFT JOIN attendance att ON att.student_id = p.account_id AND att.session_id = ?
        WHERE cr.class_id = ? 
          AND a.role = 'student'
        ORDER BY p.first_name ASC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi SQL: " . $conn->error); // Dòng này sẽ in ra lỗi cụ thể từ MySQL
}
$stmt->bind_param("ii", $session_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stt = 1;
    while($row = $result->fetch_assoc()) {
        $db_status = $row['status'] ?? ''; // Trạng thái từ database
        $status_text = "Chưa điểm danh";
        $status_color = "#999";

        // Map text và màu sắc hiển thị
        if ($db_status == 'Present') { $status_text = "Có mặt"; $status_color = "#28a745"; }
        elseif ($db_status == 'Late') { $status_text = "Muộn/Về sớm"; $status_color = "#fd7e14"; }
        elseif ($db_status == 'Excused') { $status_text = "Vắng có phép"; $status_color = "#007bff"; }
        elseif ($db_status == 'Unexcused') { $status_text = "Vắng không phép"; $status_color = "#dc3545"; }

        echo "<tr>
                <td>{$stt}</td>
                <td>{$row['code']}</td>
                <td><strong>{$row['full_name']}</strong></td>
                <td>{$row['year']}</td>
                <td>{$mode}</td>
                <td>
                    <span id='status-text-{$row['account_id']}' style='font-weight:bold; color: {$status_color};'>
                        {$status_text}
                    </span>
                </td>
                <td class='attendance-actions'>
                    <div style='display: flex; gap: 10px; justify-content: center;'>
                        <label title='Có mặt'><input type='radio' name='att[{$row['account_id']}]' value='Present' ".($db_status == 'Present' ? 'checked' : '')." onchange='updateStatusText({$row['account_id']}, \"Present\")'> C</label>
                        <label title='Muộn'><input type='radio' name='att[{$row['account_id']}]' value='Late' ".($db_status == 'Late' ? 'checked' : '')." onchange='updateStatusText({$row['account_id']}, \"Late\")'> M</label>
                        <label title='Vắng có phép'><input type='radio' name='att[{$row['account_id']}]' value='Excused' ".($db_status == 'Excused' ? 'checked' : '')." onchange='updateStatusText({$row['account_id']}, \"Excused\")'> CP</label>
                        <label title='Vắng không phép'><input type='radio' name='att[{$row['account_id']}]' value='Unexcused' ".($db_status == 'Unexcused' ? 'checked' : '')." onchange='updateStatusText({$row['account_id']}, \"Unexcused\")'> KP</label>
                    </div>
                </td>
              </tr>";
        $stt++;
    }
} else {
    echo "<tr><td colspan='7' style='text-align:center;'>Lớp học này hiện chưa có sinh viên.</td></tr>";
}
?>
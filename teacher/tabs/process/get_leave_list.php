<?php
require '../../../db_connection.php';
$date = $_GET['date'];
$class_id = $_GET['class_id'];

$sql = "SELECT 
            a.code, 
            CONCAT(p.last_name, ' ', p.first_name) as full_name, 
            lr.reason, 
            lr.evidence 
        FROM leave_requests lr
        JOIN profile p ON lr.student_id = p.account_id
        JOIN account a ON p.account_id = a.id 
        WHERE lr.class_id = ? 
          AND lr.date = ? 
          AND lr.status = 'Duyệt' 
          AND a.role = 'student'
        ORDER BY p.first_name ASC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("is", $class_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['student_code']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['reason']}</td>
                <td>" . ($row['evidence'] ? "<a href='{$row['evidence']}' target='_blank'>📷 Xem</a>" : "Không có") . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>Không có sinh viên nào nghỉ phép được duyệt.</td></tr>";
}
?>
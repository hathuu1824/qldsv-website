<?php
require_once '../../db_connection.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$class_id = intval($data['class_id']);
$grades = $data['grades'];

// 1. Kiểm tra deadline trước khi lưu
$sql_check = "SELECT deadline FROM classes WHERE id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $class_id);
$stmt_check->execute();
$res = $stmt_check->get_result()->fetch_assoc();

if ($res['deadline'] && strtotime($res['deadline']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Đã quá hạn nhập điểm, không thể lưu.']);
    exit;
}

// 2. Lưu/Cập nhật điểm vào bảng class_results
$conn->begin_transaction();

try {
    $sql_upsert = "INSERT INTO class_results (class_id, student_id, process_score, midterm_score, final_score, total_score) 
                   VALUES (?, ?, ?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                   process_score = VALUES(process_score), 
                   midterm_score = VALUES(midterm_score), 
                   final_score = VALUES(final_score), 
                   total_score = VALUES(total_score)";

    $stmt = $conn->prepare($sql_upsert);

    foreach ($grades as $g) {
        $stmt->bind_param("iidddd", 
            $class_id, 
            $g['student_id'], 
            $g['cc'], 
            $g['gk'], 
            $g['ck'], 
            $g['total']
        );
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
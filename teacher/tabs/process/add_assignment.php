<?php
require '../../../db_connection.php'; 

// Đảm bảo kết nối nhận đúng tiếng Việt có dấu
$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Lấy dữ liệu từ form
    $class_id    = $_POST['class_id'];
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $type        = $_POST['type']; // Giá trị: 'Bài tập' hoặc 'Bài kiểm tra'
    $deadline    = $_POST['deadline'];

    // 2. Chuẩn bị câu lệnh SQL (5 dấu hỏi tương ứng với 5 cột)
    $sql = "INSERT INTO assignments (class_id, title, description, type, deadline, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // 3. Bind tham số: 
        // i: class_id (integer)
        // s: title (string)
        // s: description (string)
        // s: type (string)
        // s: deadline (string)
        // Tổng cộng là "issss"
        $stmt->bind_param("issss", $class_id, $title, $description, $type, $deadline);

        // 4. Thực thi
        if ($stmt->execute()) {
            // Thành công: Quay lại trang danh sách bài tập
            header("Location: ../../class_detail.php?id=$class_id&tab=homework&status=success");
            exit();
        } else {
            echo "Lỗi thực thi: " . $stmt->error;
        }
    } else {
        echo "Lỗi chuẩn bị câu lệnh (Prepare): " . $conn->error;
    }
}
?>
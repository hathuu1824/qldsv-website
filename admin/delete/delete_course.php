<?php
session_start();
require '../../db_connection.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// 2. Lấy ID học phần cần xóa và Faculty ID để quay lại trang đúng vị trí
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Lấy faculty_id từ URL (nếu có) để khi xóa xong quay về đúng khoa đang xem
    $faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';
    $redirect_url = "../course.php" . ($faculty_id ? "?faculty=$faculty_id" : "");

    try {
        // 3. Thực hiện lệnh xóa trong bảng subjects
        // Lưu ý: Nếu bảng 'classes' hoặc 'grades' có liên kết khóa ngoại với bảng này, 
        // bạn cần kiểm tra xem có dữ liệu liên quan không trước khi xóa.
        $sql_del = "DELETE FROM subjects WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql_del)) {
            // Xóa thành công
            header("Location: $redirect_url" . (strpos($redirect_url, '?') !== false ? "&" : "?") . "msg=delete_success");
        } else {
            throw new Exception("Không thể xóa học phần");
        }
        exit();

    } catch (Exception $e) {
        // Lỗi (có thể do ràng buộc khóa ngoại nếu môn học đã có điểm hoặc có lớp)
        header("Location: $redirect_url" . (strpos($redirect_url, '?') !== false ? "&" : "?") . "msg=error");
        exit();
    }
} else {
    // Nếu không có ID thì quay về trang danh sách
    header("Location: ../course.php");
    exit();
}
?>
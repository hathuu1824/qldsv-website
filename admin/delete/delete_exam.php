<?php
session_start();
require '../../db_connection.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// 2. Kiểm tra ID ca thi truyền vào
if (isset($_GET['id'])) {
    $session_id = mysqli_real_escape_string($conn, $_GET['id']);
    $faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';
    
    // URL quay lại trang quản lý kèm ID khoa đang chọn
    $redirect_url = "../calendar.php?faculty=" . $faculty_id;

    try {
        /**
         * BƯỚC 1: KIỂM TRA SỐ LƯỢNG SINH VIÊN TRONG LỚP CỦA CA THI NÀY
         * Chúng ta đếm tổng sinh viên trong bảng class_members
         */
        $sql_check_total = "SELECT COUNT(*) as total 
                            FROM exam_sessions es
                            JOIN class_members cm ON es.class_id = cm.class_id
                            WHERE es.id = '$session_id'";
        
        $res_total = mysqli_query($conn, $sql_check_total);
        if (!$res_total) throw new Exception(mysqli_error($conn));
        $data_total = mysqli_fetch_assoc($res_total);

        /**
         * BƯỚC 2: LOGIC KIỂM TRA ĐIỀU KIỆN XÓA
         * Nếu tổng số sinh viên > 0, ta mới kiểm tra trạng thái tốt nghiệp.
         */
        if ($data_total['total'] > 0) {
            // Kiểm tra xem có sinh viên nào CHƯA tốt nghiệp không
            $sql_check_active = "SELECT COUNT(*) as active_count 
                                 FROM exam_sessions es
                                 JOIN class_members cm ON es.class_id = cm.class_id
                                 JOIN account a ON cm.student_id = a.id
                                 WHERE es.id = '$session_id' 
                                 AND a.status != 'Đã tốt nghiệp'";
            
            $res_active = mysqli_query($conn, $sql_check_active);
            if (!$res_active) throw new Exception(mysqli_error($conn));
            $data_active = mysqli_fetch_assoc($res_active);

            if ($data_active['active_count'] > 0) {
                // TRƯỜNG HỢP: Vẫn còn sinh viên đang học -> CHẶN XÓA
                header("Location: $redirect_url&msg=error_active_students");
                exit();
            }
        }

        /**
         * BƯỚC 3: THỰC HIỆN XÓA (Khi ca thi trống hoặc mọi người đã tốt nghiệp)
         */
        mysqli_begin_transaction($conn);

        // A. Xóa các dữ liệu phụ liên quan trong bảng exam_participants (nếu có)
        // Đây là bảng lưu danh sách thí sinh dự thi thực tế của ca này
        mysqli_query($conn, "DELETE FROM exam_participants WHERE exam_session_id = '$session_id'");

        // B. Xóa ca thi chính
        $sql_del_session = "DELETE FROM exam_sessions WHERE id = '$session_id'";
        if (!mysqli_query($conn, $sql_del_session)) {
            throw new Exception("Không thể thực hiện lệnh xóa ca thi.");
        }

        mysqli_commit($conn);
        
        // TRƯỜNG HỢP: Thành công
        header("Location: $redirect_url&msg=delete_success");

    } catch (Exception $e) {
        // Nếu có lỗi, khôi phục lại dữ liệu và trả về mã lỗi hệ thống
        mysqli_rollback($conn);
        header("Location: $redirect_url&msg=system_error");
    }
} else {
    // Nếu không có ID, quay lại trang chính
    header("Location: ../calendar.php");
}
exit();
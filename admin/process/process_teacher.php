<?php
session_start();
// Lùi 2 cấp để vào thư mục gốc kết nối DB
require '../../db_connection.php';

// Bật chế độ báo lỗi nghiêm ngặt để bắt lỗi SQL trong khối try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Kiểm tra quyền Admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// --- TRƯỜNG HỢP 1: THÊM GIẢNG VIÊN MỚI ---
if (isset($_POST['btn_add'])) {
    try {
        // 1. Lấy dữ liệu từ FORM
        $code = mysqli_real_escape_string($conn, $_POST['code']); // Mã giảng viên (MGV)
        $fullname = trim($_POST['fullname']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        
        // Lưu ý: Đối với GV, 'year' và 'academic_year' có thể dùng để quản lý thâm niên hoặc năm bắt đầu công tác
        $year = mysqli_real_escape_string($conn, $_POST['year']); 
        $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']); 
        
        $faculty_id = mysqli_real_escape_string($conn, $_POST['faculty_id']); // Khoa/Bộ môn công tác
        $password = password_hash('123456', PASSWORD_DEFAULT); // Mật khẩu mặc định

        // 2. Logic tách Họ và Tên (Vẫn dùng chung logic chuẩn Tiếng Việt)
        $parts = explode(' ', $fullname);
        $first_name = mysqli_real_escape_string($conn, array_pop($parts));
        $last_name = mysqli_real_escape_string($conn, implode(' ', $parts));

        // 3. Xử lý Upload ảnh đại diện
        $avatar = "";
        if (!empty($_FILES['avatar']['name'])) {
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar = time() . "_" . $code . "." . $file_ext;
            $target = "../../uploads/" . $avatar; 
            move_uploaded_file($_FILES['avatar']['tmp_name'], $target);
        }

        // 4. Bắt đầu Transaction
        mysqli_begin_transaction($conn);

        // Bước A: Chèn vào bảng account với role là 'teacher'
        $query_acc = "INSERT INTO account (code, password, role) VALUES ('$code', '$password', 'teacher')";
        mysqli_query($conn, $query_acc);
        $new_id = mysqli_insert_id($conn); 

        // Bước B: Chèn vào bảng profile
        $query_pro = "INSERT INTO profile (account_id, first_name, last_name, dob, gender, year, academic_year, faculty_id, avatar) 
                      VALUES ('$new_id', '$first_name', '$last_name', '$dob', '$gender', '$year', '$academic_year', '$faculty_id', '$avatar')";
        mysqli_query($conn, $query_pro);

        mysqli_commit($conn);
        // Chuyển hướng về trang quản lý giảng viên
        header("Location: ../teacher.php?faculty=$faculty_id&msg=add_success");
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: ../teacher.php?msg=error");
        exit();
    }
}

// --- TRƯỜNG HỢP 2: CẬP NHẬT THÔNG TIN GIẢNG VIÊN ---
if (isset($_POST['btn_edit'])) {
    try {
        // 1. Lấy dữ liệu
        $account_id = mysqli_real_escape_string($conn, $_POST['account_id']);
        $fullname = trim($_POST['fullname']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $year = mysqli_real_escape_string($conn, $_POST['year']);
        $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);

        // 2. Logic tách Họ và Tên
        $parts = explode(' ', $fullname);
        $first_name = mysqli_real_escape_string($conn, array_pop($parts));
        $last_name = mysqli_real_escape_string($conn, implode(' ', $parts));

        // 3. Chuẩn bị câu lệnh UPDATE
        $sql_update = "UPDATE profile SET 
                        first_name = '$first_name', 
                        last_name = '$last_name', 
                        dob = '$dob', 
                        gender = '$gender', 
                        email = '$email', 
                        phone = '$phone', 
                        year = '$year', 
                        academic_year = '$academic_year'";

        // 4. Xử lý ảnh mới nếu có
        if (!empty($_FILES['avatar']['name'])) {
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar = time() . "_" . $account_id . "." . $file_ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], "../../uploads/" . $avatar)) {
                $sql_update .= ", avatar = '$avatar'";
            }
        }

        $sql_update .= " WHERE account_id = '$account_id'";

        if (mysqli_query($conn, $sql_update)) {
            header("Location: ../teacher.php?msg=update_success");
        } else {
            throw new Exception("Lỗi cập nhật");
        }
        exit();

    } catch (Exception $e) {
        header("Location: ../teacher.php?msg=error");
        exit();
    }
}

header("Location: ../teacher.php");
exit();
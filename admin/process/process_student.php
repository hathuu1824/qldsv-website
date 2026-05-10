<?php
session_start();
// Lùi 2 cấp để vào thư mục gốc kết nối DB
require '../../db_connection.php';

// Bật chế độ báo lỗi nghiêm ngặt để bắt lỗi SQL trong khối try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Kiểm tra quyền Admin (Tùy chọn bảo mật)
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// --- TRƯỜNG HỢP 1: THÊM SINH VIÊN MỚI ---
if (isset($_POST['btn_add'])) {
    try {
        // 1. Lấy dữ liệu từ FORM
        $code = mysqli_real_escape_string($conn, $_POST['code']);
        $fullname = trim($_POST['fullname']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $year = mysqli_real_escape_string($conn, $_POST['year']); // Khóa (VD: K10)
        $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']); // Năm nhập học (VD: 2024)
        $faculty_id = mysqli_real_escape_string($conn, $_POST['faculty_id']);
        $password = password_hash('123456', PASSWORD_DEFAULT); // Mật khẩu mặc định

        // 2. Logic tách Họ và Tên
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

        // 4. Bắt đầu Transaction (Giao dịch)
        mysqli_begin_transaction($conn);

        // Bước A: Chèn vào bảng account
        $query_acc = "INSERT INTO account (code, password, role) VALUES ('$code', '$password', 'student')";
        mysqli_query($conn, $query_acc);
        $new_id = mysqli_insert_id($conn); // Lấy ID vừa tạo

        // Bước B: Chèn vào bảng profile (đầy đủ các cột)
        $query_pro = "INSERT INTO profile (account_id, first_name, last_name, dob, gender, year, academic_year, faculty_id, avatar) 
                      VALUES ('$new_id', '$first_name', '$last_name', '$dob', '$gender', '$year', '$academic_year', '$faculty_id', '$avatar')";
        mysqli_query($conn, $query_pro);

        // Xác nhận hoàn tất
        mysqli_commit($conn);
        header("Location: ../student.php?faculty=$faculty_id&msg=add_success");
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn); // Hoàn tác nếu lỗi
        // die($e->getMessage()); // Bỏ comment dòng này nếu muốn xem lỗi cụ thể khi debug
        header("Location: ../student.php?msg=error");
        exit();
    }
}

// --- TRƯỜNG HỢP 2: CẬP NHẬT THÔNG TIN SINH VIÊN ---
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

        // 3. Chuẩn bị câu lệnh UPDATE cơ bản
        $sql_update = "UPDATE profile SET 
                        first_name = '$first_name', 
                        last_name = '$last_name', 
                        dob = '$dob', 
                        gender = '$gender', 
                        email = '$email', 
                        phone = '$phone', 
                        year = '$year', 
                        academic_year = '$academic_year'";

        // 4. Nếu có chọn ảnh mới thì cập nhật thêm cột avatar
        if (!empty($_FILES['avatar']['name'])) {
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar = time() . "_" . $account_id . "." . $file_ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], "../../uploads/" . $avatar)) {
                $sql_update .= ", avatar = '$avatar'";
            }
        }

        $sql_update .= " WHERE account_id = '$account_id'";

        if (mysqli_query($conn, $sql_update)) {
            header("Location: ../student.php?msg=update_success");
        } else {
            throw new Exception("Lỗi cập nhật dữ liệu");
        }
        exit();

    } catch (Exception $e) {
        header("Location: ../student.php?msg=error");
        exit();
    }
}

// Nếu truy cập "lậu" không qua nút bấm
header("Location: ../student.php");
exit();
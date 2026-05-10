<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$account_id = $_SESSION['id'];
require '../db_connection.php';

// --- CHỈ XỬ LÝ KHI NHẤN NÚT LƯU (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname   = trim($_POST['first_name']);
    $lname   = trim($_POST['last_name']);
    $dob     = $_POST['dob'];
    $gender  = $_POST['gender'];
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // 1. Lấy thông tin cũ (để giữ lại avatar cũ nếu không upload ảnh mới)
    $sql_current = "SELECT avatar FROM profile WHERE account_id = ?";
    $stmt_curr = $conn->prepare($sql_current);
    $stmt_curr->bind_param("i", $account_id);
    $stmt_curr->execute();
    $res_curr = $stmt_curr->get_result();
    $row_curr = $res_curr->fetch_assoc();
    $avatar_path = $row_curr['avatar'] ?? '';

    // 2. Xử lý upload ảnh nếu có chọn file mới
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "../uploads/"; 
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true); 

        $file_extension = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
        $new_filename = "avatar_" . $account_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        $allow_types = ['jpg', 'png', 'jpeg', 'gif'];
        if (in_array(strtolower($file_extension), $allow_types)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_path = "uploads/" . $new_filename; 
            }
        }
    }

    // 3. Kiểm tra tồn tại để UPDATE hoặc INSERT
    $check_sql = "SELECT id FROM profile WHERE account_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("i", $account_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $update_sql = "UPDATE profile SET first_name=?, last_name=?, dob=?, gender=?, email=?, phone=?, address=?, avatar=? WHERE account_id=?";
        $stmt_save = $conn->prepare($update_sql);
        $stmt_save->bind_param("ssssssssi", $fname, $lname, $dob, $gender, $email, $phone, $address, $avatar_path, $account_id);
    } else {
        $insert_sql = "INSERT INTO profile (account_id, first_name, last_name, dob, gender, email, phone, address, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_save = $conn->prepare($insert_sql);
        $stmt_save->bind_param("issssssss", $account_id, $fname, $lname, $dob, $gender, $email, $phone, $address, $avatar_path);
    }

    if ($stmt_save->execute()) {
        header("Location: profile.php?msg=success");
        exit();
    }
}

// --- LẤY DỮ LIỆU HIỂN THỊ LÊN FORM ---
$sql = "SELECT a.code, p.first_name, p.last_name, p.dob, p.gender, p.email, p.phone, p.address, p.avatar 
        FROM account a 
        LEFT JOIN profile p ON a.id = p.account_id 
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$code    = $row['code'] ?? 'N/A';
$fname   = $row['first_name'] ?? '';
$lname   = $row['last_name'] ?? '';
$dob     = $row['dob'] ?? '';
$gender  = $row['gender'] ?? '';
$email   = $row['email'] ?? '';
$phone   = $row['phone'] ?? '';
$address = $row['address'] ?? '';
$avatar_display = !empty($row['avatar']) ? '../' . $row['avatar'] : '../images/profile.png';

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/profile.css">
    <title>Chỉnh sửa thông tin</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-container">
        <form action="edit.php" method="POST" enctype="multipart/form-data">
            <div class="profile-title">
                <h2>Chỉnh sửa thông tin cá nhân</h2>
                <div class="action-buttons">
                    <a href="profile.php" class="cancel-btn">Hủy bỏ</a>
                    <button type="submit" class="save-btn">Lưu thay đổi</button>
                </div>
            </div>
            
            <div class="profile-info">
                <div class="profile-img">
                    <img src="<?php echo $avatar_display; ?>" alt="Ảnh đại diện" class="card-img">
                    <div class="upload-btn-wrapper" style="margin-top: 15px;">
                        <button type="button" class="edit-btn" onclick="document.getElementById('avatarInput').click();">Chọn ảnh</button>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;" onchange="previewImage(this);">
                    </div>
                </div>

                <div class="profile-content">
                    <div class="profile-row">
                        <p><strong>Họ và tên lót:</strong> <input type="text" name="last_name" value="<?php echo htmlspecialchars($lname); ?>" required></p>
                        <p><strong>Tên:</strong> <input type="text" name="first_name" value="<?php echo htmlspecialchars($fname); ?>" required></p>
                    </div>

                    <div class="profile-row">
                        <p><strong>Mã định danh:</strong> <input type="text" value="<?php echo htmlspecialchars($code); ?>" disabled class="readonly-input" style="background: #eee; cursor: not-allowed;"></p>
                        <p><strong>Ngày sinh:</strong> <input type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>"></p>
                    </div>

                    <div class="profile-row">
                        <p><strong>Giới tính:</strong> 
                            <select name="gender">
                                <option value="Nam" <?php echo ($gender == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                                <option value="Nữ" <?php echo ($gender == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                                <option value="Khác" <?php echo ($gender == 'Khác') ? 'selected' : ''; ?>>Khác</option>
                            </select>
                        </p>
                        <p><strong>Email:</strong> <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>"></p>
                    </div>

                    <div class="profile-row">
                        <p><strong>Số điện thoại:</strong> <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>"></p>
                        <p><strong>Địa chỉ:</strong> <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>"></p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Hàm xem trước ảnh ngay sau khi chọn file
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.card-img').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
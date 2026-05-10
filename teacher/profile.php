<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$account_id = $_SESSION['id'];
require '../db_connection.php'; 

$full_name = "Chưa cập nhật";
$code      = "N/A";

$sql = "SELECT a.code, p.first_name, p.last_name, p.dob, p.gender, p.email, p.phone, p.address, p.major_id, m.major_name, p.faculty_id, f.faculty_name, p.year
        FROM account a 
        LEFT JOIN profile p ON a.id = p.account_id 
        LEFT JOIN majors m ON p.major_id = m.id 
        LEFT JOIN faculties f ON p.faculty_id = f.id 
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $code = $row['code'] ?: "N/A";
    
    $fname = trim($row['first_name'] ?? '');
    $lname = trim($row['last_name'] ?? '');

    if ($fname !== '' || $lname !== '') {
        $full_name = trim($lname . ' ' . $fname);
    }

    if (!empty($row['dob']) && $row['dob'] != '0000-00-00') {
        $dob = date("d/m/Y", strtotime($row['dob']));
    } else {
        $dob = "Chưa cập nhật";
    }
    $gender   = $row['gender'] ?: "Chưa cập nhật";
    $email    = $row['email'] ?: "Chưa cập nhật";
    $phone    = $row['phone'] ?: "Chưa cập nhật";
    $address  = $row['address'] ?: "Chưa cập nhật";
    $year     = $row['year'] ?: "Chưa cập nhật";
    $major    = $row['major_name'] ?: "Chưa cập nhật";
    $faculty  = $row['faculty_name'] ?: "Chưa cập nhật";
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/profile.css">
    <title>Thông tin cá nhân</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-container">
        <div class="profile-title">
            <h2>Thông tin cá nhân</h2>
            <button class="edit-btn" onclick="window.location.href='edit.php'">Chỉnh sửa thông tin</button>
        </div>
        <div class="profile-info">
            <div class="profile-img">
                <img src="../images/profile.png" alt="Ảnh đại diện">
                <div class="profile-button">
                    <button class="edit-btn" onclick="window.location.href='edit.php'">Chỉnh sửa ảnh</button>
                </div>
            </div>
            <div class="profile-content">
                <div class="profile-row">
                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($full_name); ?></p>
                    <p><strong>Mã giảng viên:</strong> <?php echo htmlspecialchars($code); ?></p>
                </div>
                <div class="profile-row">
                    <p><strong>Ngày sinh:</strong> <?php echo htmlspecialchars($dob); ?></p>
                    <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($gender); ?></p>
                </div>
                <div class="profile-row">
                    <p><strong>Ngành công tác:</strong> <?php echo htmlspecialchars($faculty); ?></p>
                </div>
                <div class="profile-row">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($phone); ?></p>
                </div>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($address); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
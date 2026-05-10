<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php"); 
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

require '../db_connection.php'; 

// Lấy thông tin tìm kiếm và bộ lọc
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';

if (empty($faculty_id)) {
    $get_first_faculty = mysqli_query($conn, "SELECT id FROM faculties ORDER BY faculty_name ASC LIMIT 1");
    if ($first_f = mysqli_fetch_assoc($get_first_faculty)) {
        $faculty_id = $first_f['id'];
        header("Location: teacher.php?faculty=" . $faculty_id);
        exit();
    }
}

$sql_gv = "SELECT p.*, 
               CONCAT_WS(' ', p.last_name, p.first_name) AS fullname, 
               a.code, a.role 
        FROM profile p 
        INNER JOIN account a ON p.account_id = a.id 
        WHERE a.role = 'teacher'"; 

if ($search !== '') {
    $sql_gv .= " AND (p.first_name LIKE '%$search%' 
                OR p.last_name LIKE '%$search%' 
                OR a.code LIKE '%$search%')";
}

if ($faculty_id !== '') {
    $sql_gv .= " AND p.faculty_id = '$faculty_id'";
}

$sql_gv .= " ORDER BY a.code ASC";

$result_gv = mysqli_query($conn, $sql_gv);
$total_teachers = mysqli_num_rows($result_gv);

$faculties_res = mysqli_query($conn, "SELECT * FROM faculties ORDER BY faculty_name ASC");
$faculties = [];
while($f = mysqli_fetch_assoc($faculties_res)) {
    $faculties[] = $f;
}

// Lấy mã giảng viên lớn nhất hiện có 
$sql_max_code = "SELECT code FROM account WHERE role = 'teacher' AND code LIKE 'GV%' ORDER BY code DESC LIMIT 1";
$res_max = mysqli_query($conn, $sql_max_code);

$next_id = 1; 

if ($row_max = mysqli_fetch_assoc($res_max)) {
    $last_code = $row_max['code'];
    $last_num = (int)substr($last_code, 2); 
    $next_id = $last_num + 1;
}

$next_code_string = "GV" . str_pad($next_id, 2, "0", STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Quản lý giảng viên</title>
</head>
<body>
    <?php include 'header.php' ?>
    <main class="main-container">
        <div class="title-container">
            <h2>Quản lý giảng viên</h2>
            <div class="filter-group">
                <select id="faculty-select" onchange="filterByFaculty(this.value)">
                    <?php foreach($faculties as $f): ?> <option value="<?= $f['id'] ?>" <?= ($faculty_id == $f['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['faculty_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-container">
            <div class="table-card">
                <div class="table-title">
                    <h3>Tổng số giảng viên: <span class="sv-count"><?= $total_teachers ?></span></h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm tên hoặc mã GV..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal()">+ Thêm giảng viên</button>
                    </div>
                </div>
                <div class="sv-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Ảnh</th>
                                <th>MGV</th>
                                <th>Họ và tên</th>
                                <th>Ngày sinh</th>
                                <th>Giới tính</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Lớp phụ trách</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                if ($total_teachers > 0): 
                                    $stt = 1; 
                                    while ($row = mysqli_fetch_assoc($result_gv)): 
                            ?>
                                <tr>
                                    <td style="text-align: center;"><?= $stt++ ?></td>
                                    <td>
                                        <?php 
                                            $display_name = !empty($row['fullname']) ? $row['fullname'] : ($row['code'] ?? 'S');
                                            $first_letter = mb_strtoupper(mb_substr($display_name, 0, 1, 'UTF-8'), 'UTF-8');

                                            $palette = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c', '#34495e'];
                                            $color_index = $row['account_id'] % count($palette);
                                            $bg_color = $palette[$color_index];

                                            $avatar_url = "../uploads/" . $row['avatar'];
                                            $has_avatar = !empty($row['avatar']) && file_exists($avatar_url);
                                        ?>
                                        <div class="avatar-circle" style="background-color: <?= $bg_color ?>;"> 
                                            <?php if ($has_avatar): ?>
                                                <img src="<?= $avatar_url ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <span><?= $first_letter ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['code']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= !empty($row['dob']) ? date('d/m/Y', strtotime($row['dob'])) : '---' ?></td>
                                    <td>
                                        <?php 
                                            if($row['gender'] == 'Nam') echo 'Nam';
                                            elseif($row['gender'] == 'Nữ') echo 'Nữ';
                                            else echo 'Khác';
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['year']) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Sửa</button>
                                            <a href="delete/delete_teacher.php?id=<?= $row['account_id'] ?>" class="btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 20px; color: #888;">
                                        Không có dữ liệu giảng viên nào để hiển thị.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <form action="process/process_teacher.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h2>Thêm giảng viên mới</h2>
                    <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ảnh đại diện</label>
                            <input type="file" name="avatar" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Mã giảng viên</label>
                            <input type="text" name="code" id="add_code" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Họ và tên</label>
                            <input type="text" name="fullname" required>
                        </div>
                        <div class="form-group">
                            <label>Ngày sinh</label>
                            <input type="date" name="dob" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Giới tính</label>
                            <select name="gender">
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Năm công tác</label>
                            <input type="number" name="academic_year" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Khoa công tác</label>
                            <input type="text" value="<?php 
                                foreach($faculties as $f) { 
                                    if($f['id'] == $faculty_id) {
                                        echo htmlspecialchars($f['faculty_name']); 
                                        break;
                                    }
                                } 
                            ?>" readonly class="readonly-input">
                            <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                        </div>
                        <div class="form-group">
                            <label>Lớp phụ trách</label>
                            <input type="text" name="year" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_add" class="btn-submit">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <form action="process/process_teacher.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h3>Chỉnh sửa thông tin</h3>
                    <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="account_id" id="edit_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Đổi ảnh đại diện</label>
                            <input type="file" name="avatar" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Họ và tên</label>
                            <input type="text" name="fullname" id="edit_fullname" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày sinh</label>
                            <input type="date" name="dob" id="edit_dob" required>
                        </div>
                        <div class="form-group">
                            <label>Giới tính</label>
                            <select name="gender" id="edit_gender">
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label>SĐT</label>
                            <input type="text" name="phone" id="edit_phone" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Năm công tác</label>
                            <input type="text" name="academic_year" id="edit_year" required>
                        </div>
                        <div class="form-group">
                            <label>Lớp phụ trách</label>
                            <input type="text" name="year" id="edit_class" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_edit" class="btn-submit">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const nextTeacherCode = "<?php echo $next_code_string; ?>";

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
            
            const inputCode = document.getElementById('add_code');
            if (inputCode) {
                inputCode.value = nextTeacherCode;
            }
        }
    </script>
    <script src="../javascript/profile_admin.js"></script>
</body>
</html>
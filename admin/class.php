<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

require '../db_connection.php'; 

// Xử lý cập nhật hạn nhập điểm khi Admin nhấn "Lưu hạn"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_deadline'])) {
    $c_id = intval($_POST['class_id']);
    $new_deadline = $_POST['deadline']; // Định dạng: YYYY-MM-DDTHH:MM

    if ($c_id > 0) {
        $sql_update = "UPDATE classes SET deadline = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $new_deadline, $c_id);
        
        if ($stmt_update->execute()) {
            $success_msg = "Đã cập nhật hạn nhập điểm thành công!";
        } else {
            $error_msg = "Lỗi khi cập nhật hạn: " . $conn->error;
        }
        $stmt_update->close();
    }
}

// 1. Lấy thông tin tìm kiếm và bộ lọc
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';

// Lấy danh sách Khoa để đổ vào dropdown filter
$faculties_res = mysqli_query($conn, "SELECT * FROM faculties ORDER BY faculty_name ASC");
$faculties = [];
while($f = mysqli_fetch_assoc($faculties_res)) {
    $faculties[] = $f;
}

if (empty($faculty_id) && !empty($faculties)) {
    $faculty_id = $faculties[0]['id'];
    header("Location: class.php?faculty=" . $faculty_id . (!empty($search) ? "&search=$search" : ""));
    exit();
}

// 2. Lấy dữ liệu bổ trợ cho Modal (Danh sách môn học và Giảng viên)
// Chỉ lấy môn học thuộc khoa đang chọn
$subjects_res = mysqli_query($conn, "SELECT s.id, s.subject_name FROM subjects s 
                                     JOIN majors m ON s.major_id = m.id 
                                     WHERE m.faculty_id = '$faculty_id'");
$teachers_res = mysqli_query($conn, "SELECT p.account_id, CONCAT(p.last_name, ' ', p.first_name) as fullname 
                                     FROM profile p 
                                     JOIN account a ON p.account_id = a.id 
                                     WHERE a.role = 'teacher' AND p.faculty_id = '$faculty_id'");

// 3. Truy vấn danh sách Lớp học (Join với subjects và profile giảng viên)
$sql_classes = "SELECT c.*, s.subject_name, CONCAT(p.last_name, ' ', p.first_name) as teacher_name 
                FROM classes c 
                INNER JOIN subjects s ON c.subject_id = s.id 
                INNER JOIN majors m ON s.major_id = m.id 
                LEFT JOIN profile p ON c.account_id = p.account_id 
                WHERE m.faculty_id = '$faculty_id'"; 

if ($search !== '') {
    $sql_classes .= " AND (c.class_name LIKE '%$search%' OR c.class_code LIKE '%$search%')";
}

$sql_classes .= " ORDER BY c.semester DESC, c.class_code ASC";
$result_classes = mysqli_query($conn, $sql_classes);
$total_classes = mysqli_num_rows($result_classes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Quản lý lớp học</title>
</head>
<body>
    <?php include 'header.php' ?>
    <main class="main-container">
        <div class="title-container">
            <h2>Quản lý lớp học</h2>
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
                    <h3>Tổng số lớp học: <span class="sv-count"><?= $total_classes ?></span></h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm tên hoặc mã lớp..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal()">+ Thêm lớp học</button>
                    </div>
                </div>
                <div class="sv-table" style="max-height: 500px; overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã lớp</th>
                                <th>Tên lớp</th>
                                <th>Nhóm</th>
                                <th>Kỳ học</th>
                                <th>Giảng viên</th>
                                <th>Trạng thái</th>
                                <th>Hạn nhập điểm</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            if ($total_classes > 0):
                                while ($row = mysqli_fetch_assoc($result_classes)): 
                                    $full_class_code = $row['class_code'] . '-' . $row['group_id'];
                            ?>
                                <tr>
                                    <td align="center"><?= $stt++ ?></td>
                                    <td><strong><?= htmlspecialchars($full_class_code) ?></strong></td>
                                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                                    <td><?= htmlspecialchars($row['group_id']) ?></td>
                                    <td align="center"><?= $row['semester'] ?></td>
                                    <td><?= htmlspecialchars($row['teacher_name'] ?? 'Chưa phân công') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace(' ', '-', $row['status']) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: flex; gap: 5px;">
                                            <input type="hidden" name="class_id" value="<?= $row['id'] ?>">
                                            
                                            <input type="datetime-local" 
                                                name="deadline" 
                                                value="<?= $row['deadline'] ? date('Y-m-d\TH:i', strtotime($row['deadline'])) : '' ?>"
                                                style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">

                                            <button type="submit" name="update_deadline" 
                                                    style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                                Lưu hạn
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Sửa</button>
                                            <a href="delete/delete_class.php?id=<?= $row['id'] ?>&faculty=<?= $faculty_id ?>" class="btn-delete" onclick="return confirm('Xóa lớp này?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="8" align="center">Không tìm thấy lớp học nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <form action="process/process_class.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h2>Thêm học phần mới</h2>
                    <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Môn học</label>
                            <select name="subject_id" required>
                                <option value="" disabled selected>Chọn môn học</option>
                                <?php while($s = mysqli_fetch_assoc($subjects_res)): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Giảng viên</label>
                            <select name="account_id">
                                <option value="" disabled selected>Chọn giảng viên</option>
                                <?php while($t = mysqli_fetch_assoc($teachers_res)): ?>
                                    <option value="<?= $t['account_id'] ?>"><?= htmlspecialchars($t['fullname']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mã học phần</label>
                            <input type="text" name="class_code" required>
                        </div>
                        <div class="form-group">
                            <label>Nhóm</label>
                            <input type="number" name="group_id" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tên lớp</label>
                            <input type="text" name="class_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Học kỳ</label>
                            <input type="number" name="semester" required>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status">
                                <option value="Chưa bắt đầu" selected>Chưa bắt đầu</option>
                                <option value="Đang học">Đang học</option>
                                <option value="Đã kết thúc">Đã kết thúc</option>
                            </select>
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
            <form action="process/process_class.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h3>Chỉnh sửa thông tin</h3>
                    <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-group">
                        <label>Tên lớp</label>
                        <input type="text" name="class_name" id="edit_name" required readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mã học phần</label>
                            <input type="text" name="class_code" id="edit_code" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label>Nhóm</label>
                            <input type="number" name="group_id" id="edit_group" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Giảng viên</label>
                            <select name="account_id" id="edit_account_id">
                                <option value=""></option>
                                <?php 
                                mysqli_data_seek($teachers_res, 0); 
                                while($t = mysqli_fetch_assoc($teachers_res)): ?>
                                    <option value="<?= $t['account_id'] ?>"><?= htmlspecialchars($t['fullname']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status" id="edit_status">
                                <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                <option value="Đang học">Đang học</option>
                                <option value="Đã kết thúc">Đã kết thúc</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Học kỳ</label>
                        <input type="number" name="semester" id="edit_semester" required readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_edit" class="btn-submit">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
    </script>
    <script src="../javascript/class_admin.js"></script>
</body>
</html>
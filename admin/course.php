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
        $search_param = !empty($search) ? "&search=" . urlencode($search) : "";
        header("Location: course.php?faculty=" . $faculty_id . $search_param);
        exit();
    }
}

$faculties_res = mysqli_query($conn, "SELECT * FROM faculties ORDER BY faculty_name ASC");
$faculties = [];
while($f = mysqli_fetch_assoc($faculties_res)) {
    $faculties[] = $f;
}

$majors_in_faculty = [];
if (!empty($faculty_id)) {
    $m_res = mysqli_query($conn, "SELECT * FROM majors WHERE faculty_id = '$faculty_id' ORDER BY major_name ASC");
    while($m = mysqli_fetch_assoc($m_res)) {
        $majors_in_faculty[] = $m;
    }
}

// Truy vấn danh sách học phần theo khoa và tìm kiếm
$sql_hp = "SELECT s.*, m.major_name 
           FROM subjects s 
           INNER JOIN majors m ON s.major_id = m.id 
           INNER JOIN faculties f ON m.faculty_id = f.id 
           WHERE f.id = '$faculty_id'"; 

if ($search !== '') {
    $sql_hp .= " AND (s.subject_name LIKE '%$search%' OR s.subject_code LIKE '%$search%')";
}

$sql_hp .= " ORDER BY s.subject_code ASC";
$result_hp = mysqli_query($conn, $sql_hp);
$total_subjects = mysqli_num_rows($result_hp);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Quản lý học phần</title>
</head>
<body>
    <?php include 'header.php' ?>
    <main class="main-container">
        <div class="title-container">
            <h2>Quản lý học phần</h2>
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
                    <h3>Tổng số học phần: <span class="sv-count"><?= $total_subjects ?></span></h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm tên hoặc mã HP..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal()">+ Thêm học phần</button>
                    </div>
                </div>
                <div class="sv-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã HP</th>
                                <th>Tên học phần</th>
                                <th>Tín chỉ</th>
                                <th>Chuyên ngành</th>
                                <th>Loại</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            if ($total_subjects > 0):
                                while ($row = mysqli_fetch_assoc($result_hp)): 
                            ?>
                                <tr>
                                    <td align="center"><?= $stt++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['subject_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td align="center"><?= $row['credit'] ?></td>
                                    <td><?= htmlspecialchars($row['major_name']) ?></td>
                                    <td><?= $row['is_e'] ? 'Tự chọn' : 'Bắt buộc' ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Sửa</button>
                                            <a href="delete/delete_course.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Xóa học phần này?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="7" align="center">Khoa này hiện chưa có học phần nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <form action="process/process_course.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h2>Thêm học phần mới</h2>
                    <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Chuyên ngành</label>
                            <select name="major_id" required>
                                <?php foreach($majors_in_faculty as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['major_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mã HP</label>
                            <input type="text" name="subject_code" maxlength="6" required>
                        </div>
                        <div class="form-group">
                            <label>Tên HP</label>
                            <input type="text" name="subject_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số tín chỉ</label>
                            <input type="number" name="credit" min="1" max="9" required>
                        </div>
                        <div class="form-group">
                            <label>Hình thức HP</label>
                            <select name="is_e">
                                <option value="0">Bắt buộc</option>
                                <option value="1">Tự chọn</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mô tả chi tiết</label>
                        <textarea name="description" rows="3"></textarea>
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
            <form action="process/process_course.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h3>Chỉnh sửa thông tin</h3>
                    <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Chuyên ngành</label>
                            <select name="major_id" id="edit_major_id">
                                <?php 
                                $all_m = mysqli_query($conn, "SELECT * FROM majors ORDER BY major_name ASC");
                                while($am = mysqli_fetch_assoc($all_m)): ?>
                                    <option value="<?= $am['id'] ?>"><?= htmlspecialchars($am['major_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mã HP</label>
                            <input type="text" name="subject_code" id="edit_code" required>
                        </div>
                        <div class="form-group">
                            <label>Tên HP</label>
                            <input type="text" name="subject_name" id="edit_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số tín chỉ</label>
                            <input type="number" name="credit" id="edit_credit" required>
                        </div>
                        <div class="form-group">
                            <label>Loại HP</label>
                            <select name="is_e" id="edit_is_e">
                                <option value="0">Bắt buộc</option>
                                <option value="1">Tự chọn</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" id="edit_desc" rows="3"></textarea>
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
    <script src="../javascript/course_admin.js"></script>
</body>
</html>
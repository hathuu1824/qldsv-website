<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

require '../db_connection.php'; 

// 1. Lấy thông tin tìm kiếm và bộ lọc Khoa
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';

$faculties_res = mysqli_query($conn, "SELECT * FROM faculties ORDER BY faculty_name ASC");
$faculties = [];
while($f = mysqli_fetch_assoc($faculties_res)) {
    $faculties[] = $f;
}

if (empty($faculty_id) && !empty($faculties)) {
    $faculty_id = $faculties[0]['id'];
    header("Location: major.php?faculty=" . $faculty_id . (!empty($search) ? "&search=$search" : ""));
    exit();
}

// 2. Truy vấn danh sách Chuyên ngành + Đếm số sinh viên
$sql_majors = "SELECT m.*, 
                (SELECT COUNT(*) FROM profile p WHERE p.major_id = m.id) AS student_count 
               FROM majors m 
               WHERE m.faculty_id = '$faculty_id'"; 

if ($search !== '') {
    $sql_majors .= " AND m.major_name LIKE '%$search%'";
}

$sql_majors .= " ORDER BY m.id ASC";
$result_majors = mysqli_query($conn, $sql_majors);
$total_majors = mysqli_num_rows($result_majors);

$sql_sessions = "SELECT mr.major_id, mr.date_start, mr.date_end, m.major_name, m.major_code
                 FROM major_registrations mr
                 JOIN majors m ON mr.major_id = m.id
                 WHERE m.faculty_id = '$faculty_id'
                 GROUP BY mr.major_id"; 

$res_sessions = mysqli_query($conn, $sql_sessions);

if (!$res_sessions) {
    $res_sessions = mysqli_query($conn, "SELECT 1 FROM majors WHERE 0"); 
}

$total_major_sessions = mysqli_num_rows($res_sessions);

// Tạo mã chuyên ngành
$next_major_code = "";
if (!empty($faculty_id)) {
    $f_res = mysqli_query($conn, "SELECT faculty_code FROM faculties WHERE id = '$faculty_id'");
    $f_data = mysqli_fetch_assoc($f_res);
    $f_prefix = $f_data['faculty_code'] ?? "MAJOR";

    $last_m_res = mysqli_query($conn, "SELECT major_code FROM majors 
                                       WHERE faculty_id = '$faculty_id' 
                                       AND major_code LIKE '{$f_prefix}_%' 
                                       ORDER BY major_code DESC LIMIT 1");
    
    $next_num = 1;
    if ($last_m = mysqli_fetch_assoc($last_m_res)) {
        $parts = explode('_', $last_m['major_code']);
        $last_num = (int)end($parts);
        $next_num = $last_num + 1;
    }
    $next_major_code = $f_prefix . "_" . str_pad($next_num, 2, "0", STR_PAD_LEFT);
}
$total_major_sessions = mysqli_num_rows($res_sessions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Quản lý chuyên ngành</title>
</head>
<body>
    <?php include 'header.php' ?>
    <main class="main-container">
        <div class="title-container">
            <h2>Quản lý chuyên ngành</h2>
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
                    <h3>Lịch đăng ký chuyên ngành</h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm lịch đăng ký..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal('addScheduleModal')">+ Thêm lịch đăng ký</button>
                    </div>
                </div>
                <div class="sv-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã chuyên ngành</th>
                                <th>Tên chuyên ngành</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1; 
                            if($total_major_sessions > 0):
                                while($row = mysqli_fetch_assoc($res_sessions)): 
                                    $today = date('Y-m-d H:i:s');
                                    $status_text = "Đang mở";
                                    $status_class = "status-approved";
                                    if ($today < $row['date_start']) {
                                        $status_text = "Sắp mở";
                                        $status_class = "status-pending";
                                    } elseif ($today > $row['date_end']) {
                                        $status_text = "Đã đóng";
                                        $status_class = "status-rejected";
                                    }
                            ?>
                            <tr>
                                <td align="center"><?= $stt++ ?></td>
                                <td align="center"><strong><?= htmlspecialchars($row['major_code']) ?></strong></td>
                                <td><?= htmlspecialchars($row['major_name']) ?></td>
                                <td align="center">
                                    <?= date('d/m/Y H:i', strtotime($row['date_start'])) ?>
                                </td>
                                <td align="center">
                                    <?= date('d/m/Y H:i', strtotime($row['date_end'])) ?>
                                </td>
                                <td align="center">
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= $status_text ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6" align="center">Không tìm thấy lịch đăng ký chuyên ngành nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-card">
                <div class="table-title">
                    <h3>Tổng số chuyên ngành: <span class="sv-count"><?= $total_majors ?></span></h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm tên chuyên ngành..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal('addModal')">+ Thêm chuyên ngành</button>
                    </div>
                </div>
                <div class="sv-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã chuyên ngành</th>
                                <th>Tên chuyên ngành</th>
                                <th>Số lượng SV</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            if ($total_majors > 0):
                                while ($row = mysqli_fetch_assoc($result_majors)): 
                            ?>
                                <tr>
                                    <td align="center"><?= $stt++ ?></td>
                                    <td align="center"><?= htmlspecialchars($row['major_code']) ?></td>
                                    <td><?= htmlspecialchars($row['major_name']) ?></td>
                                    <td align="center">
                                        <span class="student-count-badge"><?= $row['student_count'] ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; justify-content: center;">
                                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Sửa</button>
                                            <a href="delete/delete_major.php?id=<?= $row['id'] ?>&faculty=<?= $faculty_id ?>" 
                                               class="btn-delete" 
                                               onclick="return confirm('Xóa chuyên ngành này có thể ảnh hưởng đến dữ liệu sinh viên. Bạn chắc chắn chứ?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" align="center">Không tìm thấy chuyên ngành nào thuộc khoa này.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="addScheduleModal" class="modal">
        <div class="modal-content">
            <form action="process/process_major.php" method="POST">
                <div class="modal-header">
                    <h3>Thiết lập lịch đăng ký chuyên ngành</h3>
                    <span class="close-btn" onclick="closeModal('addScheduleModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-group">
                        <label>Chuyên ngành</label>
                        <select name="major_id" required>
                            <option value="" disabled selected>Chọn chuyên ngành</option>
                            <?php 
                            mysqli_data_seek($result_majors, 0); 
                            while($m = mysqli_fetch_assoc($result_majors)): 
                            ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['major_code']) ?> - <?= htmlspecialchars($m['major_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Thời gian bắt đầu</label>
                            <input type="datetime-local" name="date_start" required>
                        </div>
                        <div class="form-group">
                            <label>Thời gian kết thúc</label>
                            <input type="datetime-local" name="date_end" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_add_schedule" class="btn-submit">Kích hoạt lịch đăng ký</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <form action="process/process_major.php" method="POST">
                <div class="modal-header">
                    <h2>Thêm chuyên ngành mới</h2>
                    <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-group">
                        <label>Mã chuyên ngành</label>
                        <input type="text" name="major_code" value="<?= $next_major_code ?>" readonly style="background: #eee;">
                    </div>
                    <div class="form-group">
                        <label>Tên chuyên ngành</label>
                        <input type="text" name="major_name" placeholder="Ví dụ: Kỹ thuật phần mềm" required>
                    </div>
                    <p style="font-size: 0.85rem; color: #666; margin-top: 10px;">
                        * Chuyên ngành sẽ được thêm trực tiếp vào Khoa đang chọn.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_add" class="btn-submit">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <form action="process/process_major.php" method="POST">
                <div class="modal-header">
                    <h3>Chỉnh sửa chuyên ngành</h3>
                    <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-group">
                        <label>Tên chuyên ngành</label>
                        <input type="text" name="major_name" id="edit_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_edit" class="btn-submit">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../javascript/major_admin.js"></script>
</body>
</html>
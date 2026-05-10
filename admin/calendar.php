<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

require '../db_connection.php'; 

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';

// Lấy danh sách Khoa
$faculties_res = mysqli_query($conn, "SELECT * FROM faculties ORDER BY faculty_name ASC");
$faculties = [];
while($f = mysqli_fetch_assoc($faculties_res)) {
    $faculties[] = $f;
}

if (empty($faculty_id) && !empty($faculties)) {
    $faculty_id = $faculties[0]['id'];
    header("Location: calendar.php?faculty=" . $faculty_id . (!empty($search) ? "&search=$search" : ""));
    exit();
}

// Truy vấn danh sách Lịch thi
$sql_exams = "SELECT es.*, s.subject_name, c.class_code,
                (SELECT COUNT(*) FROM class_members cm WHERE cm.class_id = es.class_id) AS student_count 
              FROM exam_sessions es
              INNER JOIN classes c ON es.class_id = c.id
              INNER JOIN subjects s ON c.subject_id = s.id
              INNER JOIN majors m ON s.major_id = m.id
              WHERE m.faculty_id = '$faculty_id'";

if ($search !== '') {
    $sql_exams .= " AND (s.subject_name LIKE '%$search%' OR es.room LIKE '%$search%')";
}

$sql_exams .= " ORDER BY es.exam_date ASC, es.exam_time ASC";

$result_exams = mysqli_query($conn, $sql_exams);

if (!$result_exams) {
    die("Lỗi SQL: " . mysqli_error($conn) . " <br> Câu lệnh: " . $sql_exams);
}

$total_exams = mysqli_num_rows($result_exams);

$classes_res = mysqli_query($conn, "SELECT c.id, c.class_code, s.subject_name 
                                    FROM classes c 
                                    JOIN subjects s ON c.subject_id = s.id
                                    JOIN majors m ON s.major_id = m.id
                                    WHERE m.faculty_id = '$faculty_id'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Quản lý lịch thi</title>
</head>
<body>
    <?php include 'header.php' ?>
    <main class="main-container">
        <div class="title-container">
            <h2>Quản lý lịch thi</h2>
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
                    <h3>Tổng số môn thi: <span class="sv-count"><?= $total_exams ?></span></h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm tên học phần thi..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal()">+ Thêm lịch thi</button>
                    </div>
                </div>
                <div class="sv-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Học phần</th>
                                <th>Ngày thi</th>
                                <th>Giờ thi</th>
                                <th>Phòng thi</th>
                                <th>Số lượng SV</th>
                                <th>Chi tiết</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            if ($total_exams > 0):
                                while ($row = mysqli_fetch_assoc($result_exams)): 
                            ?>
                                <tr>
                                    <td align="center"><?= $stt++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['subject_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($row['class_code']) ?></small>
                                    </td>
                                    <td align="center"><?= date('d/m/Y', strtotime($row['exam_date'])) ?></td>
                                    <td align="center"><?= date('H:i', strtotime($row['exam_time'])) ?></td>
                                    <td align="center"><?= htmlspecialchars($row['room']) ?></td>
                                    <td align="center">
                                        <span class="student-count-badge"><?= $row['student_count'] ?></span>
                                    </td>
                                    <td align="center">
                                        <button class="btn-add" onclick="viewParticipants(<?= $row['id'] ?>, '<?= addslashes($row['subject_name']) ?>')">Xem SV</button>
                                    </td>
                                    <td align="center">
                                        <div style="display: flex; gap: 5px; justify-content: center;">
                                            <button class="btn-edit" onclick='openEditModal(<?= json_encode($row) ?>)'>Sửa</button>
                                            <a href="delete/delete_exam.php?id=<?= $row['id'] ?>&faculty=<?= $faculty_id ?>" class="btn-delete" onclick="return confirm('Xóa lịch thi này?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="8" align="center">Không có lịch thi nào được tìm thấy.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="detailsModal" class="modal">
        <div class="modal-content" style="width: 70%; max-width: 900px;"> <div class="modal-header">
                <h2 id="modal-title">Danh sách sinh viên</h2>
                <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span>
            </div>
            <div class="modal-body" id="participants-content">
                <p style="text-align:center;">Đang tải danh sách...</p>
            </div>
            <div class="modal-footer">
                <button class="btn-submit" onclick="closeModal('detailsModal')" style="background: #6c757d;">Đóng</button>
            </div>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <form action="process/process_exam.php" method="POST">
                <div class="modal-header">
                    <h2>Thêm lịch thi mới</h2>
                    <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Học phần</label>
                            <select name="class_id" required>
                                <?php mysqli_data_seek($classes_res, 0); 
                                    while($c = mysqli_fetch_assoc($classes_res)): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['class_code'] ?> - <?= $c['subject_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày thi</label>
                            <input type="date" name="exam_date" required>
                        </div>
                        <div class="form-group">
                            <label>Giờ thi</label>
                            <input type="time" name="exam_time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phòng thi</label>
                        <input type="text" name="room" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_add" class="btn-submit">Lưu lịch thi</button>
                </div>
            </form>
        </div>
    </div>
    <div id="editModal" class="modal">
        <div class="modal-content">
            <form action="process/process_exam.php" method="POST">
                <div class="modal-header">
                    <h3>Chỉnh sửa lịch thi</h3>
                    <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Học phần</label>
                            <input type="text" id="edit_class_display" readonly 
                                style="background-color: #f4f4f4; cursor: not-allowed; font-weight: bold; color: #555;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày thi</label>
                            <input type="date" name="exam_date" id="edit_date" required>
                        </div>
                        <div class="form-group">
                            <label>Giờ thi</label>
                            <input type="time" name="exam_time" id="edit_time" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phòng thi</label>
                            <input type="text" name="room" id="edit_room" placeholder="Ví dụ: P404, A1.102..." required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_edit" class="btn-submit">Lưu thay đổi</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')" 
                            style="background: #95a5a6; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="../javascript/calendar_admin.js?v=1.0"></script>
</body>
</html>
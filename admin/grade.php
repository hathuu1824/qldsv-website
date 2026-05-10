<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

require '../db_connection.php'; 

// 1. Bộ lọc Khoa
$faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';
$faculties_res = mysqli_query($conn, "SELECT * FROM faculties ORDER BY faculty_name ASC");
$faculties = [];
while($f = mysqli_fetch_assoc($faculties_res)) {
    $faculties[] = $f;
}

if (empty($faculty_id) && !empty($faculties)) {
    $faculty_id = $faculties[0]['id'];
    header("Location: grade.php?faculty=" . $faculty_id);
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';

/// 2. Truy vấn Bảng 1: Lịch thi phúc khảo 
$sql_sessions = "SELECT rs.*, s.subject_name 
                 FROM exam_sessions rs
                 JOIN subjects s ON rs.class_id = s.id
                 JOIN majors m ON s.major_id = m.id
                 WHERE m.faculty_id = '$faculty_id'";

if ($search !== '') {
    $sql_sessions .= " AND (s.subject_name LIKE '%$search%' OR rs.room LIKE '%$search%')";
}
$sql_sessions .= " ORDER BY rs.exam_date ASC";
$res_sessions = mysqli_query($conn, $sql_sessions);

// Kiểm tra lỗi SQL để tránh Fatal Error
if (!$res_sessions) {
    die("Lỗi truy vấn Lịch phúc khảo: " . mysqli_error($conn));
}

// 3. TRUY VẤN BẢNG 2: Yêu cầu phúc khảo từ SV 
$sql_requests = "SELECT rr.*, s.subject_name, p.first_name, p.last_name, a.code as student_code
                 FROM re_exam_requests rr
                 INNER JOIN subjects s ON rr.subject_id = s.id
                 INNER JOIN account a ON rr.account_id = a.id
                 INNER JOIN profile p ON a.id = p.account_id  
                 INNER JOIN majors m ON s.major_id = m.id
                 WHERE m.faculty_id = '$faculty_id'";

if ($search !== '') {
    $sql_requests .= " AND (s.subject_name LIKE '%$search%' OR a.code LIKE '%$search%')";
}
$sql_requests .= " ORDER BY rr.created_at DESC";
$res_requests = mysqli_query($conn, $sql_requests);

if (!$res_requests) {
    die("Lỗi truy vấn Yêu cầu phúc khảo: " . mysqli_error($conn));
}

// 3. TRUY VẤN BẢNG 2: Yêu cầu phúc khảo từ SV (Requests)
$sql_requests = "SELECT rr.*, s.subject_name, p.first_name, p.last_name, a.code as student_code
                 FROM re_exam_requests rr
                 JOIN subjects s ON rr.subject_id = s.id
                 JOIN account a ON rr.account_id = a.id
                 JOIN profile p ON a.id = p.account_id
                 JOIN majors m ON s.major_id = m.id
                 WHERE m.faculty_id = '$faculty_id'
                 ORDER BY rr.created_at DESC";
$res_requests = mysqli_query($conn, $sql_requests);

// Lấy danh sách môn học cho Modal Thêm lịch
$subjects_res = mysqli_query($conn, "SELECT s.id, s.subject_name FROM subjects s 
                                     JOIN majors m ON s.major_id = m.id 
                                     WHERE m.faculty_id = '$faculty_id'");
$total_requests = mysqli_query($conn, $sql_requests); 
$total_count_req = mysqli_num_rows($res_requests);                                     
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Quản lý phúc khảo</title>
</head>
<body>
    <?php include 'header.php' ?>
    <main class="main-container">
        <div class="title-container">
            <h2>Quản lý phúc khảo</h2>
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
                    <h3>Lịch phúc khảo</h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm lịch phúc khảo..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal()">+ Thêm lịch phúc khảo</button>
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
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1; 
                            if(mysqli_num_rows($res_sessions) > 0):
                                while($row = mysqli_fetch_assoc($res_sessions)): 
                            ?>
                            <tr>
                                <td align="center"><?= $stt++ ?></td>
                                <td><strong><?= htmlspecialchars($row['subject_name']) ?></strong></td>
                                <td align="center"><?= date('d/m/Y', strtotime($row['exam_date'])) ?></td>
                                <td align="center"><?= date('H:i', strtotime($row['exam_time'])) ?></td>
                                <td align="center"><?= htmlspecialchars($row['room']) ?></td>
                                <td align="center"><?= date('d/m/Y', strtotime($row['date_start'])) ?></td>
                                <td align="center"><?= date('d/m/Y', strtotime($row['date_end'])) ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="7" align="center">Không tìm thấy ca phúc khảo nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <div class="table-card">
                <div class="table-title">
                    <h3>Yêu cầu phúc khảo: <span class="sv-count"><?= $total_count_req ?></span></h3>
                    <div class="table-btn">
                        <form action="" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Tìm yêu cầu phúc khảo..." 
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-search">Tìm</button>
                        </form>
                    </div>
                </div>
                <div class="sv-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>MSV</th>
                                <th>Họ tên</th>
                                <th>Học phần</th>
                                <th>Điểm</th>
                                <th>Lý do</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            if ($total_count_req > 0):
                                while($req = mysqli_fetch_assoc($res_requests)): ?>
                            <tr>
                                <td align="center"><?= $stt++ ?></td>
                                <td align="center"><?= htmlspecialchars($req['student_code']) ?></td>
                                <td><?= htmlspecialchars($req['last_name'].' '.$req['first_name']) ?></td>
                                <td><?= htmlspecialchars($req['subject_name']) ?></td>
                                
                                <td align="center" style="font-weight: bold; color: #e74c3c;">
                                    <?= isset($req['old_score']) ? $req['old_score'] : 'N/A' ?>
                                </td>
                                
                                <td><small><?= htmlspecialchars($req['reason']) ?></small></td>
                                <td align="center">
                                    <span class="status-badge status-<?= str_replace(' ', '-', $req['status']) ?>">
                                        <?= $req['status'] ?>
                                    </span>
                                </td>
                                <td align="center">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <?php if($req['status'] == 'Chờ duyệt'): ?>
                                            <button class="btn-edit" style="background:#27ae60">Duyệt</button>
                                            <button class="btn-delete">Từ chối</button>
                                        <?php else: ?>
                                            <span style="color:#999; font-size:12px;">Đã xử lý</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="8" align="center">Hiện chưa có yêu cầu phúc khảo nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <form action="process/process_re_exam.php" method="POST">
                <div class="modal-header">
                    <h2>Thiết lập ca phúc khảo</h2>
                    <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Học phần</label>
                            <select name="subject_id" required>
                                <?php mysqli_data_seek($subjects_res, 0); while($s = mysqli_fetch_assoc($subjects_res)): ?>
                                    <option value="<?= $s['id'] ?>"><?= $s['subject_name'] ?></option>
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
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phòng thi</label>
                            <input type="text" name="room" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày bắt đầu</label>
                            <input type="date" name="date_start" required>
                        </div>
                        <div class="form-group">
                            <label>Ngày kết thúc</label>
                            <input type="date" name="date_end" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_add_session" class="btn-submit">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openAddModal() { document.getElementById('addModal').style.display = 'block'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        window.onclick = function(e) { if(e.target.className === 'modal') e.target.style.display = 'none'; }
    </script>
</body>
</html>
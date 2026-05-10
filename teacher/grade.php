<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php?error=no_permission");
    exit();
}

require '../db_connection.php'; 

$faculties_res = mysqli_query($conn, "SELECT * FROM faculties ORDER BY faculty_name ASC");
$faculties = [];
while($f = mysqli_fetch_assoc($faculties_res)) {
    $faculties[] = $f;
}

$faculty_id = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : '';
if (empty($faculty_id) && !empty($faculties)) {
    $faculty_id = $faculties[0]['id'];
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql_requests = "SELECT rr.*, s.subject_name, p.first_name, p.last_name, a.code as student_code
                 FROM re_exam_requests rr
                 INNER JOIN subjects s ON rr.subject_id = s.id
                 INNER JOIN account a ON rr.account_id = a.id
                 INNER JOIN profile p ON a.id = p.account_id  
                 INNER JOIN majors m ON s.major_id = m.id
                 WHERE m.faculty_id = '$faculty_id' 
                 AND rr.status = 'Đã duyệt'"; 

if ($search !== '') {
    $sql_requests .= " AND (s.subject_name LIKE '%$search%' OR a.code LIKE '%$search%')";
}

$sql_requests .= " ORDER BY rr.created_at DESC";
$res_requests = mysqli_query($conn, $sql_requests);

if (!$res_requests) {
    die("Lỗi truy vấn Yêu cầu phúc khảo: " . mysqli_error($conn));
}
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
</body>
</html>
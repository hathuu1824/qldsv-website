<?php
if (!isset($conn)) {
    require_once '../../db_connection.php';
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// 1. Lấy danh sách sinh viên & Sắp xếp ABC theo Tên
$sql_students = "SELECT a.id, a.code, p.first_name, p.last_name,
                        r.score_process, r.score_midterm, r.score_final, r.total_score
                 FROM account a 
                 JOIN profile p ON a.id = p.account_id 
                 JOIN class_members cm ON a.id = cm.student_id 
                 LEFT JOIN class_results r ON (r.class_id = cm.class_id AND r.account_id = a.id)
                 WHERE cm.class_id = ?
                 ORDER BY p.first_name ASC, p.last_name ASC";
                 
$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param("i", $class_id);
$stmt_students->execute();
$students = $stmt_students->get_result();

// 2. Lấy thông tin lớp để hiển thị tiêu đề
$sql_class = "SELECT class_name FROM classes WHERE id = ?";
$stmt_c = $conn->prepare($sql_class);
$stmt_c->bind_param("i", $class_id);
$stmt_c->execute();
$class_info = $stmt_c->get_result()->fetch_assoc();

// 3. Lấy deadline nhập điểm để kiểm soát chế độ sửa/nhập
$sql_deadline = "SELECT deadline FROM classes WHERE id = ?";
$stmt_d = $conn->prepare($sql_deadline);
$stmt_d->bind_param("i", $class_id);
$stmt_d->execute();
$result_d = $stmt_d->get_result();

$class_info = $result_d->fetch_assoc();

$grading_deadline = $class_info['deadline'] ?? null;
$is_expired = false;
$deadline_display = "Chưa thiết lập";

if ($grading_deadline) {
    $deadline_time = strtotime($grading_deadline);
    $current_time = time();
    
    if ($current_time > $deadline_time) {
        $is_expired = true;
    }
    $deadline_display = date('H:i - d/m/Y', $deadline_time);
}
?>

<div class="assign-container">
    <div class="assign-tool" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h4>Thời gian khóa nhập điểm: <?= $deadline_display?></h4>
        <div style="display: flex; flex-direction: row; align-items: center; gap: 10px;">
            <div class="edit-left">
                <div class="edit-controls">
                    <?php if (!$is_expired): ?>
                        <button id="btn-edit-mode" onclick="toggleEditMode(true)" class="btn-reloading">
                            <i class="fas fa-edit"></i> Chỉnh sửa điểm
                        </button>
                    <?php else: ?>
                        <div style="color: #d93025; font-weight: bold; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-lock"></i> Đã hết hạn nhập điểm (Hạn: <?= date('d/m/Y H:i', strtotime($grading_deadline)) ?>)
                        </div>
                    <?php endif; ?>
                </div>
                <div id="edit-actions" style="display: none; gap: 10px;">
                    <button onclick="saveGradesToServer()" class="btn-reloading" style="background: #28a745; color: white; border:none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-save"></i> Lưu dữ liệu
                    </button>
                    <button onclick="toggleEditMode(false)" class="btn-reloading" style="background: #6c757d; color: white; border:none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                        Hủy bỏ
                    </button>
                </div>
            </div>
            <div class="tool-right">
                <button id="btn-import-excel" onclick="importExcel()" class="btn-reloading" style="background: #1d6f42; color: white; border:none; padding: 8px 18px; border-radius: 8px; cursor: pointer; display: none;">
                    <i class="fas fa-file-import"></i> Nhập từ Excel
                </button>
                <button id="btn-export-excel" onclick="exportExcel()" class="btn-reloading" style="background: #d93025; color: white; border:none; padding: 8px 18px; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-file-export"></i> Xuất bảng điểm
                </button>
            </div>
        </div>
    </div>

    <div class="assign-content">
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow-x: auto;">
            <table id="tableToExport" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #eee;">
                        <th style="padding: 15px 12px; text-align: left; width: 100px;">Mã SV</th>
                        <th style="padding: 15px 12px; text-align: left;">Họ và tên</th>
                        <th style="padding: 15px 12px; text-align: center;">Chuyên cần (10%)</th>
                        <th style="padding: 15px 12px; text-align: center;">Giữa kỳ (30%)</th>
                        <th style="padding: 15px 12px; text-align: center;">Cuối kỳ (60%)</th>
                        <th style="padding: 15px 12px; text-align: center;">Tổng kết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($st = $students->fetch_assoc()): 
                        $sid = $st['id'];
                        if (is_null($st['score_process'])) {
                            $sql_total_ss = "SELECT COUNT(*) FROM class_sessions WHERE class_id = ?";
                            $stmt_tss = $conn->prepare($sql_total_ss);
                            $stmt_tss->bind_param("i", $class_id);
                            $stmt_tss->execute();
                            $total_ss = $stmt_tss->get_result()->fetch_row()[0] ?: 1; 

                            $sql_my_ss = "SELECT COUNT(*) FROM attendance WHERE student_id = ? AND status = 'Có mặt' AND session_id IN (SELECT id FROM class_sessions WHERE class_id = ?)";
                            $stmt_mss = $conn->prepare($sql_my_ss);
                            $stmt_mss->bind_param("ii", $sid, $class_id);
                            $stmt_mss->execute();
                            $my_ss = $stmt_mss->get_result()->fetch_row()[0];
                            $display_cc = number_format(($my_ss / $total_ss) * 10, 1);
                        } else {
                            $display_cc = number_format($st['score_process'], 1);
                        }
                    ?>
                    <tr class="row-student" data-student-id="<?= $sid ?>" style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><?= htmlspecialchars($st['code']) ?></td>
                        <td style="padding: 12px;"><strong><?= htmlspecialchars($st['last_name'] . " " . $st['first_name']) ?></strong></td>
                        <td style="padding: 12px; text-align: center;">
                            <input type="number" step="0.1" value="<?= $display_cc ?>" class="score-input" data-type="cc" readonly style="width: 50px; text-align: center; border:none; background:none;">
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <input type="number" step="0.1" value="<?= $st['score_midterm'] ?? '' ?>" class="score-input" data-type="gk" readonly style="width: 50px; text-align: center; border:none; background:none;">
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <input type="number" step="0.1" value="<?= $st['score_final'] ?? '' ?>" class="score-input" data-type="ck" readonly style="width: 50px; text-align: center; border:none; background:none;">
                        </td>
                        <td style="padding: 12px; text-align: center; font-weight: bold; color: #d93025;" class="total-score-val">
                            <?= $st['total_score'] ?? '--' ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
/**
 * Chuyển đổi chế độ Xem/Sửa
 */
function toggleEditMode(isEditing) {
    const inputs = document.querySelectorAll('.score-input');
    const btnEdit = document.getElementById('btn-edit-mode');
    const editActions = document.getElementById('edit-actions');
    const btnImport = document.getElementById('btn-import-excel');
    const btnExport = document.getElementById('btn-export-excel');

    if (isEditing) {
        // Vào chế độ sửa: Hiện khung input, hiện nút Nhập, ẩn nút Xuất
        inputs.forEach(input => {
            input.readOnly = false;
            input.style.border = "1px solid #007bff";
            input.style.background = "#fff";
            input.style.borderRadius = "4px";
        });
        btnEdit.style.display = 'none';
        editActions.style.display = 'flex';
        btnImport.style.display = 'inline-block';
        btnExport.style.display = 'none';
    } else {
        // Thoát chế độ sửa: Ẩn khung input, hiện nút Xuất, ẩn nút Nhập
        inputs.forEach(input => {
            input.readOnly = true;
            input.style.border = "1px solid transparent";
            input.style.background = "transparent";
        });
        btnEdit.style.display = 'inline-block';
        editActions.style.display = 'none';
        btnImport.style.display = 'none';
        btnExport.style.display = 'inline-block';
    }
}

/**
 * Tính điểm tổng kết khi nhập liệu
 */
document.querySelectorAll('.score-input').forEach(input => {
    input.addEventListener('input', function() {
        let row = this.closest('tr');
        let cc = parseFloat(row.querySelector('input[data-type="cc"]').value) || 0;
        let gk = parseFloat(row.querySelector('input[data-type="gk"]').value) || 0;
        let ck = parseFloat(row.querySelector('input[data-type="ck"]').value) || 0;
     
        // Công thức trọng số: 10% - 30% - 60%
        let total = (cc * 0.1) + (gk * 0.3) + (ck * 0.6);
        row.querySelector('.total-score-val').innerText = total.toFixed(1);
    });
});

/**
 * Xuất file Excel (Chỉ khả dụng ở chế độ Xem)
 */
function exportExcel() {
    var table = document.getElementById("tableToExport");
    var wb = XLSX.utils.table_to_book(table, {sheet: "Bảng điểm lớp"});
    XLSX.writeFile(wb, "BangDiem_Lop_<?= $class_id ?>.xlsx");
}

/**
 * Nhập file Excel (Chỉ khả dụng ở chế độ Sửa)
 */
function importExcel() {
    let fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.xlsx, .xls';
    fileInput.onchange = e => { 
        alert("Đã nhận file. Hệ thống sẽ xử lý đọc dữ liệu từ file Excel này.");
    };
    fileInput.click();
}

/**
 * Gửi dữ liệu điểm đã nhập về server (PHP xử lý)
 */
function saveGradesToServer() {
    const rows = document.querySelectorAll('.row-student');
    const gradesData = [];

    rows.forEach(row => {
        const studentId = row.getAttribute('data-student-id');
        const cc = row.querySelector('input[data-type="cc"]').value;
        const gk = row.querySelector('input[data-type="gk"]').value;
        const ck = row.querySelector('input[data-type="ck"]').value;
        const total = row.querySelector('.total-score-val').innerText;

        gradesData.push({
            student_id: studentId,
            cc: cc,
            gk: gk,
            ck: ck,
            total: total
        });
    });

    // Gửi dữ liệu bằng Fetch API
    fetch('process/save_grades.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            class_id: <?= $class_id ?>,
            grades: gradesData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Lưu bảng điểm thành công!");
            toggleEditMode(false);
        } else {
            alert("Có lỗi xảy ra: " + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Không thể kết nối tới máy chủ.");
    });
}
</script>
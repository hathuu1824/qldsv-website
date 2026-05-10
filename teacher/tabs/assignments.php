<?php
if (!isset($conn)) {
    require_once '../../db_connection.php';
}
$filter = $_GET['filter'] ?? 'active';
$current_time = date('Y-m-d H:i:s');

if ($filter === 'closed') {
    $sql_assign = "SELECT * FROM assignments WHERE class_id = ? AND deadline < ? ORDER BY deadline DESC";
} else {
    $sql_assign = "SELECT * FROM assignments WHERE class_id = ? AND deadline >= ? ORDER BY deadline ASC";
}

$stmt_assign = $conn->prepare($sql_assign);
$stmt_assign->bind_param("is", $class_id, $current_time);
$stmt_assign->execute();
$assign_result = $stmt_assign->get_result();
$total_assign = $assign_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="assign-container">
        <div class="assign-tool">
            <div class="filter-group">
                <a href="class_detail.php?id=<?= $class_id ?>&tab=homework&filter=active" 
                class="btn-filter <?= $filter === 'active' ? 'active' : '' ?>">
                Đang diễn ra
                </a>
                <a href="class_detail.php?id=<?= $class_id ?>&tab=homework&filter=closed" 
                class="btn-filter <?= $filter === 'closed' ? 'active' : '' ?>">
                Đã kết thúc
                </a>
            </div>
            <div class="notify-btn">
                <button onclick="window.location.reload();" class="btn-reloading">Tải lại</button>
                <button onclick="openAddAssignModal()" class="btn-reloading">Thêm bài tập, bài kiểm tra</button>
            </div>
        </div>
        <div class="assign-content">
            <?php if ($total_assign > 0): ?>
                <div class="assign-grid">
                    <?php while($item = $assign_result->fetch_assoc()): 
                        $deadline = strtotime($item['deadline']);
                        $is_urgent = ($deadline - time() < 86400 && $filter === 'active'); 
                    ?>
                        <div class="assign-card <?= $is_urgent ? 'urgent' : '' ?>">
                            <div class="card-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="card-info">
                                <h4><?= htmlspecialchars($item['title']) ?></h4>
                                <p class="desc"><?= mb_strimwidth(htmlspecialchars($item['description']), 0, 80, "...") ?></p>
                                <div class="card-footer">
                                    <span class="deadline">
                                        <i class="far fa-clock"></i> 
                                        Hạn chót: <?= date('H:i | d/m/Y', $deadline) ?>
                                    </span>
                                    <a href="view_submissions.php?id=<?= $item['id'] ?>" class="btn-action">
                                        <i class="fas fa-user-check"></i> Chấm bài
                                    </a>
                                </div>
                            </div>
                            <?php if ($is_urgent): ?>
                                <div class="badge-urgent">Sắp hết hạn!</div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-assign-card">
                    <p>Hiện tại không có bài tập nào <?= $filter === 'active' ? 'đang diễn ra' : 'đã kết thúc' ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalAddAssignment" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center;">
        <div style="background: white; padding: 25px; width: 500px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.5); position: relative;">
            <h3 style="margin-top: 0; color: #007bff; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Thêm bài tập / bài kiểm tra</h3>
            <form action="tabs/process/add_assignment.php" method="POST">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                <div style="margin-top: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Tiêu đề bài tập:</label>
                    <input type="text" name="title" required placeholder="Ví dụ: Bài tập về nhà buổi 1" 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                </div>
                <div style="margin-top: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Mô tả chi tiết:</label>
                    <textarea name="description" rows="4" placeholder="Hướng dẫn sinh viên làm bài..." 
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;"></textarea>
                </div>
                <div style="display: flex; gap: 15px; margin-top: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Loại:</label>
                        <select name="type" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="Bài tập">Bài tập về nhà</option>
                            <option value="Bài kiểm tra">Bài kiểm tra</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Hạn chót:</label>
                        <input type="datetime-local" name="deadline" required 
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                </div>
                <div style="margin-top: 25px; text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeAddAssignModal()" 
                            style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Hủy</button>
                    <button type="submit" 
                            style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Tạo bài tập</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddAssignModal() {
            const modal = document.getElementById('modalAddAssignment');
            if (modal) {
                // Đảm bảo nó được đưa ra ngoài body để không bị "nhốt"
                document.body.appendChild(modal);
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Khóa cuộn trang
            }
        }
        function closeAddAssignModal() {
            const modal = document.getElementById('modalAddAssignment');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; 
            }
        }
    </script>
</body>
</html>
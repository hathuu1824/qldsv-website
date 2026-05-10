<?php
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
            <button onclick="window.location.reload();" class="btn-reloading">Tải lại</button>
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
                                    <a href="submit_assignment.php?id=<?= $item['id'] ?>" class="btn-action">
                                        <?= $filter === 'active' ? 'Làm bài' : 'Xem lại' ?>
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
</body>
</html>
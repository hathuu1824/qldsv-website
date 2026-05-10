<?php
require_once 'config/cf_class.php';
$semesters_list = $semesters_list ?? [];
$selected_sem = $_GET['semester'] ?? ($semesters_list[0]['semester'] ?? 1);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/class.css">
    <title>Quản lý lớp học</title>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="main-container">
        <div class="class-title">
            <h2>Quản lý lớp học</h2>
            <div class="filter-group">
                <form method="GET" action="">
                    <select name="semester" id="semester-select" onchange="this.form.submit()">
                        <?php 
                        foreach ($semesters_list as $sem_item): 
                            $sem_code = (int)$sem_item['semester']; 
                            $year_start = floor($sem_code / 10); 
                            $year_end = $year_start + 1;
                            $sem_display = $sem_code % 10;
                        ?>
                            <option value="<?= $sem_code ?>" <?= ($selected_sem == $sem_code) ? 'selected' : '' ?>>
                                Học kỳ <?= $sem_display ?> - Năm học <?= $year_start ?> - <?= $year_end ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        <hr>

        <div class="class-list">
            <h3>Danh sách lớp học</h3>
            <div class="class-grid">
            <?php if (empty($list)): ?>
                <p>Không có lớp học nào trong học kỳ này.</p>
            <?php else: ?>
                <?php foreach ($list as $sub): 
                    $attended = $sub['attended_sessions'] ?? 0; 
                    $total_sessions = $sub['total_sessions'] ?? 10;
                    $percent = ($total_sessions > 0) ? round(($attended / $total_sessions) * 100) : 0;
                    $progress_color = ($percent >= 100) ? '#2ecc71' : '#3498db'; 
                ?>
                    <div class="class-card" onclick="window.location.href='class_detail.php?id=<?= $sub['id'] ?>'">
                        <div class="card-header">
                            <span class="subject-code"><?= htmlspecialchars($sub['subject_code']) ?></span>
                            <span class="credit-label"><?= $sub['credit'] ?> tín chỉ</span>
                        </div>
                        <div class="card-body">
                            <h4><?= htmlspecialchars($sub['subject_name']) ?></h4>
                        </div>
                        <div class="card-stats">
                            <div class="stat-item">
                                <span class="stat-label">Bài tập</span>
                                <span class="stat-value"><?= $sub['assignment_count'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Đã dạy</span>
                                <span class="stat-value"><?= $attended ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Diễn đàn</span>
                                <span class="stat-value"><?= $sub['forum_count'] ?></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="progress-mini-container">
                                <div class="progress-mini-bar">
                                    <div class="progress-mini-fill" style="width: <?= $percent ?>%; background-color: <?= $progress_color ?>;"></div>
                                </div>
                                <span class="progress-mini-text">Đã dạy: <?= $attended ?>/<?= $total_sessions ?> buổi</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="class-content">
            <div class="class-assignment">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Bài tập, bài kiểm tra</h3>
                    <?php if(!empty($assignments) && count($assignments) > 1): ?>
                        <div class="nav-controls" style="user-select: none;">
                            <span onclick="changeAssign(-1)" style="cursor: pointer; padding: 0 10px; font-weight: bold;"> < </span>
                            <span onclick="changeAssign(1)" style="cursor: pointer; padding: 0 10px; font-weight: bold; margin-right: 10px;"> > </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="assignment-list">
                    <?php if(empty($assignments)): ?>
                        <div class="empty-item">Không có bài tập</div>
                    <?php else: ?>
                        <?php foreach($assignments as $index => $assign): ?>
                            <div class="assignment-item" 
                                data-index="<?php echo $index; ?>" 
                                style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>; cursor: pointer;"
                                onclick="window.location.href='assignments.php?id=<?php echo $assign['id']; ?>'">
                                <div class="assign-info">
                                    <strong style="display: block; color: #2c3e50; font-size: 1.1em;"><?php echo htmlspecialchars($assign['title']); ?></strong>
                                    <p style="margin: 8px 0; font-size: 0.9em; color: #7f8c8d;">
                                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($assign['subject_name']); ?>
                                    </p>
                                    <div class="assign-meta" style="font-size: 0.85em; color: #e74c3c; font-weight: bold;">
                                        <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($assign['deadline'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="class-forum">
                <h3>Diễn đàn</h3>
                <div class="forum-list" style="max-height: 220px; overflow-y: auto; padding-right: 5px;">
                    <?php if(empty($forums)): ?>
                        <div class="empty-item">Không có chủ đề thảo luận nào</div>
                    <?php else: ?>
                        <?php foreach($forums as $forum): ?>
                            <div class="forum-item" 
                                style="cursor: pointer; margin-bottom: 10px;" 
                                onclick="window.location.href='forum_detail.php?id=<?php echo $forum['id']; ?>'">
                                <div class="forum-info">
                                    <strong><?php echo htmlspecialchars($forum['title']); ?></strong>
                                    <p>Môn: <?php echo htmlspecialchars($forum['subject_name']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="class-calendar">
                <h3>Lịch giảng dạy sắp tới</h3>
                <div class="calendar-list">
                    <?php if(empty($calendars)): ?>
                        <div class="empty-item">Không có lịch giảng dạy trong tuần này</div>
                    <?php else: ?>
                        <?php foreach($calendars as $cal): ?>
                            <div class="calendar-item" 
                                style="margin-bottom: 10px; cursor: pointer;"
                                onclick="window.location.href='calendar_detail.php?id=<?php echo $cal['id']; ?>'">
                                <div class="calendar-info">
                                    <strong><?php echo htmlspecialchars($cal['subject_name']); ?></strong>
                                    <p style="margin: 8px 0; font-size: 0.9em; color: #7f8c8d;">Mã lớp: <?php echo htmlspecialchars($cal['subject_code']); ?></p>
                                </div>
                                <div class="calendar-meta" style="font-size: 0.85em; color: #e74c3c; font-weight: bold;">
                                    Ngày: <?php echo date('d/m/Y', strtotime($cal['date'])); ?> | <?php echo $cal['period']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>    
</body>
</html>
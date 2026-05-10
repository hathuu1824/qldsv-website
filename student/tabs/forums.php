<?php
$palette = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c', '#34495e'];

$sql_forum = "SELECT f.*, p.first_name, p.last_name, p.avatar, f.account_id 
              FROM forums f 
              LEFT JOIN profile p ON f.account_id = p.account_id 
              WHERE f.class_id = ? 
              ORDER BY f.created_at DESC";

$stmt_forum = $conn->prepare($sql_forum);
$stmt_forum->bind_param("i", $class_id);
$stmt_forum->execute();
$forum_result = $stmt_forum->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="forums-container">
        <div class="add-forums">
            <div class="avatar-circle" style="background-color: <?= $bg_color; ?>;">
                <?php if (!empty($avatar_from_db) && file_exists($avatar_from_db)): ?>
                    <img src="<?= $avatar_from_db; ?>" alt="User Avatar">
                <?php else: ?>
                    <span><?= $first_letter; ?></span>
                <?php endif; ?>
            </div>
            <div class="cmt-card">
                <input type="text" placeholder="Bạn có muốn chia sẻ gì với mọi người trên diễn đàn không?">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                <button type="submit" class="btn-submit">Đăng bài</button>
            </div>
        </div>
        <?php if ($forum_result->num_rows > 0): ?>
            <div class="news-forums">
                <?php while($post = $forum_result->fetch_assoc()): ?>
                    <div class="post-item">
                        <div class="post-header">
                            <div class="avatar-circle small" style="background-color: <?= $post['bg_color']; ?>;">
                                <?php if (!empty($post['avatar']) && file_exists($post['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($post['avatar']); ?>" alt="Poster">
                                <?php else: ?>
                                    <span><?= mb_substr($post['first_name'], 0, 1, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="post-info">
                                <strong><?= htmlspecialchars($post['last_name'] . ' ' . $post['first_name']) ?></strong>
                                <span><?= date('H:i - d/m/Y', strtotime($post['created_at'])) ?></span>
                            </div>
                        </div>
                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
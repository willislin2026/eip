<?php
require_once '../includes/header.php';

$feedback_status = '';
$feedback_error = '';

// 處理意見提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    if (empty($title) || empty($content)) {
        $feedback_error = '請填寫標題與內容';
    } else {
        if ($_SESSION['use_mysql']) {
            try {
                $stmt = $pdo->prepare("INSERT INTO `feedbacks` (username, title, content) VALUES (?, ?, ?)");
                $stmt->execute([$user['username'], $title, $content]);
                $feedback_status = '您的意見已成功送出！我們將儘快處理。';
            } catch(Exception $e) {
                $feedback_error = '資料庫寫入失敗: ' . $e->getMessage();
            }
        } else {
            $_SESSION['mock_feedbacks'][] = [
                'id' => count($_SESSION['mock_feedbacks']) + 1,
                'username' => $user['username'],
                'title' => $title,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $feedback_status = '您的意見已成功送出！(Session)';
        }
    }
}

// 撈取當前使用者的歷史意見反應
$my_feedbacks = [];
if ($_SESSION['use_mysql']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `feedbacks` WHERE username = ? ORDER BY created_at DESC");
        $stmt->execute([$user['username']]);
        $my_feedbacks = $stmt->fetchAll();
    } catch(Exception $e) {}
} else {
    foreach ($_SESSION['mock_feedbacks'] as $fb) {
        if ($fb['username'] === $user['username']) {
            $my_feedbacks[] = $fb;
        }
    }
    // 降冪排序
    usort($my_feedbacks, function($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-comment-dots"></i> 員工意見箱</h1>
    <p class="page-subtitle">歡迎同仁提出各項興革建議或反饋，本意見箱將直遞總經理室與人資部，並對您的身分予以嚴格保密。</p>
</div>

<div class="dashboard-grid">
    
    <!-- 1. 意見反應表單 (Col-6) -->
    <div class="card col-6">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-regular fa-envelope"></i> 提交新意見</h2>
            <i class="fa-solid fa-paper-plane" style="color: var(--primary-light);"></i>
        </div>
        
        <?php if (!empty($feedback_status)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $feedback_status; ?></div>
        <?php endif; ?>
        <?php if (!empty($feedback_error)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $feedback_error; ?></div>
        <?php endif; ?>
        
        <form action="feedback.php" method="POST">
            <input type="hidden" name="action" value="submit_feedback">
            <div class="form-group">
                <label for="title" class="form-label">意見主題</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="請簡述您的意見主題" required>
            </div>
            
            <div class="form-group">
                <label for="content" class="form-label">詳細內容與說明</label>
                <textarea id="content" name="content" class="form-control" rows="6" placeholder="請詳細寫下您所遇到的問題或改善建議..." required></textarea>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">
                <i class="fa-solid fa-paper-plane"></i> 安全送出意見
            </button>
        </form>
    </div>
    
    <!-- 2. 個人歷史意見 (Col-6) -->
    <div class="card col-6">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> 我的歷史意見反應</h2>
            <span class="room-status-badge available">歷史紀錄</span>
        </div>
        
        <div style="max-height: 420px; overflow-y: auto;">
            <?php if (empty($my_feedbacks)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    <i class="fa-solid fa-comments" style="font-size: 3rem; margin-bottom: 1rem; color: var(--border-color);"></i>
                    <p>目前尚無歷史反饋紀錄</p>
                </div>
            <?php else: ?>
                <?php foreach ($my_feedbacks as $fb): ?>
                    <div style="padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 12px; background-color: #f8fafc;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 5px; margin-bottom: 8px;">
                            <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($fb['title']); ?></strong>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($fb['created_at']); ?></span>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-main); white-space: pre-line;"><?php echo htmlspecialchars($fb['content']); ?></p>
                        <div style="margin-top: 8px; text-align: right;">
                            <span class="room-status-badge available" style="font-size: 0.75rem; background-color: #e0f2fe; color: #0369a1;">處理中...</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php
require_once '../includes/footer.php';
?>

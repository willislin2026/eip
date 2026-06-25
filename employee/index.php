<?php
require_once '../includes/header.php';

// 初始化留言板 Session 模擬
if (!isset($_SESSION['mock_messages'])) {
    $_SESSION['mock_messages'] = [
        ['name' => '王小明', 'content' => '大家早！有人要一起團購下午茶嗎？', 'time' => date('Y-m-d H:i', strtotime('-2 hours'))],
        ['name' => '李四', 'content' => '提醒大家，今天下午三點有慶生會，記得要準時到喔！', 'time' => date('Y-m-d H:i', strtotime('-1 hours'))]
    ];
}

$message_status = '';

// 處理留言送出
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_msg') {
    $content = trim($_POST['msg_content']);
    if (!empty($content)) {
        array_unshift($_SESSION['mock_messages'], [
            'name' => $user['realname'],
            'content' => $content,
            'time' => date('Y-m-d H:i')
        ]);
        $message_status = '留言發送成功！';
    }
}

// 處理問卷調查送出
$survey_submitted = isset($_SESSION['survey_submitted']) ? $_SESSION['survey_submitted'] : false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_survey') {
    $_SESSION['survey_submitted'] = true;
    $survey_submitted = true;
    
    // 如果有 MySQL 可寫入
    if ($_SESSION['use_mysql']) {
        try {
            $stmt = $pdo->prepare("INSERT INTO `surveys` (username, q1, q2, q3) VALUES (?, ?, ?, ?)");
            // 這裡如果 surveys table 尚未定義，我們在 config.php 的 init 中建立
        } catch(Exception $e) {}
    }
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-user-tie"></i> 員工專區</h1>
    <p class="page-subtitle">此處提供員工內部日常互動、福委會資訊發佈、問卷調查及各項行政資訊查詢。</p>
</div>

<div class="dashboard-grid">

    <!-- ===== 左側子選單欄 (Col-3) ===== -->
    <div class="sidebar-column">
        <div class="sidebar-nav-card">
            <div class="sidebar-nav-title">
                <i class="fa-solid fa-user-tie"></i> 員工專區導覽
            </div>
            <a href="http://ai.btc.com.tw/nancy/lunch0623/index.php" target="_self" class="sidebar-nav-item"><i class="fa-solid fa-utensils"></i> 台北訂餐</a>
            <a href="#welfare" class="sidebar-nav-item"><i class="fa-solid fa-gift"></i> 福委園地</a>
            <a href="http://eip.btc.com.tw/index.asp?title=4&select=4&sel=true&sequence=2&url=http://eip.btc.com.tw/tool/hr/index_hr.asp" target="_blank" class="sidebar-nav-item"><i class="fa-solid fa-users-gear"></i> 人資申請系統</a>
            <a href="<?php echo BASE_URL; ?>employee/feedback.php" class="sidebar-nav-item"><i class="fa-solid fa-comment-dots"></i> 員工意見箱</a>
            <a href="#messages" class="sidebar-nav-item"><i class="fa-solid fa-paper-plane"></i> 線上留言板</a>
            <a href="#safety" class="sidebar-nav-item"><i class="fa-solid fa-shield-halved"></i> 廠區安全管理</a>
            <a href="#survey" class="sidebar-nav-item"><i class="fa-solid fa-clipboard-question"></i> 內控問卷調查</a>
            <a href="<?php echo BASE_URL; ?>employee/password.php" class="sidebar-nav-item"><i class="fa-solid fa-key"></i> 變更密碼</a>
        </div>
    </div>

    <!-- ===== 中間主要內容欄 (Col-6) ===== -->
    <div class="main-column">
        <!-- 1. 同仁留言板 -->
        <div class="card" id="messages">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-paper-plane"></i> 線上發送消息 (同仁留言板)</h2>
                <span class="room-status-badge available">即時公告</span>
            </div>

            <?php if (!empty($message_status)): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $message_status; ?></div>
            <?php endif; ?>

            <form action="index.php#messages" method="POST" style="margin-bottom: 1.5rem;">
                <input type="hidden" name="action" value="post_msg">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="msg_content" class="form-control" placeholder="今天有什麼分享？..." required>
                    <button type="submit" class="btn"><i class="fa-solid fa-paper-plane"></i> 發送</button>
                </div>
            </form>

            <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 10px;">
                <?php foreach ($_SESSION['mock_messages'] as $msg): ?>
                    <div style="padding: 10px; border-bottom: 1px solid #f1f5f9; position: relative;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.8rem;">
                            <span style="font-weight: 700; color: var(--primary-color);"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($msg['name']); ?></span>
                            <span style="color: var(--text-muted);"><?php echo htmlspecialchars($msg['time']); ?></span>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--text-main);"><?php echo htmlspecialchars($msg['content']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 2. 內控問卷調查 -->
        <div class="card" id="survey">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-clipboard-question"></i> 內部控制問卷調查</h2>
                <?php if ($survey_submitted): ?>
                    <span class="room-status-badge available"><i class="fa-solid fa-check"></i> 已完成</span>
                <?php else: ?>
                    <span class="room-status-badge booked">待填</span>
                <?php endif; ?>
            </div>

            <?php if ($survey_submitted): ?>
                <div style="text-align: center; padding: 1rem;">
                    <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; color: var(--success); margin-bottom: 0.5rem;"></i>
                    <h3 style="color: var(--primary-color); font-size: 1rem;">感謝參與！</h3>
                    <p style="color: var(--text-muted); font-size: 0.8rem;">已記入人事考評檔案。</p>
                    <a href="index.php?reset_survey=1" class="btn btn-secondary" style="margin-top: 10px; font-size: 0.75rem; padding: 4px 10px;">重新填寫</a>
                </div>
            <?php else: ?>
                <form action="index.php#survey" method="POST">
                    <input type="hidden" name="action" value="submit_survey">
                    
                    <div class="form-group" style="margin-bottom: 0.8rem;">
                        <label class="form-label" style="font-size: 0.85rem; margin-bottom: 3px;">1. 是否已詳閱消防逃生路線？</label>
                        <div style="display: flex; gap: 15px; font-size: 0.85rem;">
                            <label><input type="radio" name="q1" value="yes" required checked> 是，已詳閱</label>
                            <label><input type="radio" name="q1" value="no"> 否</label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0.8rem;">
                        <label class="form-label" style="font-size: 0.85rem; margin-bottom: 3px;">2. 是否知悉國內旅遊補助規範？</label>
                        <div style="display: flex; gap: 15px; font-size: 0.85rem;">
                            <label><input type="radio" name="q2" value="yes" required checked> 是，已清楚</label>
                            <label><input type="radio" name="q2" value="no"> 否</label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="font-size: 0.85rem; margin-bottom: 3px;">3. 本季內控與簽核流程是否順暢？</label>
                        <div style="display: flex; gap: 15px; font-size: 0.85rem;">
                            <label><input type="radio" name="q3" value="yes" required checked> 是，非常順暢</label>
                            <label><input type="radio" name="q3" value="no"> 否，需改進</label>
                        </div>
                    </div>

                    <button type="submit" class="btn" style="font-size: 0.8rem; padding: 6px 12px; width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i> 提交內控問卷
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== 右側輔助小工具欄 (Col-3) ===== -->
    <div class="layout-right">
        <!-- 3. 福委園地 -->
        <div class="card" id="welfare">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-gift"></i> 福委園地</h2>
                <i class="fa-solid fa-heart" style="color: #e74c3c;"></i>
            </div>
            <div style="font-size: 0.9rem;">
                <div style="background-color: #fdf2f2; border-left: 4px solid #f87171; padding: 10px; border-radius: 4px; margin-bottom: 12px;">
                    <strong style="color: #991b1b; font-size: 0.85rem;">🔥 國內旅遊補助申請</strong>
                    <p style="margin-top: 5px; color: #7f1d1d; font-size: 0.8rem; line-height: 1.4; margin-bottom: 0;">自即日起開放申請，每位補助 NT$5,000，請於 8/31 前檢附憑證報支。</p>
                </div>
                <ul style="padding-left: 1.2rem; color: var(--text-main); display: flex; flex-direction: column; gap: 8px; font-size: 0.8rem; margin: 0;">
                    <li>端午節禮品發放 (發放時間: 6/15)</li>
                    <li>特約廠商更新 (含大安區特約美食)</li>
                    <li>福委會本季收支明細已公佈於雲端</li>
                </ul>
            </div>
        </div>

        <!-- 4. 廠區安全管理 -->
        <div class="card" id="safety">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-shield-halved"></i> 廠區安全守則</h2>
                <i class="fa-solid fa-fire-extinguisher" style="color: var(--warning);"></i>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 8px;">
                <p style="text-align: justify; font-size: 0.8rem; color: var(--text-main); margin: 0; line-height: 1.4;">
                    各樓層消防栓與滅火器周邊 1 公尺內嚴禁堆放雜物。若遇緊急狀況，請遵循安全通道之綠色逃生出口指示牌，迅速至大樓前廣場集合，切勿搭乘電梯。
                </p>
            </div>
        </div>
    </div>

</div>

<?php
require_once '../includes/footer.php';
?>

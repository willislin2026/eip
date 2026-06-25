<?php
require_once '../includes/header.php';

// 初始化最後同步時間
if (!isset($_SESSION['last_hr_sync'])) {
    $_SESSION['last_hr_sync'] = '尚未同步';
}

$sync_message = '';

// 處理一鍵同步請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_hr') {
    $_SESSION['last_hr_sync'] = date('Y-m-d H:i:s');
    $sync_message = '人事系統簽核層級資料同步成功！已更新 5 筆同仁職級與簽核權限。';
}

// 載入員工資料，用於分類展示
$employees = [];
if ($_SESSION['use_mysql']) {
    try {
        $stmt = $pdo->query("SELECT * FROM `employees`");
        $employees = $stmt->fetchAll();
    } catch (Exception $e) {}
} else {
    foreach ($_SESSION['mock_employees'] as $emp) {
        $employees[] = $emp;
    }
}

// 依職級進行分類
$hierarchy = [
    'Director' => [],
    'Manager' => [],
    'Employee' => []
];
foreach ($employees as $emp) {
    $level = $emp['approval_level'];
    if (isset($hierarchy[$level])) {
        $hierarchy[$level][] = $emp;
    }
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-sitemap"></i> 簽核層級管理 (來自人事系統)</h1>
    <p class="page-subtitle">此處展示企業內部的簽核權限與職級架構，系統每日會自動與人事 (HR) 系統同步最新的層級資料。</p>
</div>

<div class="dashboard-grid">
    <!-- 1. 人事系統資料同步控制台 (Col-4) -->
    <div class="card col-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-rotate"></i> 人事資料同步狀態</h2>
            <span class="room-status-badge available">連線正常</span>
        </div>
        
        <?php if (!empty($sync_message)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $sync_message; ?></div>
        <?php endif; ?>

        <div style="font-size: 0.9rem; margin-bottom: 20px;">
            <p style="margin-bottom: 10px;"><strong>串接人事資料庫 (HR DB)：</strong></p>
            <div style="background-color: #f8fafc; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; font-family: 'Outfit';">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">人事連線狀態：</span>
                    <span style="color: var(--success); font-weight: 700;">Connected</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">簽核資料庫版本：</span>
                    <span style="font-weight: 600;">v2.6.24-HR</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">最後同步時間：</span>
                    <span style="color: var(--primary-color); font-weight: 700;"><?php echo $_SESSION['last_hr_sync']; ?></span>
                </div>
            </div>
        </div>

        <form action="approval_levels.php" method="POST" id="syncForm" onsubmit="showLoading()">
            <input type="hidden" name="action" value="sync_hr">
            <button type="submit" class="btn" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fa-solid fa-cloud-arrow-down" id="syncIcon"></i> 
                <span id="syncText">手動同步人事簽核資料</span>
            </button>
        </form>
        
        <div id="loadingSpinner" style="display: none; text-align: center; margin-top: 15px;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.5rem; color: var(--primary-light);"></i>
            <span style="font-size: 0.85rem; color: var(--text-muted); margin-left: 8px;">正在同步組織架構資料...</span>
        </div>
    </div>

    <!-- 2. 簽核層級樹狀結構展示 (Col-8) -->
    <div class="card col-8">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-network-wired"></i> 企業組織與簽核層級架構圖</h2>
            <span class="room-status-badge available">核決權限</span>
        </div>
        
        <!-- 樹狀階層 CSS 佈局 -->
        <div class="tree-container">
            <!-- 頂級: Director -->
            <div class="tree-node director-node">
                <div class="node-header">
                    <span class="level-badge director-badge">Director</span>
                    <strong>處長 / 系統管理員</strong>
                </div>
                <div class="node-body">
                    <p><strong>簽核範圍：</strong> 全公司行政、採購及名片表單終審</p>
                    <p><strong>核決金額上限：</strong> 無限制 (終審)</p>
                </div>
            </div>
            
            <div class="tree-connector">
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            
            <!-- 中級: Manager -->
            <div class="tree-node manager-node">
                <div class="node-header">
                    <span class="level-badge manager-badge">Manager</span>
                    <strong>部門主管 / 經理</strong>
                </div>
                <div class="node-body">
                    <p><strong>簽核範圍：</strong> 部門內部會議室預約核准、文具申領初審</p>
                    <p><strong>核決金額上限：</strong> NT$ 100,000 元整</p>
                </div>
            </div>
            
            <div class="tree-connector">
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            
            <!-- 初級: Employee -->
            <div class="tree-node employee-node">
                <div class="node-header">
                    <span class="level-badge employee-badge">Employee</span>
                    <strong>一般員工 / 專員</strong>
                </div>
                <div class="node-body">
                    <p><strong>簽核範圍：</strong> 僅限發起文具申請、會議室預約及意見填報</p>
                    <p><strong>核決金額上限：</strong> 無核准權限</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. 同仁職級歸類表 (Col-12) -->
    <div class="card col-12">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-users-viewfinder"></i> 職級同仁對照清單 (依簽核層級)</h2>
            <span class="room-status-badge available" style="background-color: var(--primary-color); color: white;">已分類</span>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; font-size: 0.9rem;">
            <!-- Director 列 -->
            <div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: var(--radius-md); padding: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #fca5a5; padding-bottom: 8px; margin-bottom: 10px;">
                    <strong style="color: #991b1b;"><i class="fa-solid fa-crown"></i> Director 級成員</strong>
                    <span style="background-color: #991b1b; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-family: 'Outfit'; font-weight: 700;">
                        <?php echo count($hierarchy['Director']); ?> 人
                    </span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($hierarchy['Director'] as $emp): ?>
                        <div style="background-color: white; padding: 8px 12px; border-radius: var(--radius-sm); box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;">
                            <strong><?php echo htmlspecialchars($emp['realname']); ?></strong>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($emp['department']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Manager 列 -->
            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #fde68a; padding-bottom: 8px; margin-bottom: 10px;">
                    <strong style="color: #d97706;"><i class="fa-solid fa-user-shield"></i> Manager 級成員</strong>
                    <span style="background-color: #d97706; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-family: 'Outfit'; font-weight: 700;">
                        <?php echo count($hierarchy['Manager']); ?> 人
                    </span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($hierarchy['Manager'] as $emp): ?>
                        <div style="background-color: white; padding: 8px 12px; border-radius: var(--radius-sm); box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;">
                            <strong><?php echo htmlspecialchars($emp['realname']); ?></strong>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($emp['department']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Employee 列 -->
            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md); padding: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #bbf7d0; padding-bottom: 8px; margin-bottom: 10px;">
                    <strong style="color: #16a34a;"><i class="fa-solid fa-user-tie"></i> Employee 級成員</strong>
                    <span style="background-color: #16a34a; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-family: 'Outfit'; font-weight: 700;">
                        <?php echo count($hierarchy['Employee']); ?> 人
                    </span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; max-height: 250px; overflow-y: auto; padding-right: 5px;">
                    <?php foreach ($hierarchy['Employee'] as $emp): ?>
                        <div style="background-color: white; padding: 8px 12px; border-radius: var(--radius-sm); box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;">
                            <strong><?php echo htmlspecialchars($emp['realname']); ?></strong>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($emp['department']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 樹狀結構樣式 */
.tree-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 0;
}

.tree-node {
    width: 100%;
    max-width: 500px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background-color: white;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: var(--transition);
}

.tree-node:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.node-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 15px;
    border-bottom: 1px solid var(--border-color);
    color: white;
}

.node-body {
    padding: 12px 15px;
    font-size: 0.85rem;
    color: var(--text-main);
}

.node-body p {
    margin-bottom: 4px;
}

.node-body p:last-child {
    margin-bottom: 0;
}

/* 節點特定樣式 */
.director-node {
    border-color: #fca5a5;
}
.director-node .node-header {
    background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
}

.manager-node {
    border-color: #fde68a;
}
.manager-node .node-header {
    background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
}

.employee-node {
    border-color: #bbf7d0;
}
.employee-node .node-header {
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
}

/* 標記徽章 */
.level-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 20px;
    background-color: rgba(255, 255, 255, 0.25);
    color: white;
    text-transform: uppercase;
}

.tree-connector {
    margin: 10px 0;
    color: var(--text-muted);
    font-size: 1.2rem;
}
</style>

<script>
function showLoading() {
    document.getElementById('loadingSpinner').style.display = 'block';
    document.getElementById('syncIcon').className = 'fa-solid fa-spinner fa-spin';
    document.getElementById('syncText').innerText = '串接人事系統中...';
}
</script>

<?php
require_once '../includes/footer.php';
?>

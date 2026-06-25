<?php
require_once '../includes/header.php';

// 初始化今日點餐匯總與明細
$today = date('Y-m-d');
$orders_detail = [];
$meal_summary = [];

if ($_SESSION['use_mysql']) {
    try {
        // 讀取所有訂餐紀錄，並關聯員工資料
        $stmt = $pdo->prepare("
            SELECT o.id, o.username, o.meal_option, o.order_date, o.status, e.realname, e.department, e.ext_no 
            FROM `orders` o
            LEFT JOIN `employees` e ON o.username = e.username
            ORDER BY o.order_date DESC, o.id DESC
        ");
        $stmt->execute();
        $all_orders = $stmt->fetchAll();
    } catch (Exception $e) {
        $all_orders = [];
    }
} else {
    // Session 模擬資料庫
    $all_orders = [];
    foreach ($_SESSION['mock_orders'] as $mo) {
        $username = $mo['username'];
        $emp = isset($_SESSION['mock_employees'][$username]) ? $_SESSION['mock_employees'][$username] : [
            'realname' => '未知員工', 'department' => '未知', 'ext_no' => '-'
        ];
        $all_orders[] = [
            'id' => $mo['id'],
            'username' => $username,
            'meal_option' => $mo['meal_option'],
            'order_date' => $mo['order_date'],
            'status' => $mo['status'],
            'realname' => $emp['realname'],
            'department' => $emp['department'],
            'ext_no' => $emp['ext_no']
        ];
    }
    // 排序：日期降序，ID降序
    usort($all_orders, function($a, $b) {
        if ($a['order_date'] == $b['order_date']) {
            return $b['id'] - $a['id'];
        }
        return strcmp($b['order_date'], $a['order_date']);
    });
}

// 篩選今日訂餐並進行統計
foreach ($all_orders as $ord) {
    if ($ord['order_date'] === $today) {
        $orders_detail[] = $ord;
        
        $meal = $ord['meal_option'];
        if (!isset($meal_summary[$meal])) {
            $meal_summary[$meal] = 0;
        }
        $meal_summary[$meal]++;
    }
}

// 處理模擬菜單更新
$menu_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_menu_item') {
    $menu_item = trim($_POST['menu_item']);
    if (!empty($menu_item)) {
        $menu_status = "店家菜單項目 「" . htmlspecialchars($menu_item) . "」 新增成功！";
    }
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-utensils"></i> 總務部 - 訂餐管理系統</h1>
    <p class="page-subtitle">此處提供總務行政同仁檢視每日訂餐統計、管理店家菜單，並匯出便當店家的點單報表。</p>
</div>

<div class="dashboard-grid">
    <!-- 1. 今日點餐數量彙總 (Col-4) -->
    <div class="card col-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-chart-pie"></i> 今日便當數量彙總</h2>
            <span class="room-status-badge available"><?php echo $today; ?></span>
        </div>
        
        <?php if (empty($meal_summary)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-receipt" style="font-size: 3rem; margin-bottom: 12px; color: #cbd5e1;"></i>
                <p>今日尚無任何同仁提交訂餐資料。</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($meal_summary as $meal => $count): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background-color: #f8fafc;">
                        <span style="font-weight: 700; color: var(--primary-color);"><i class="fa-solid fa-bowl-rice"></i> <?php echo htmlspecialchars($meal); ?></span>
                        <span style="background-color: var(--primary-color); color: white; padding: 3px 10px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; font-family: 'Outfit';">
                            <?php echo $count; ?> 份
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 15px; border-top: 2px solid var(--border-color); padding-top: 15px; display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 1.1rem; color: var(--primary-dark);">
                    <span>總計數量</span>
                    <span><?php echo array_sum($meal_summary); ?> 份</span>
                </div>
                
                <button class="btn" style="margin-top: 10px; width: 100%;" onclick="alert('已模擬產生便當店家報表，準備傳送至：合家歡便當店 (02-2365-XXXX)');"><i class="fa-solid fa-paper-plane"></i> 傳送今日訂單至便當店</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. 店家菜單維護 & 模擬操作 (Col-4) -->
    <div class="card col-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-list-check"></i> 今日便當菜單管理</h2>
            <span class="room-status-badge available">設定</span>
        </div>
        
        <?php if (!empty($menu_status)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $menu_status; ?></div>
        <?php endif; ?>

        <form action="order_manage.php" method="POST" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="add_menu_item">
            <div class="form-group">
                <label class="form-label">新增今日便當選項</label>
                <input type="text" name="menu_item" class="form-control" placeholder="例如：香酥雞腿飯 ($100)" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;"><i class="fa-solid fa-plus"></i> 新增至今日菜單</button>
        </form>

        <div style="border-top: 1px solid var(--border-color); padding-top: 15px;">
            <strong style="color: var(--primary-color); font-size: 0.9rem; display: block; margin-bottom: 10px;">現有合作店家名單：</strong>
            <ul style="padding-left: 20px; font-size: 0.85rem; color: var(--text-main); display: flex; flex-direction: column; gap: 6px;">
                <li><strong>合家歡便當 (今日)</strong> - 台北市中正區羅斯福路</li>
                <li><strong>老張牛肉麵 (週二/四)</strong> - 台北市大安區新生南路</li>
                <li><strong>健康輕食沙拉 (週五)</strong> - 台北市中正區紹興街</li>
            </ul>
        </div>
    </div>

    <!-- 3. 便當統計報表匯出快捷 (Col-4) -->
    <div class="card col-4" style="background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-file-excel"></i> 訂餐報表匯出</h2>
            <i class="fa-solid fa-download" style="color: var(--primary-light);"></i>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px; text-align: justify;">
            總務同仁可以匯出每日、每週或每月的同仁點餐報表，以便與便當店家進行月底對帳與統一撥款。
        </p>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <button class="btn btn-secondary" onclick="alert('已模擬匯出今日點餐 Excel 報表');" style="font-size: 0.85rem; text-align: left; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fa-solid fa-file-excel" style="color: #1e7145; margin-right: 6px;"></i> 匯出今日報表 (.xlsx)</span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            <button class="btn btn-secondary" onclick="alert('已模擬匯出本週點餐 Excel 報表');" style="font-size: 0.85rem; text-align: left; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fa-solid fa-file-excel" style="color: #1e7145; margin-right: 6px;"></i> 匯出本週報表 (.xlsx)</span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            <button class="btn btn-secondary" onclick="alert('已模擬匯出上月對帳 Excel 報表');" style="font-size: 0.85rem; text-align: left; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fa-solid fa-calendar-days" style="color: var(--primary-light); margin-right: 6px;"></i> 匯出上月對帳報表 (.xlsx)</span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- 4. 同仁點餐明細表 (Col-12) -->
    <div class="card col-12" id="order-details">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-list"></i> 同仁訂餐歷史紀錄 (今日與歷史資料)</h2>
            <span class="room-status-badge available" style="background-color: var(--primary-color); color: white;">
                共計 <?php echo count($all_orders); ?> 筆紀錄
            </span>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); background-color: #f8fafc;">
                        <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">訂餐日期</th>
                        <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">帳號</th>
                        <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">姓名</th>
                        <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">部門</th>
                        <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">分機</th>
                        <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">餐點選項</th>
                        <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">狀態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_orders)): ?>
                        <tr>
                            <td colspan="7" style="padding: 20px; text-align: center; color: var(--text-muted);">目前系統中無任何訂餐紀錄。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_orders as $ord): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0; background-color: <?php echo ($ord['order_date'] === $today) ? '#f0f7fc' : 'transparent'; ?>">
                                <td style="padding: 12px; font-family: 'Outfit';">
                                    <?php echo htmlspecialchars($ord['order_date']); ?>
                                    <?php if ($ord['order_date'] === $today): ?>
                                        <span class="room-status-badge available" style="font-size: 0.7rem; padding: 1px 4px; margin-left: 5px;">今日</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; font-family: 'Outfit';"><?php echo htmlspecialchars($ord['username']); ?></td>
                                <td style="padding: 12px; font-weight: bold;"><?php echo htmlspecialchars($ord['realname']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($ord['department']); ?></td>
                                <td style="padding: 12px; font-family: 'Outfit';"><?php echo htmlspecialchars($ord['ext_no']); ?></td>
                                <td style="padding: 12px; font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($ord['meal_option']); ?></td>
                                <td style="padding: 12px;">
                                    <span class="room-status-badge available"><?php echo htmlspecialchars($ord['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>

<?php
require_once '../includes/header.php';

$order_status = '';
$order_error = '';

// 特餐選項
$meals = [
    '玫瑰油雞飯 (NT$100)' => '玫瑰油雞飯 (NT$100)',
    '招牌炸排骨飯 (NT$95)' => '招牌炸排骨飯 (NT$95)',
    '黃金鱈魚排飯 (NT$110)' => '黃金鱈魚排飯 (NT$110)',
    '日式蒲燒鰻飯 (NT$150)' => '日式蒲燒鰻飯 (NT$150)',
    '鮮菇時蔬素食餐 (NT$90)' => '鮮菇時蔬素食餐 (NT$90)'
];

// 處理點餐送出
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'order_meal') {
    $selected_meal = trim($_POST['meal_option']);
    $order_date = date('Y-m-d');
    
    if (empty($selected_meal)) {
        $order_error = '請選擇您的餐點';
    } else {
        if ($_SESSION['use_mysql']) {
            try {
                // 先檢查今日是否已點過餐
                $stmt = $pdo->prepare("SELECT id FROM `orders` WHERE username = ? AND order_date = ? AND status = '已點餐'");
                $stmt->execute([$user['username'], $order_date]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // 更新今日點餐
                    $update_stmt = $pdo->prepare("UPDATE `orders` SET meal_option = ? WHERE id = ?");
                    $update_stmt->execute([$selected_meal, $existing['id']]);
                    $order_status = '今日點餐修改成功！';
                } else {
                    // 新增今日點餐
                    $insert_stmt = $pdo->prepare("INSERT INTO `orders` (username, order_date, meal_option) VALUES (?, ?, ?)");
                    $insert_stmt->execute([$user['username'], $order_date, $selected_meal]);
                    $order_status = '訂餐成功！已記入資料庫。';
                }
            } catch (Exception $e) {
                $order_error = '資料庫寫入失敗: ' . $e->getMessage();
            }
        } else {
            // 模擬 Session 儲存
            $found = false;
            foreach ($_SESSION['mock_orders'] as &$ord) {
                if ($ord['username'] === $user['username'] && $ord['order_date'] === $order_date && $ord['status'] === '已點餐') {
                    $ord['meal_option'] = $selected_meal;
                    $found = true;
                    $order_status = '今日點餐修改成功！(Session)';
                    break;
                }
            }
            if (!$found) {
                $_SESSION['mock_orders'][] = [
                    'id' => count($_SESSION['mock_orders']) + 1,
                    'username' => $user['username'],
                    'realname' => $user['realname'],
                    'order_date' => $order_date,
                    'meal_option' => $selected_meal,
                    'status' => '已點餐'
                ];
                $order_status = '訂餐成功！(Session)';
            }
        }
    }
}

// 取得今日點餐清單
$today_orders = [];
$today_date = date('Y-m-d');
if ($_SESSION['use_mysql']) {
    try {
        $stmt = $pdo->prepare("SELECT o.*, e.realname FROM `orders` o JOIN `employees` e ON o.username = e.username WHERE o.order_date = ? AND o.status = '已點餐'");
        $stmt->execute([$today_date]);
        $today_orders = $stmt->fetchAll();
    } catch(Exception $e) {}
} else {
    // 從 mock 中撈取今日點餐
    foreach ($_SESSION['mock_orders'] as $ord) {
        if ($ord['order_date'] === $today_date && $ord['status'] === '已點餐') {
            $today_orders[] = $ord;
        }
    }
}

// 統計今日各餐點數量
$meal_counts = [];
foreach ($today_orders as $o) {
    $meal = $o['meal_option'];
    if (!isset($meal_counts[$meal])) {
        $meal_counts[$meal] = 0;
    }
    $meal_counts[$meal]++;
}

// 取得當前使用者今日已點餐點
$my_today_meal = '';
foreach ($today_orders as $o) {
    if ($o['username'] === $user['username']) {
        $my_today_meal = $o['meal_option'];
        break;
    }
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-utensils"></i> 台北訂餐系統</h1>
    <p class="page-subtitle">每日點餐時間截止至早上 10:00，逾時將關閉今日點餐功能。點餐資料將自動報送總務部。</p>
</div>

<div class="dashboard-grid">
    
    <!-- 1. 填寫點餐表單 (Col-6) -->
    <div class="card col-6">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-pen-to-square"></i> 線上點餐</h2>
            <span class="room-status-badge available">開放中</span>
        </div>
        
        <?php if (!empty($order_status)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $order_status; ?></div>
        <?php endif; ?>
        <?php if (!empty($order_error)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $order_error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($my_today_meal)): ?>
            <div style="background-color: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 15px; font-size: 0.9rem;">
                <i class="fa-solid fa-check-double"></i> 您今日已完成點餐。已點餐點為：<strong><?php echo htmlspecialchars($my_today_meal); ?></strong>。如欲更改餐點，可於下方重新選擇並送出。
            </div>
        <?php endif; ?>
        
        <form action="order.php" method="POST">
            <input type="hidden" name="action" value="order_meal">
            <div class="form-group">
                <label class="form-label">選擇今日特餐</label>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($meals as $key => $val): ?>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition);" class="meal-option-row">
                            <input type="radio" name="meal_option" value="<?php echo htmlspecialchars($key); ?>" <?php echo ($my_today_meal === $key) ? 'checked' : ''; ?> required>
                            <span><?php echo htmlspecialchars($val); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 10px; padding: 0.8rem;">
                <i class="fa-solid fa-check"></i> 確定送出訂餐
            </button>
        </form>
    </div>
    
    <!-- 2. 今日訂餐統計與同仁清單 (Col-6) -->
    <div class="card col-6">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-chart-pie"></i> 今日訂餐統計 (<?php echo count($today_orders); ?> 份)</h2>
            <i class="fa-solid fa-list-check" style="color: var(--primary-light);"></i>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1rem; color: var(--primary-color); margin-bottom: 10px;">便當數量統計</h3>
            <?php if (empty($meal_counts)): ?>
                <p style="font-size: 0.9rem; color: var(--text-muted); text-align: center; padding: 10px; border: 1px dashed var(--border-color); border-radius: 4px;">今日尚無同仁點餐</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($meal_counts as $meal => $count): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; padding: 6px 12px; background-color: #f8fafc; border-radius: 4px; border-left: 4px solid var(--primary-light);">
                            <span><?php echo htmlspecialchars($meal); ?></span>
                            <span style="font-weight: 700; color: var(--primary-color);"><?php echo $count; ?> 份</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h3 style="font-size: 1rem; color: var(--primary-color); margin-bottom: 10px;">同仁點餐明細</h3>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 8px;">
                <?php if (empty($today_orders)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; padding: 10px;">無明細資料</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); text-align: left; background-color: #f1f5f9;">
                                <th style="padding: 6px;">同仁</th>
                                <th style="padding: 6px;">已點餐點</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_orders as $ord): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 6px; font-weight: bold;"><?php echo htmlspecialchars(isset($ord['realname']) ? $ord['realname'] : $ord['username']); ?></td>
                                    <td style="padding: 6px; color: var(--text-main);"><?php echo htmlspecialchars($ord['meal_option']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 點餐選項 UI 動態點擊效果
const rows = document.querySelectorAll('.meal-option-row');
rows.forEach(row => {
    row.addEventListener('click', function() {
        rows.forEach(r => r.style.borderColor = 'var(--border-color)');
        this.style.borderColor = 'var(--primary-light)';
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>

<?php
require_once '../includes/header.php';

// 初始化申領 Session 模擬
if (!isset($_SESSION['mock_stationery'])) {
    $_SESSION['mock_stationery'] = [
        ['type' => 'stationery', 'item' => '原子筆(黑) * 5, 螢光筆 * 2', 'qty' => 1, 'date' => date('Y-m-d', strtotime('-10 days')), 'status' => '已領取'],
        ['type' => 'card', 'item' => '商務名片印製 (3 盒)', 'qty' => 3, 'date' => date('Y-m-d', strtotime('-5 days')), 'status' => '已送印']
    ];
}

$status_msg = '';

// 處理文具申請
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_stationery') {
    $items = $_POST['items'] ?? [];
    $qty = (int)$_POST['qty'];
    $memo = trim($_POST['memo']);
    
    if (empty($items)) {
        $status_msg = 'error: 請至少選擇一項文具';
    } else {
        $item_str = implode(', ', $items) . ($memo ? " (備註: $memo)" : '');
        $_SESSION['mock_stationery'][] = [
            'type' => 'stationery',
            'item' => $item_str,
            'qty' => $qty,
            'date' => date('Y-m-d'),
            'status' => '待核准'
        ];
        $status_msg = 'success: 文具申請成功！已送交總務部審核。';
    }
}

// 處理名片申請
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_card') {
    $title_en = trim($_POST['title_en']);
    $name_en = trim($_POST['name_en']);
    $boxes = (int)$_POST['boxes'];
    
    if (empty($title_en) || empty($name_en)) {
        $status_msg = 'error: 請輸入英文姓名與職稱';
    } else {
        $item_str = "名片印製: $name_en ($title_en)";
        $_SESSION['mock_stationery'][] = [
            'type' => 'card',
            'item' => $item_str,
            'qty' => $boxes,
            'date' => date('Y-m-d'),
            'status' => '待核准'
        ];
        $status_msg = 'success: 名片印製申請成功！已傳送印製流程。';
    }
}

// 取得當前 Tab 參數
$active_tab = $_GET['tab'] ?? 'stationery';
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-pen-fancy"></i> 總務專區 - 行政資源申請</h1>
    <p class="page-subtitle">同仁可在此線上辦理辦公室文具領用以及商務個人名片印製申請。</p>
</div>

<!-- Tab 切換導覽 -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 0px;">
    <a href="stationery.php?tab=stationery" class="btn <?php echo $active_tab === 'stationery' ? '' : 'btn-secondary'; ?>" style="border-radius: var(--radius-sm) var(--radius-sm) 0 0; padding: 10px 20px; margin-bottom: -2px;">
        <i class="fa-solid fa-pencil"></i> 文具領用申請
    </a>
    <a href="stationery.php?tab=card" class="btn <?php echo $active_tab === 'card' ? '' : 'btn-secondary'; ?>" style="border-radius: var(--radius-sm) var(--radius-sm) 0 0; padding: 10px 20px; margin-bottom: -2px;">
        <i class="fa-solid fa-address-card"></i> 個人名片印製
    </a>
</div>

<div class="dashboard-grid">
    
    <!-- 1. 申請表單 (Col-6) -->
    <div class="card col-6">
        <?php if (!empty($status_msg)): ?>
            <?php if (strpos($status_msg, 'success:') === 0): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo substr($status_msg, 8); ?></div>
            <?php else: ?>
                <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo substr($status_msg, 6); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($active_tab === 'stationery'): ?>
            <!-- 文具領用表單 -->
            <div class="card-header" style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                <h2 class="card-title" style="font-size: 1.1rem;"><i class="fa-solid fa-list-check"></i> 填報文具領用清單</h2>
            </div>
            <form action="stationery.php?tab=stationery" method="POST">
                <input type="hidden" name="action" value="apply_stationery">
                
                <div class="form-group">
                    <label class="form-label">選擇申領項目 (可複選)</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label><input type="checkbox" name="items[]" value="中性原子筆(黑)"> 中性原子筆 (黑)</label>
                        <label><input type="checkbox" name="items[]" value="中性原子筆(藍)"> 中性原子筆 (藍)</label>
                        <label><input type="checkbox" name="items[]" value="螢光筆(黃)"> 螢光筆 (黃)</label>
                        <label><input type="checkbox" name="items[]" value="A4影印紙(包)"> A4影印紙 (包)</label>
                        <label><input type="checkbox" name="items[]" value="便條紙(本)"> 便條紙 (本)</label>
                        <label><input type="checkbox" name="items[]" value="環保長尾夾"> 環保長尾夾</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="qty" class="form-label">基本申請組數</label>
                    <input type="number" id="qty" name="qty" class="form-control" value="1" min="1" max="10" required>
                </div>
                
                <div class="form-group">
                    <label for="memo" class="form-label">備註用途 / 特殊規格說明</label>
                    <input type="text" id="memo" name="memo" class="form-control" placeholder="非必填，例如: 會議報告用、特殊寬度長尾夾">
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> 送出文具申請
                </button>
            </form>
            
        <?php else: ?>
            <!-- 名片印製表單 -->
            <div class="card-header" style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                <h2 class="card-title" style="font-size: 1.1rem;"><i class="fa-solid fa-address-card"></i> 填報名片印製內容</h2>
            </div>
            <form action="stationery.php?tab=card" method="POST">
                <input type="hidden" name="action" value="apply_card">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">中文姓名 (唯讀)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['realname']); ?>" style="background-color: #f1f5f9; cursor: not-allowed;" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">中文部門 (唯讀)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['department']); ?>" style="background-color: #f1f5f9; cursor: not-allowed;" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name_en" class="form-label">英文姓名 (印製於名片)</label>
                    <input type="text" id="name_en" name="name_en" class="form-control" placeholder="例如: Kevin Wang" required>
                </div>
                
                <div class="form-group">
                    <label for="title_en" class="form-label">英文職稱</label>
                    <input type="text" id="title_en" name="title_en" class="form-control" placeholder="例如: Senior Software Engineer" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="boxes" class="form-label">申請印製數量 (盒)</label>
                        <select id="boxes" name="boxes" class="form-control">
                            <option value="1">1 盒</option>
                            <option value="2">2 盒</option>
                            <option value="3" selected>3 盒 (標準)</option>
                            <option value="5">5 盒 (主管級)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">印製樣式</label>
                        <label style="display: block; margin-top: 8px;"><input type="checkbox" name="double_sided" value="1" checked> 雙面印製 (背英文)</label>
                    </div>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">
                    <i class="fa-solid fa-address-card"></i> 送出名片印製申請
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- 2. 歷史申領紀錄列表 (Col-6) -->
    <div class="card col-6">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> 我的歷史資源申請紀錄</h2>
            <span class="room-status-badge available">全部紀錄</span>
        </div>
        
        <div style="max-height: 420px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 2px solid var(--border-color); text-align: left;">
                        <th style="padding: 10px;">日期</th>
                        <th style="padding: 10px;">類別</th>
                        <th style="padding: 10px;">申領項目與明細</th>
                        <th style="padding: 10px;">數量</th>
                        <th style="padding: 10px;">審核狀態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($_SESSION['mock_stationery']) as $row): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px; font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['date']); ?></td>
                            <td style="padding: 10px;">
                                <span class="news-badge <?php echo $row['type'] === 'card' ? 'monthly' : ''; ?>" style="font-size: 0.75rem;">
                                    <?php echo $row['type'] === 'card' ? '名片' : '文具'; ?>
                                </span>
                            </td>
                            <td style="padding: 10px; font-weight: 500; font-size: 0.85rem; color: var(--text-main);"><?php echo htmlspecialchars($row['item']); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo htmlspecialchars($row['qty']); ?></td>
                            <td style="padding: 10px;">
                                <?php if ($row['status'] === '已領取' || $row['status'] === '已送印'): ?>
                                    <span class="room-status-badge available"><?php echo htmlspecialchars($row['status']); ?></span>
                                <?php else: ?>
                                    <span class="room-status-badge booked"><?php echo htmlspecialchars($row['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<?php
require_once '../includes/footer.php';
?>

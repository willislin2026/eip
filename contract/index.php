<?php
require_once '../includes/header.php';

// 初始化合約 Session 模擬
if (!isset($_SESSION['mock_contracts'])) {
    $_SESSION['mock_contracts'] = [
        ['code' => 'CON-2026-001', 'name' => '115年度辦公大樓LED照明更換工程合約', 'vendor' => '台大照明科技股份有限公司', 'amount' => 'NT$450,000', 'date_start' => '2026-01-01', 'date_end' => '2026-12-31', 'status' => '生效中'],
        ['code' => 'CON-2026-002', 'name' => 'EIP系統智慧AI整合開發授權合約', 'vendor' => '頂尖人工智慧實驗室股份有限公司', 'amount' => 'NT$1,200,000', 'date_start' => '2026-03-15', 'date_end' => '2027-03-14', 'status' => '審核中']
    ];
}

$contract_status = '';
$contract_error = '';

// 處理新增合約
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_contract') {
    $name = trim($_POST['name']);
    $vendor = trim($_POST['vendor']);
    $amount = trim($_POST['amount']);
    $date_start = trim($_POST['date_start']);
    $date_end = trim($_POST['date_end']);
    
    if (empty($name) || empty($vendor) || empty($amount) || empty($date_start) || empty("date_end")) {
        $contract_error = '所有欄位均為必填';
    } else {
        $new_code = 'CON-2026-' . str_pad(count($_SESSION['mock_contracts']) + 1, 3, '0', STR_PAD_LEFT);
        $_SESSION['mock_contracts'][] = [
            'code' => $new_code,
            'name' => $name,
            'vendor' => $vendor,
            'amount' => 'NT$' . number_format((float)str_replace(['$', ',', 'NT$'], '', $amount)),
            'date_start' => $date_start,
            'date_end' => $date_end,
            'status' => '審核中'
        ];
        $contract_status = '合約登錄成功！已送出起草會簽流程。';
    }
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-file-contract"></i> 合約管理系統</h1>
    <p class="page-subtitle">
        <span style="background-color: var(--primary-light); color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; margin-right: 5px;">獨立工作區</span> 
        本模組為獨立資料夾工作區開發，透過引入共用範本，實現與主系統完全一致的 UI 視覺風格。
    </p>
</div>

<div class="dashboard-grid">

    <!-- ===== 左側子選單欄 (Col-3) ===== -->
    <div class="sidebar-column">
        <div class="sidebar-nav-card">
            <div class="sidebar-nav-title">
                <i class="fa-solid fa-file-contract"></i> 知識管理導覽
            </div>
            <a href="#add-contract" class="sidebar-nav-item"><i class="fa-solid fa-file-signature"></i> 起草與登錄新合約</a>
            <a href="#contract-list" class="sidebar-nav-item"><i class="fa-solid fa-list"></i> 公司現存合約清單</a>
            <a href="#training" class="sidebar-nav-item"><i class="fa-solid fa-chalkboard-user"></i> 教育訓練課程講義</a>
            <a href="#ehs" class="sidebar-nav-item"><i class="fa-solid fa-heart-pulse"></i> 環安衛 (EHS) 政策</a>
            <div style="border-top: 1px solid rgba(255,255,255,0.2); margin: 0.8rem 0;"></div>
            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); padding-left: 0.8rem; margin-bottom: 0.5rem; font-weight: bold;"><i class="fa-solid fa-link"></i> 快速連結</div>
            <a href="https://www.google.com" target="_blank" class="sidebar-nav-item"><i class="fa-solid fa-link"></i> ISO 文件區</a>
            <a href="https://www.google.com" target="_blank" class="sidebar-nav-item"><i class="fa-solid fa-barcode"></i> 條碼管理系統</a>
        </div>
    </div>

    <!-- ===== 中間主要內容欄 (Col-6) ===== -->
    <div class="main-column">
        <!-- 1. 新增合約表單 -->
        <div class="card" id="add-contract">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-file-signature"></i> 起草與登錄新合約</h2>
                <i class="fa-solid fa-plus" style="color: var(--primary-light);"></i>
            </div>

            <?php if (!empty($contract_status)): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $contract_status; ?></div>
            <?php endif; ?>
            <?php if (!empty($contract_error)): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $contract_error; ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="add_contract">

                <div class="form-group">
                    <label for="name" class="form-label">合約名稱</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="請輸入合約主旨與名稱" required>
                </div>

                <div class="form-group">
                    <label for="vendor" class="form-label">協力廠商 (乙方)</label>
                    <input type="text" id="vendor" name="vendor" class="form-control" placeholder="請輸入乙方廠商完整名稱" required>
                </div>

                <div class="form-group">
                    <label for="amount" class="form-label">合約總金額 (TWD)</label>
                    <input type="number" id="amount" name="amount" class="form-control" placeholder="例如: 150000" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label for="date_start" class="form-label">合約起日</label>
                        <input type="date" id="date_start" name="date_start" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date_end" class="form-label">合約迄日</label>
                        <input type="date" id="date_end" name="date_end" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%;">
                    <i class="fa-solid fa-file-arrow-up"></i> 登入新合約
                </button>
            </form>
        </div>

        <!-- 2. 公司現存合約清單 -->
        <div class="card" id="contract-list">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-list"></i> 公司現存合約清單</h2>
                <span class="room-status-badge available">限制閱讀</span>
            </div>

            <div style="max-height: 420px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 10px;">合約編號</th>
                            <th style="padding: 10px;">合約名稱 / 簽訂對象</th>
                            <th style="padding: 10px;">合約金額</th>
                            <th style="padding: 10px;">合約期間</th>
                            <th style="padding: 10px;">狀態</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($_SESSION['mock_contracts']) as $con): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px; font-family: monospace; font-weight: bold; color: var(--primary-color);">
                                    <?php echo htmlspecialchars($con['code']); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <div style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($con['name']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">廠商: <?php echo htmlspecialchars($con['vendor']); ?></div>
                                </td>
                                <td style="padding: 10px; font-weight: 700; color: #2c3e50;">
                                    <?php echo htmlspecialchars($con['amount']); ?>
                                </td>
                                <td style="padding: 10px; font-size: 0.75rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($con['date_start']) . ' ~ ' . htmlspecialchars($con['date_end']); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php if ($con['status'] === '生效中'): ?>
                                        <span class="room-status-badge available"><?php echo htmlspecialchars($con['status']); ?></span>
                                    <?php else: ?>
                                        <span class="room-status-badge booked"><?php echo htmlspecialchars($con['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== 右側輔助小工具欄 (Col-3) ===== -->
    <div class="layout-right">
        <!-- 3. 教育訓練 -->
        <div class="card" id="training">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-chalkboard-user"></i> 教育訓練課程</h2>
                <i class="fa-solid fa-graduation-cap" style="color: var(--primary-light);"></i>
            </div>
            <div style="font-size: 0.85rem;">
                <ul style="padding-left: 1.2rem; display: flex; flex-direction: column; gap: 8px; margin: 0;">
                    <li><a href="https://www.google.com" target="_blank">115年夏季資安宣導講義</a></li>
                    <li><a href="https://www.google.com" target="_blank">ISO 9001:2015 稽核影音</a></li>
                    <li><a href="https://www.google.com" target="_blank">ESG 淨零碳排與永續講義</a></li>
                </ul>
            </div>
        </div>

        <!-- 4. 環安衛政策 -->
        <div class="card" id="ehs">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-heart-pulse"></i> 環安衛政策</h2>
                <i class="fa-solid fa-shield-halved" style="color: var(--warning);"></i>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 8px;">
                <p style="text-align: justify; font-size: 0.8rem; color: var(--text-main); margin: 0; line-height: 1.4;">
                    公司秉持「安全第一、零職災、綠色職場」之環安衛基本政策。全體同仁於辦公室與廠區操作時，務必嚴格遵守安全規範。若發現公共區域硬體破損或安全疑慮，請立即向總務處申報通報。
                </p>
            </div>
        </div>
    </div>

</div>




<?php
require_once '../includes/footer.php';
?>

<?php
require_once '../includes/header.php';
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-paste"></i> 行政專區</h1>
    <p class="page-subtitle">此處提供公司內部各大行政部門規章、文具/名片行政表單申請，以及會議室時段預約功能。</p>
</div>

<div class="dashboard-grid">

    <!-- ===== 左側子選單欄 (Col-3) ===== -->
    <div class="sidebar-column">
        <div class="sidebar-nav-card">
            <div class="sidebar-nav-title">
                <i class="fa-solid fa-paste"></i> 行政專區導覽
            </div>
            <a href="#hr" class="sidebar-nav-item"><i class="fa-solid fa-users"></i> 人力資源部</a>
            <a href="#legal" class="sidebar-nav-item"><i class="fa-solid fa-scale-balanced"></i> 法務部公告</a>
            <a href="#it" class="sidebar-nav-item"><i class="fa-solid fa-laptop-code"></i> 資訊部守則</a>
            <a href="finance.php" class="sidebar-nav-item"><i class="fa-solid fa-coins"></i> 財務核銷專區</a>
            <div style="border-top: 1px solid rgba(255,255,255,0.2); margin: 0.8rem 0;"></div>
            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); padding-left: 0.8rem; margin-bottom: 0.5rem; font-weight: bold;"><i class="fa-solid fa-cubes"></i> 總務部服務</div>
            <a href="order_manage.php" class="sidebar-nav-item"><i class="fa-solid fa-utensils"></i> 訂餐管理系統</a>
            <a href="meeting_room.php" class="sidebar-nav-item"><i class="fa-solid fa-door-open"></i> 會議室預約系統</a>
            <a href="stationery.php?tab=stationery" class="sidebar-nav-item"><i class="fa-solid fa-pen-fancy"></i> 辦公文具申領</a>
            <a href="stationery.php?tab=card" class="sidebar-nav-item"><i class="fa-solid fa-address-card"></i> 商務名片印製</a>
        </div>
    </div>

    <!-- ===== 中間主要內容欄 (Col-6) ===== -->
    <div class="main-column">
        <!-- 1. 人力資源部 -->
        <div class="card" id="hr">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-users"></i> 人力資源部公告</h2>
                <i class="fa-solid fa-bullhorn" style="color: var(--primary-light);"></i>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <strong>📌 115年度績效考評時程</strong>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">自 7/1 起開放系統自評，請於 7/25 前完成主管面談初評。</p>
                </div>
                <div>
                    <strong>📌 特休假結轉通知</strong>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">年度特休展延請填報展延表單，逾期將依法結算薪資。</p>
                </div>
            </div>
        </div>

        <!-- 2. 法務部 -->
        <div class="card" id="legal">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-scale-balanced"></i> 法務部公告</h2>
                <span class="room-status-badge available">法規</span>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <strong>📌 合約會簽作業準則</strong>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">凡對外合約必須先提交法務室進行第一輪審閱，確認合規後始得簽核。</p>
                </div>
                <div>
                    <strong>📌 保密協定 (NDA)</strong>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">與外部廠商洽談合作前，務必完成雙方 NDA 簽署作業。</p>
                </div>
            </div>
        </div>

        <!-- 3. 資訊部 -->
        <div class="card" id="it">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-laptop-code"></i> 資訊部守則</h2>
                <i class="fa-solid fa-triangle-exclamation" style="color: var(--warning);"></i>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <strong>📌 密碼複雜度要求</strong>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">密碼須含大小寫字母及數字，系統每 90 天強制要求變更。</p>
                </div>
            </div>
        </div>

        <!-- 4. 財務部 -->
        <div class="card" id="finance">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-coins"></i> 財務部出差專區</h2>
                <span class="room-status-badge available" style="background-color: #e0f2fe; color: #0369a1;">財務</span>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; justify-content: space-between; height: calc(100% - 35px);">
                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;">
                    <div>
                        <strong>📌 本週基準匯率已更新</strong>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">USD/TWD 核銷匯率為 32.450，出差報支請依此標準申報。</p>
                    </div>
                </div>
                <a href="finance.php" class="btn" style="font-size: 0.75rem; padding: 5px 10px; width: 100%;"><i class="fa-solid fa-circle-info"></i> 財務核銷專區</a>
            </div>
        </div>
    </div>

    <!-- ===== 右側輔助小工具欄 (Col-3) ===== -->
    <div class="layout-right">
        <!-- 總務部快速服務通道 -->
        <div class="card" id="general-affairs" style="background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%); border: 1px solid #bae6fd; padding: 1.2rem;">
            <div class="card-header" style="border-bottom-color: #bae6fd; padding-bottom: 0.5rem; margin-bottom: 0.8rem;">
                <h2 class="card-title" style="color: var(--primary-color); font-size: 1rem;"><i class="fa-solid fa-cubes"></i> 總務快捷</h2>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="order_manage.php" class="btn btn-secondary" style="font-size: 0.8rem; padding: 8px 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-solid fa-utensils" style="color: var(--primary-color); margin-right: 6px;"></i> 訂餐管理</span>
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
                </a>
                <a href="meeting_room.php" class="btn btn-secondary" style="font-size: 0.8rem; padding: 8px 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-solid fa-door-open" style="color: var(--primary-color); margin-right: 6px;"></i> 會議室預約</span>
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
                </a>
                <a href="stationery.php?tab=stationery" class="btn btn-secondary" style="font-size: 0.8rem; padding: 8px 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-solid fa-pen-fancy" style="color: var(--primary-color); margin-right: 6px;"></i> 文具申領</span>
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
                </a>
                <a href="stationery.php?tab=card" class="btn btn-secondary" style="font-size: 0.8rem; padding: 8px 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-solid fa-address-card" style="color: var(--primary-color); margin-right: 6px;"></i> 名片印製</span>
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>
    </div>

</div>




<?php
require_once '../includes/footer.php';
?>

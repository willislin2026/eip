<?php
require_once '../includes/header.php';
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-coins"></i> 財務核銷專區</h1>
    <p class="page-subtitle">此處提供同仁出差核銷基準匯率查詢、轉帳手續費規範、財務常用表單下載及報支常見問題。</p>
</div>

<div class="dashboard-grid">
    <!-- 1. 出差基準匯率 (Col-6) -->
    <div class="card col-6" id="exchange-rate">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-money-bill-trend-up"></i> 本週出差核銷基準匯率</h2>
            <span class="room-status-badge available">每週一更新</span>
        </div>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">
            同仁申報國外出差旅費報支時，若無檢附水單，請統一依據下表財務部公告之基準匯率進行核銷折算。
        </p>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; background-color: #f8fafc;">
                    <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">幣別</th>
                    <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">財務核銷匯率 (TWD)</th>
                    <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">波動趨勢</th>
                    <th style="padding: 12px; font-weight: 600; color: var(--primary-color);">更新日期</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #e2e8f0; transition: var(--transition);">
                    <td style="padding: 12px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 1.2rem;">🇺🇸</span> USD (美金)
                    </td>
                    <td style="padding: 12px; font-weight: 600; font-family: 'Outfit';">32.450</td>
                    <td style="padding: 12px; color: var(--danger);"><i class="fa-solid fa-arrow-trend-up"></i> 升值 (上週: 32.31)</td>
                    <td style="padding: 12px; color: var(--text-muted); font-size: 0.85rem;"><?php echo date('Y-m-d', strtotime('last Monday')); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 1.2rem;">🇯🇵</span> JPY (日幣)
                    </td>
                    <td style="padding: 12px; font-weight: 600; font-family: 'Outfit';">0.2030</td>
                    <td style="padding: 12px; color: var(--success);"><i class="fa-solid fa-arrow-trend-down"></i> 貶值 (上週: 0.2055)</td>
                    <td style="padding: 12px; color: var(--text-muted); font-size: 0.85rem;"><?php echo date('Y-m-d', strtotime('last Monday')); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 1.2rem;">🇪🇺</span> EUR (歐元)
                    </td>
                    <td style="padding: 12px; font-weight: 600; font-family: 'Outfit';">34.820</td>
                    <td style="padding: 12px; color: var(--text-muted);"><i class="fa-solid fa-minus"></i> 持平</td>
                    <td style="padding: 12px; color: var(--text-muted); font-size: 0.85rem;"><?php echo date('Y-m-d', strtotime('last Monday')); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 1.2rem;">🇨🇳</span> CNY (人民幣)
                    </td>
                    <td style="padding: 12px; font-weight: 600; font-family: 'Outfit';">4.4650</td>
                    <td style="padding: 12px; color: var(--danger);"><i class="fa-solid fa-arrow-trend-up"></i> 升值 (上週: 4.450)</td>
                    <td style="padding: 12px; color: var(--text-muted); font-size: 0.85rem;"><?php echo date('Y-m-d', strtotime('last Monday')); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 2. 國內跨行匯款手續費事宜 (Col-6) -->
    <div class="card col-6" id="remittance">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-comments-dollar"></i> 國內跨行轉帳手續費規範</h2>
            <span class="room-status-badge booked" style="background-color: rgba(241, 196, 15, 0.15); color: #b7950b;">重要公告</span>
        </div>
        <div style="background-color: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 15px;">
            <p style="font-size: 0.95rem; color: var(--text-main); font-weight: bold; display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                <i class="fa-solid fa-circle-exclamation" style="color: var(--warning); font-size: 1.2rem;"></i> 跨行手續費 NT$15 需自理說明：
            </p>
            <p style="font-size: 0.9rem; color: var(--text-main); text-align: justify; line-height: 1.7;">
                凡同仁報支出差旅費、交際費或零用金核銷，如指定非公司合作銀行撥款，其跨行轉帳產生的 <strong>NT$15元</strong> 手續費將由同仁自理（財務部在撥款時會從核銷金額中直接扣除）。
            </p>
            <div style="margin-top: 15px; border-top: 1px dashed var(--border-color); padding-top: 15px; font-size: 0.85rem; color: var(--text-muted);">
                <strong>本公司合作銀行 (免手續費)：</strong>
                <ul style="padding-left: 20px; margin-top: 5px; display: flex; flex-direction: column; gap: 4px;">
                    <li>合作金庫商業銀行 (各分行)</li>
                    <li>如欲變更薪資或核銷撥款帳戶，請洽人資部填表申請。</li>
                </ul>
            </div>
        </div>
        <div style="display: flex; gap: 10px; background-color: #e0f2fe; padding: 12px; border-radius: var(--radius-sm); border: 1px solid #bae6fd; font-size: 0.85rem; color: #0369a1;">
            <i class="fa-solid fa-circle-info" style="font-size: 1.1rem; margin-top: 2px;"></i>
            <div>
                <strong>溫馨提醒：</strong> 零用金小額核銷（NT$1,000 元以下），建議優先使用現金方式至行政大樓財務櫃檯直接領取，可避免轉帳手續費扣除。
            </div>
        </div>
    </div>

    <!-- 3. 常用財務表單下載 (Col-6) -->
    <div class="card col-6" id="downloads">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-file-arrow-down"></i> 常用財務報銷表單下載</h2>
            <i class="fa-solid fa-download" style="color: var(--primary-light);"></i>
        </div>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">
            請下載並使用最新版本的表格填報，並於每月 25 日前送交至財務部進行當月核銷結帳。
        </p>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); transition: var(--transition);" class="download-item">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fa-solid fa-file-excel" style="font-size: 2rem; color: #1e7145;"></i>
                    <div>
                        <strong style="font-size: 0.9rem; color: var(--primary-color);">國外出差旅費報支單 (v2026.01)</strong>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">適用國外出差之機票、住宿、日支費核銷申報</p>
                    </div>
                </div>
                <a href="#" class="btn btn-secondary" onclick="alert('開始下載 國外出差旅費報支單.xlsx (模擬)'); return false;" style="padding: 5px 12px; font-size: 0.8rem;"><i class="fa-solid fa-download"></i> 下載</a>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" class="download-item">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fa-solid fa-file-excel" style="font-size: 2rem; color: #1e7145;"></i>
                    <div>
                        <strong style="font-size: 0.9rem; color: var(--primary-color);">國內出差報支暨派車申請單 (v2025.10)</strong>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">適用國內跨廠區公出、油資補助及過路費申報</p>
                    </div>
                </div>
                <a href="#" class="btn btn-secondary" onclick="alert('開始下載 國內出差報支單.xlsx (模擬)'); return false;" style="padding: 5px 12px; font-size: 0.8rem;"><i class="fa-solid fa-download"></i> 下載</a>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" class="download-item">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fa-solid fa-file-word" style="font-size: 2rem; color: #2b579a;"></i>
                    <div>
                        <strong style="font-size: 0.9rem; color: var(--primary-color);">小額零用金報銷清冊 (v2024.12)</strong>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">文具、茶水等金額 NT$2,000 元以下之緊急小額報銷</p>
                    </div>
                </div>
                <a href="#" class="btn btn-secondary" onclick="alert('開始下載 小額零用金報銷清冊.docx (模擬)'); return false;" style="padding: 5px 12px; font-size: 0.8rem;"><i class="fa-solid fa-download"></i> 下載</a>
            </div>
        </div>
    </div>

    <!-- 4. 財務報銷常見問題 FAQ (Col-6) -->
    <div class="card col-6" id="faq">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-circle-question"></i> 財務核銷 FAQ</h2>
            <i class="fa-solid fa-lightbulb" style="color: var(--warning);"></i>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.85rem;">
            <details style="background: #f8fafc; padding: 10px 15px; border-radius: 6px; border: 1px solid var(--border-color);" open>
                <summary style="font-weight: 700; color: var(--primary-color); cursor: pointer; outline: none; margin-bottom: 5px;">
                    Q1: 發票抬頭跟統編填寫錯誤，是否能進行報支？
                </summary>
                <div style="color: var(--text-main); margin-top: 5px; padding-top: 5px; border-top: 1px solid #e2e8f0; text-align: justify;">
                    <strong>A:</strong> 不可以。本公司發票抬頭請務必填寫「<strong>英群股份有限公司</strong>」，統一編號為「<strong>12345678</strong>」。若取得之二聯式發票、收據或電子發票統編有誤，請向商家申請更正或重新開立，否則財務部將予以退件。
                </div>
            </details>
            
            <details style="background: #f8fafc; padding: 10px 15px; border-radius: 6px; border: 1px solid var(--border-color);">
                <summary style="font-weight: 700; color: var(--primary-color); cursor: pointer; outline: none;">
                    Q2: 出差高鐵票或車票遺失，該如何報銷？
                </summary>
                <div style="color: var(--text-main); margin-top: 5px; padding-top: 5px; border-top: 1px solid #e2e8f0; text-align: justify;">
                    <strong>A:</strong> 若車票遺失，請至高鐵或台鐵櫃檯申請「購票證明」或「乘車證明」代之。若無法提供相關憑證，需填寫「支出證明單」，並述明遺失理由、乘車起訖點，經一級主管及財務主管特准後始能核銷。
                </div>
            </details>
            
            <details style="background: #f8fafc; padding: 10px 15px; border-radius: 6px; border: 1px solid var(--border-color);">
                <summary style="font-weight: 700; color: var(--primary-color); cursor: pointer; outline: none;">
                    Q3: 國外出差信用卡刷卡手續費可以核銷嗎？
                </summary>
                <div style="color: var(--text-main); margin-top: 5px; padding-top: 5px; border-top: 1px solid #e2e8f0; text-align: justify;">
                    <strong>A:</strong> 可以。請提供該筆信用卡帳單明細影本（需包含同仁姓名、刷卡日期、消費金額及國外交易手續費金額），財務部將依實際產生的海外交易手續費核實給付。
                </div>
            </details>
        </div>
    </div>
</div>

<style>
.download-item:hover {
    border-color: var(--primary-light) !important;
    background-color: #f8fafc;
}
details summary::-webkit-details-marker {
    display: none;
}
details summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
details summary::after {
    content: "\f078";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    transition: transform 0.2s ease;
}
details[open] summary::after {
    transform: rotate(180deg);
}
</style>

<?php
require_once '../includes/footer.php';
?>

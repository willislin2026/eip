<?php
require_once 'includes/header.php';

// 取得最新消息資料
$bulletins = [];
if ($_SESSION['use_mysql']) {
    $stmt = $pdo->query("SELECT * FROM `bulletin` ORDER BY publish_date DESC");
    $bulletins = $stmt->fetchAll();
} else {
    $bulletins = $_SESSION['mock_bulletin'];
    // 排序
    usort($bulletins, function($a, $b) {
        return strcmp($b['publish_date'], $a['publish_date']);
    });
}

// 取得員工資料清單，供分機搜尋與帳號維護使用
$employees = [];
if ($_SESSION['use_mysql']) {
    $stmt = $pdo->query("SELECT id, username, realname, department, ext_no, email, approval_level FROM `employees` ORDER BY department ASC");
    $employees = $stmt->fetchAll();
} else {
    $employees = array_values($_SESSION['mock_employees']);
}

// 將員工資料轉為 JSON 供 JS 即時搜尋
$employees_json = json_encode($employees, JSON_UNESCAPED_UNICODE);
?>

<!-- 頁面標題區 -->
<div class="page-header">
    <h1 class="page-title">歡迎回來，<?php echo htmlspecialchars($user['realname']); ?> 同仁</h1>
    <p class="page-subtitle">今天是 <?php echo date('Y 年 m 月 d 日'); ?>，祝您有美好、高效的一天！您的簽核層級為：<strong><?php echo htmlspecialchars($user['approval_level']); ?></strong>。</p>
</div>

<!-- 首頁資訊網格 (Dashboard Grid) -->
<div class="dashboard-grid">

    <!-- ===== 左側子選單欄 (Col-3) ===== -->
    <div class="sidebar-column">
        <div class="sidebar-nav-card">
            <div class="sidebar-nav-title">
                <i class="fa-solid fa-house"></i> 首頁導覽
            </div>
            <a href="javascript:void(0);" onclick="switchHomeTab('default'); window.location.hash='news';" class="sidebar-nav-item"><i class="fa-regular fa-newspaper"></i> 最新消息</a>
            <a href="javascript:void(0);" onclick="switchHomeTab('default'); window.location.hash='news';" class="sidebar-nav-item"><i class="fa-solid fa-book-open"></i> 電子月刊</a>
            <a href="javascript:void(0);" onclick="switchHomeTab('calendar');" class="sidebar-nav-item"><i class="fa-regular fa-calendar-days"></i> 萬年曆</a>
            <a href="javascript:void(0);" onclick="switchHomeTab('default'); window.location.hash='directory';" class="sidebar-nav-item"><i class="fa-solid fa-address-book"></i> 分機/Email 查詢</a>
            <a href="http://mail.btc.com.tw" target="_blank" class="sidebar-nav-item"><i class="fa-solid fa-envelope"></i> Web-Mail</a>
            <div style="border-top: 1px solid rgba(255,255,255,0.2); margin: 0.8rem 0;"></div>
            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); padding-left: 0.8rem; margin-bottom: 0.5rem; font-weight: bold;"><i class="fa-solid fa-gears"></i> 系統管理</div>
            <a href="<?php echo BASE_URL; ?>admin/users.php" class="sidebar-nav-item"><i class="fa-solid fa-users-gear"></i> 員工帳號維護</a>
            <a href="<?php echo BASE_URL; ?>admin/approval_levels.php" class="sidebar-nav-item"><i class="fa-solid fa-sitemap"></i> 簽核層級 (人事)</a>
        </div>
    </div>

    <!-- ===== 中間主要內容欄 (Col-6) ===== -->
    <div class="main-column">
        <!-- 1. 最新消息與電子月刊 -->
        <div class="card" id="news">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-regular fa-newspaper"></i> 最新消息 &amp; 電子月刊</h2>
                <i class="fa-solid fa-bullhorn" style="color: var(--primary-light);"></i>
            </div>
            <ul class="news-list">
                <?php foreach ($bulletins as $item): ?>
                    <li class="news-item">
                        <div>
                            <span class="news-badge <?php echo $item['type'] === 'monthly' ? 'monthly' : ''; ?>">
                                <?php echo $item['type'] === 'monthly' ? '月刊' : '最新'; ?>
                            </span>
                            <a href="javascript:void(0);" onclick="showNewsDetail(<?php echo htmlspecialchars(json_encode($item, JSON_UNESCAPED_UNICODE)); ?>)" class="news-title-link">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </div>
                        <span class="news-date"><?php echo htmlspecialchars($item['publish_date']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 2. 員工分機與 Email 快速查詢 -->
        <div class="card" id="directory">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-address-book"></i> 員工分機與 Email 快速查詢</h2>
                <span style="font-size: 0.8rem; color: var(--text-muted);">即時過濾</span>
            </div>
            <div class="search-box">
                <input type="text" id="search-input" class="form-control" placeholder="請輸入關鍵字... (例如: 王小明)" style="flex: 1;">
                <button class="btn" onclick="clearSearch()"><i class="fa-solid fa-rotate-left"></i> 清除</button>
            </div>
            <div style="max-height: 300px; overflow-y: auto;">
                <table class="search-results">
                    <thead>
                        <tr>
                            <th>姓名</th>
                            <th>部門</th>
                            <th>分機</th>
                            <th>電子郵件</th>
                        </tr>
                    </thead>
                    <tbody id="search-table-body">
                        <!-- 由 JavaScript 即時過濾填入 -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. 大萬年曆與農民曆專區 -->
        <div class="card" id="large-calendar-container" style="display: none;">
            <div class="card-header" style="border-bottom: 2px solid var(--primary-light); padding-bottom: 0.8rem; margin-bottom: 1.2rem;">
                <h2 class="card-title"><i class="fa-regular fa-calendar-days"></i> 企業萬年曆 &amp; 每日黃曆</h2>
                <div style="display: flex; gap: 6px;">
                    <button id="l-prev-month" class="btn" style="padding: 4px 8px; font-size: 0.75rem;"><i class="fa-solid fa-chevron-left"></i> 上個月</button>
                    <button id="l-today-btn" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem;"><i class="fa-solid fa-calendar-day"></i> 返回今天</button>
                    <button id="l-next-month" class="btn" style="padding: 4px 8px; font-size: 0.75rem;">下個月 <i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            
            <!-- 大萬年曆標題 -->
            <div style="text-align: center; margin-bottom: 15px;">
                <h3 id="l-calendar-month-year" style="color: var(--primary-color); font-size: 1.4rem; font-weight: 700; margin: 0;">2026年 6月</h3>
            </div>
            
            <!-- 大日曆主網格 -->
            <div class="calendar-grid" id="l-calendar-days-container" style="font-size: 0.95rem; gap: 8px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(7, 1fr);">
                <!-- 由 JavaScript 動態填入大日曆，包含農曆日期標記 -->
            </div>
            
            <!-- 類農民曆顯示看板 -->
            <div id="l-farmer-panel" style="background: linear-gradient(135deg, #fffcf0 0%, #fef9e6 100%); border: 1px solid #f3d995; border-radius: var(--radius-md); padding: 1.2rem; display: grid; grid-template-columns: 2fr 3fr; gap: 15px;">
                <!-- 左側：黃曆日期資訊 -->
                <div style="border-right: 1px dashed #d5c295; padding-right: 15px; display: flex; flex-direction: column; justify-content: center; text-align: center; gap: 4px;">
                    <div style="font-size: 0.85rem; color: #7d6608; font-weight: 600;" id="f-solar-date">西元 2026 年 6 月 29 日</div>
                    <div style="font-size: 1.5rem; color: #b7950b; font-weight: 700; margin: 2px 0;" id="f-lunar-date">農曆 五月十五</div>
                    <div style="font-size: 0.8rem; color: #7d6608;" id="f-chinese-date">歲次 丙午年 庚子月 癸酉日</div>
                    <div style="font-size: 0.8rem; color: #c0392b; font-weight: 600;" id="f-zodiac-clash">生肖屬馬 ｜ 煞北沖鼠</div>
                </div>
                <!-- 右側：宜 / 忌 雙欄看板 -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <!-- 宜 -->
                    <div style="background-color: rgba(46, 204, 113, 0.06); border: 1px solid rgba(46, 204, 113, 0.25); border-radius: var(--radius-sm); padding: 8px;">
                        <h4 style="color: #27ae60; font-size: 0.9rem; border-bottom: 1px solid rgba(46, 204, 113, 0.25); padding-bottom: 4px; margin-bottom: 6px; display: flex; align-items: center; gap: 4px; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> 宜</h4>
                        <div id="f-suitable" style="font-size: 0.8rem; color: #1e8449; line-height: 1.5; font-weight: 600;">
                            祭祀、出行、交易、納財
                        </div>
                    </div>
                    <!-- 忌 -->
                    <div style="background-color: rgba(231, 76, 60, 0.06); border: 1px solid rgba(231, 76, 60, 0.25); border-radius: var(--radius-sm); padding: 8px;">
                        <h4 style="color: #c0392b; font-size: 0.9rem; border-bottom: 1px solid rgba(231, 76, 60, 0.25); padding-bottom: 4px; margin-bottom: 6px; display: flex; align-items: center; gap: 4px; font-weight: 700;"><i class="fa-solid fa-circle-xmark"></i> 忌</h4>
                        <div id="f-unsuitable" style="font-size: 0.8rem; color: #7b241c; line-height: 1.5; font-weight: 600;">
                            動土、詞訟、安葬、破土
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 右側輔助小工具欄 (Col-3) ===== -->
    <div class="layout-right">
        <!-- 1. 萬年曆卡片 -->
        <div class="card calendar-widget" id="calendar">
            <div class="card-header">
                <h2 class="card-title" style="font-size: 1rem;"><i class="fa-regular fa-calendar-days"></i> 萬年曆</h2>
                <span style="font-size: 0.75rem; font-weight: 600; color: var(--primary-color);" id="calendar-month-year"></span>
            </div>
            <div class="calendar-header">
                <button id="prev-month" class="btn" style="padding: 1px 6px; font-size: 0.75rem;"><i class="fa-solid fa-chevron-left"></i></button>
                <span style="font-size: 0.8rem; font-weight: 500;" id="calendar-current-day">今天: <?php echo date('m/d'); ?></span>
                <button id="next-month" class="btn" style="padding: 1px 6px; font-size: 0.75rem;"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="calendar-grid" id="calendar-days-container" style="font-size: 0.8rem; gap: 3px;">
                <!-- 由 JavaScript 動態填入 -->
            </div>
        </div>

        <!-- 2. 天氣與快捷外部系統 -->
        <div class="card" style="padding: 1.2rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div class="weather-widget" style="padding: 0.8rem; border-radius: var(--radius-sm); margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div class="weather-info">
                    <div class="weather-desc" style="font-size: 0.8rem;"><i class="fa-solid fa-location-dot"></i> 台北市</div>
                    <div class="weather-temp" style="font-size: 1.5rem; font-weight: 700;">28°C</div>
                </div>
                <div class="weather-icon" style="color: #f59f00; font-size: 2rem;">
                    <i class="fa-solid fa-cloud-sun-rain"></i>
                </div>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding-top: 10px;">
                <h3 style="font-size: 0.85rem; color: var(--primary-color); margin-bottom: 8px;"><i class="fa-solid fa-bolt"></i> 快捷外部系統</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                    <a href="https://www.cwa.gov.tw/V8/C/W/County/County.html?CID=63" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 6px 4px;"><i class="fa-solid fa-cloud-sun"></i> 氣象</a>
                    <a href="http://mail.btc.com.tw" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 6px 4px;"><i class="fa-solid fa-envelope"></i> Mail</a>
                    <a href="http://eip.btc.com.tw/index.asp?title=4&select=4&sel=true&sequence=2&url=http://eip.btc.com.tw/tool/hr/index_hr.asp" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 6px 4px; grid-column: span 2;"><i class="fa-solid fa-file-circle-check"></i> 人資系統</a>
                </div>
            </div>
        </div>

        <!-- 3. 系統管理與人事速覽 -->
        <div class="card" id="sysadmin" style="padding: 1.2rem;">
            <div class="card-header" style="padding-bottom: 0.5rem; margin-bottom: 0.8rem;">
                <h2 class="card-title" style="font-size: 0.95rem; color: var(--primary-color);"><i class="fa-solid fa-gears"></i> 人事同步</h2>
            </div>
            <?php if ($user['approval_level'] === 'Director'): ?>
                <button class="btn" style="width: 100%; font-size: 0.75rem; padding: 6px;" onclick="alert('人事系統自動化同步中...')">
                    <i class="fa-solid fa-rotate"></i> 同步人事資料庫
                </button>
            <?php else: ?>
                <button class="btn btn-secondary" style="width: 100%; font-size: 0.75rem; padding: 6px; cursor: not-allowed;" disabled>
                    <i class="fa-solid fa-lock"></i> 僅限 Director
                </button>
            <?php endif; ?>
        </div>
    </div>

</div>
    


<!-- 最新消息詳細內容的 Dialog / Modal -->
<dialog id="news-dialog" style="border: none; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); padding: 2rem; max-width: 550px; width: 90%; margin: auto;">
    <h2 id="dialog-title" style="color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 8px; margin-bottom: 15px;">公佈標題</h2>
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px; display: flex; justify-content: space-between;">
        <span id="dialog-type" class="news-badge">分類</span>
        <span id="dialog-date">日期</span>
    </div>
    <p id="dialog-content" style="line-height: 1.7; margin-bottom: 20px; white-space: pre-line;">內容</p>
    <div style="text-align: right;">
        <button onclick="document.getElementById('news-dialog').close()" class="btn" style="padding: 6px 16px; font-size: 0.9rem;">關閉</button>
    </div>
</dialog>

<!-- JavaScript 控制 -->
<script>
// 1. 載入員工資料以供查詢
const employeeData = <?php echo $employees_json; ?>;

function renderEmployeeTable(data) {
    const tbody = document.getElementById('search-table-body');
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--text-muted);">無符合的同仁資料</td></tr>`;
        return;
    }
    
    data.forEach(emp => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${emp.realname}</strong></td>
            <td>${emp.department}</td>
            <td><i class="fa-solid fa-phone" style="color: var(--primary-light); font-size: 0.85rem;"></i> ${emp.ext_no}</td>
            <td><a href="mailto:${emp.email}"><i class="fa-regular fa-envelope"></i> ${emp.email}</a></td>
        `;
        tbody.appendChild(row);
    });
}

// 實施即時搜尋
document.getElementById('search-input').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase().trim();
    if (query === '') {
        renderEmployeeTable(employeeData);
        return;
    }
    
    const filtered = employeeData.filter(emp => {
        return emp.realname.toLowerCase().includes(query) || 
               emp.department.toLowerCase().includes(query) || 
               emp.ext_no.includes(query) ||
               emp.email.toLowerCase().includes(query);
    });
    
    renderEmployeeTable(filtered);
});

function clearSearch() {
    document.getElementById('search-input').value = '';
    renderEmployeeTable(employeeData);
}

// 2. 顯示最新消息 Modal
function showNewsDetail(item) {
    const dialog = document.getElementById('news-dialog');
    document.getElementById('dialog-title').innerText = item.title;
    document.getElementById('dialog-content').innerText = item.content;
    document.getElementById('dialog-date').innerText = '發佈日期: ' + item.publish_date;
    
    const badge = document.getElementById('dialog-type');
    if (item.type === 'monthly') {
        badge.innerText = '電子月刊';
        badge.className = 'news-badge monthly';
    } else {
        badge.innerText = '最新消息';
        badge.className = 'news-badge';
    }
    
    dialog.showModal();
}

// 3. 右側小萬年曆生成邏輯
let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth(); // 0-11

function generateCalendar(year, month) {
    const container = document.getElementById('calendar-days-container');
    container.innerHTML = '';
    
    const weekDays = ['日', '一', '二', '三', '四', '五', '六'];
    weekDays.forEach(day => {
        const head = document.createElement('div');
        head.className = 'calendar-day-head';
        head.innerText = day;
        container.appendChild(head);
    });
    
    const months = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'];
    document.getElementById('calendar-month-year').innerText = `${year}年 ${months[month]}`;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'calendar-day';
        emptyCell.style.visibility = 'hidden';
        container.appendChild(emptyCell);
    }
    
    const today = new Date();
    
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'calendar-day';
        dayCell.innerText = day;
        
        if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
            dayCell.classList.add('today');
        }
        
        dayCell.onclick = function() {
            // 小月曆點選時，同步切換至大萬年曆對應的日期
            lCurrentYear = year;
            lCurrentMonth = month;
            lSelectedYear = year;
            lSelectedMonth = month;
            lSelectedDay = day;
            switchHomeTab('calendar');
        };
        
        container.appendChild(dayCell);
    }
}

document.getElementById('prev-month').onclick = function() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentYear, currentMonth);
};

document.getElementById('next-month').onclick = function() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentYear, currentMonth);
};

// 4. 切換中欄 Tabs
function switchHomeTab(tabName) {
    const newsCard = document.getElementById('news');
    const directoryCard = document.getElementById('directory');
    const largeCalendarContainer = document.getElementById('large-calendar-container');
    
    if (tabName === 'calendar') {
        newsCard.style.display = 'none';
        directoryCard.style.display = 'none';
        largeCalendarContainer.style.display = 'block';
        
        // 觸發大萬年曆渲染
        generateLargeCalendar(lCurrentYear, lCurrentMonth);
    } else {
        newsCard.style.display = 'block';
        directoryCard.style.display = 'block';
        largeCalendarContainer.style.display = 'none';
    }
}

// 5. 大萬年曆與農民曆演算
let lCurrentYear = new Date().getFullYear();
let lCurrentMonth = new Date().getMonth();
let lSelectedYear = lCurrentYear;
let lSelectedMonth = lCurrentMonth;
let lSelectedDay = new Date().getDate();

// 計算農曆與農民曆宜忌
function getFarmerCalendarInfo(year, month, day) {
    // 基準點：2026年6月1日 ➔ 農曆丙午年四月十六，且為「庚寅」日 (干支天干第6, 地支第2)
    const baseDate = new Date(2026, 5, 1); // 0-indexed month: 5 為六月
    const targetDate = new Date(year, month, day);
    const diffTime = targetDate - baseDate;
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    
    const tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    const diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
    const shengXiao = ['鼠', '牛', '虎', '兔', '龍', '蛇', '馬', '羊', '猴', '雞', '狗', '豬'];
    
    let yearOffset = (year - 1984) % 60;
    let yearGan = tianGan[(yearOffset % 10 + 10) % 10];
    let yearZhi = diZhi[(yearOffset % 12 + 12) % 12];
    let yearZodiac = shengXiao[(yearOffset % 12 + 12) % 12];
    
    // 日干支
    let dayGanIndex = ((6 + diffDays) % 10 + 10) % 10;
    let dayZhiIndex = ((2 + diffDays) % 12 + 12) % 12;
    let dayGan = tianGan[dayGanIndex];
    let dayZhi = diZhi[dayZhiIndex];
    
    // 月干支 (以簡化算法推導月干支)
    let monthGanIndex = ((yearOffset * 12 + month + 2) % 10 + 10) % 10;
    let monthZhiIndex = ((month + 2) % 12 + 12) % 12;
    let monthGan = tianGan[monthGanIndex];
    let monthZhi = diZhi[monthZhiIndex];
    
    // 農曆日期模擬 (基準：2026年6月1日為四月十六，大月30天、小月29天交替)
    let lunarM = 4;
    let lunarD = 16;
    let days = diffDays;
    
    if (days >= 0) {
        for (let i = 0; i < days; i++) {
            lunarD++;
            let monthLength = (lunarM % 2 === 0) ? 29 : 30; // 雙月29天，單月30天
            if (lunarD > monthLength) {
                lunarD = 1;
                lunarM++;
                if (lunarM > 12) lunarM = 1;
            }
        }
    } else {
        for (let i = 0; i < Math.abs(days); i++) {
            lunarD--;
            if (lunarD < 1) {
                lunarM--;
                if (lunarM < 1) lunarM = 12;
                lunarD = (lunarM % 2 === 0) ? 29 : 30;
            }
        }
    }
    
    const lunarMonths = ['正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '臘'];
    const lunarDays = [
        '初一', '初二', '初三', '初四', '初五', '初六', '初七', '初八', '初九', '初十',
        '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十',
        '廿一', '廿二', '廿三', '廿四', '廿五', '廿六', '廿七', '廿八', '廿九', '三十'
    ];
    
    let lunarMonthStr = lunarMonths[lunarM - 1] + '月';
    let lunarDayStr = lunarDays[lunarD - 1];
    
    // 生肖沖煞與煞向
    let clashZodiac = shengXiao[(dayZhiIndex + 6) % 12];
    
    // 煞向 (申子辰煞南，巳酉丑煞東，寅午戌煞北，亥卯未煞西)
    let shaDir = '';
    let zhi = diZhi[dayZhiIndex];
    if (['申', '子', '辰'].includes(zhi)) shaDir = '南';
    else if (['巳', '酉', '丑'].includes(zhi)) shaDir = '東';
    else if (['寅', '午', '戌'].includes(zhi)) shaDir = '北';
    else if (['亥', '卯', '未'].includes(zhi)) shaDir = '西';
    
    // 宜與忌的雜湊生成 (以日期做 seed)
    const yiList = ['祭祀', '祈福', '求嗣', '出行', '解除', '伐木', '出火', '拆卸', '修造', '動土', '造廟', '安床', '納畜', '入殮', '破土', '啟鑽', '安葬', '立碑', '交易', '立券', '納財', '開市', '栽種', '會親友', '入宅', '移徙', '理髮', '沐浴'];
    const jiList = ['嫁娶', '出行', '移徙', '入宅', '開市', '動土', '破土', '安葬', '作灶', '安門', '上樑', '蓋屋', '詞訟', '針灸', '掘井', '作樑', '伐木', '分居', '分立', '置產'];
    
    let dateStr = `${year}${month}${day}`;
    let seed = 0;
    for (let i = 0; i < dateStr.length; i++) {
        seed += dateStr.charCodeAt(i) * (i + 1);
    }
    
    function getSeedRand(s) {
        let x = Math.sin(s) * 10000;
        return x - Math.floor(x);
    }
    
    let yi = [];
    let ji = [];
    let tempSeed = seed;
    
    while (yi.length < 4) {
        let r = getSeedRand(tempSeed++);
        let item = yiList[Math.floor(r * yiList.length)];
        if (!yi.includes(item)) yi.push(item);
    }
    
    while (ji.length < 4) {
        let r = getSeedRand(tempSeed++);
        let item = jiList[Math.floor(r * jiList.length)];
        if (!ji.includes(item) && !yi.includes(item)) ji.push(item);
    }
    
    return {
        lunarMonth: lunarMonthStr,
        lunarDay: lunarDayStr,
        lunarDayRaw: lunarD,
        chineseYear: `${yearGan}${yearZhi}`,
        chineseMonth: `${monthGan}${monthZhi}`,
        chineseDay: `${dayGan}${dayZhi}`,
        zodiac: yearZodiac,
        clashZodiac: clashZodiac,
        shaDir: shaDir,
        suitable: yi.join('、'),
        unsuitable: ji.join('、')
    };
}

// 渲染大農民曆詳情看板
function updateFarmerPanel(year, month, day) {
    const info = getFarmerCalendarInfo(year, month, day);
    
    document.getElementById('f-solar-date').innerText = `西元 ${year} 年 ${month + 1} 月 ${day} 日`;
    document.getElementById('f-lunar-date').innerText = `農曆 ${info.lunarMonth}${info.lunarDay}`;
    document.getElementById('f-chinese-date').innerText = `歲次 ${info.chineseYear}年 ${info.chineseMonth}月 ${info.chineseDay}日`;
    document.getElementById('f-zodiac-clash').innerText = `生肖屬${info.zodiac} ｜ 煞${info.shaDir}沖${info.clashZodiac}`;
    document.getElementById('f-suitable').innerText = info.suitable;
    document.getElementById('f-unsuitable').innerText = info.unsuitable;
}

// 動態渲染大月曆 Grid
function generateLargeCalendar(year, month) {
    const container = document.getElementById('l-calendar-days-container');
    container.innerHTML = '';
    
    const weekDays = ['日', '一', '二', '三', '四', '五', '六'];
    weekDays.forEach(day => {
        const head = document.createElement('div');
        head.className = 'calendar-day-head';
        head.innerText = day;
        head.style.fontWeight = 'bold';
        head.style.color = 'var(--primary-color)';
        head.style.padding = '8px 0';
        head.style.borderBottom = '2px solid var(--border-color)';
        container.appendChild(head);
    });
    
    document.getElementById('l-calendar-month-year').innerText = `${year}年 ${month + 1}月`;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.style.border = '1px solid transparent';
        container.appendChild(emptyCell);
    }
    
    const today = new Date();
    
    for (let day = 1; day <= daysInMonth; day++) {
        const info = getFarmerCalendarInfo(year, month, day);
        
        const dayCell = document.createElement('div');
        dayCell.style.border = '1px solid var(--border-color)';
        dayCell.style.borderRadius = 'var(--radius-sm)';
        dayCell.style.padding = '8px';
        dayCell.style.minHeight = '65px';
        dayCell.style.display = 'flex';
        dayCell.style.flexDirection = 'column';
        dayCell.style.justifyContent = 'space-between';
        dayCell.style.cursor = 'pointer';
        dayCell.style.transition = 'var(--transition)';
        dayCell.style.backgroundColor = '#ffffff';
        
        const numDiv = document.createElement('div');
        numDiv.innerText = day;
        numDiv.style.fontWeight = '700';
        numDiv.style.fontSize = '1.05rem';
        numDiv.style.color = 'var(--text-main)';
        
        const lunarDiv = document.createElement('div');
        lunarDiv.innerText = info.lunarDayRaw === 1 ? info.lunarMonth : info.lunarDay;
        lunarDiv.style.fontSize = '0.72rem';
        lunarDiv.style.color = 'var(--text-muted)';
        lunarDiv.style.textAlign = 'right';
        
        if (info.lunarDayRaw === 1) {
            lunarDiv.style.color = '#c0392b';
            lunarDiv.style.fontWeight = '700';
        }
        
        dayCell.appendChild(numDiv);
        dayCell.appendChild(lunarDiv);
        
        if (year === lSelectedYear && month === lSelectedMonth && day === lSelectedDay) {
            dayCell.style.backgroundColor = 'rgba(50, 140, 193, 0.1)';
            dayCell.style.borderColor = 'var(--primary-light)';
            dayCell.style.boxShadow = 'var(--shadow-sm)';
        }
        
        if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
            numDiv.style.color = 'var(--primary-light)';
            dayCell.style.border = '2px solid var(--primary-light)';
        }
        
        dayCell.onmouseenter = function() {
            dayCell.style.transform = 'translateY(-2px)';
            dayCell.style.boxShadow = 'var(--shadow-sm)';
            dayCell.style.borderColor = 'var(--primary-light)';
        };
        dayCell.onmouseleave = function() {
            dayCell.style.transform = 'none';
            if (!(year === lSelectedYear && month === lSelectedMonth && day === lSelectedDay)) {
                dayCell.style.borderColor = 'var(--border-color)';
                dayCell.style.boxShadow = 'none';
            }
        };
        
        dayCell.onclick = function() {
            lSelectedYear = year;
            lSelectedMonth = month;
            lSelectedDay = day;
            generateLargeCalendar(year, month);
            updateFarmerPanel(year, month, day);
        };
        
        container.appendChild(dayCell);
    }
}

// 大萬年曆按鈕事件
document.getElementById('l-prev-month').onclick = function() {
    lCurrentMonth--;
    if (lCurrentMonth < 0) {
        lCurrentMonth = 11;
        lCurrentYear--;
    }
    generateLargeCalendar(lCurrentYear, lCurrentMonth);
};

document.getElementById('l-next-month').onclick = function() {
    lCurrentMonth++;
    if (lCurrentMonth > 11) {
        lCurrentMonth = 0;
        lCurrentYear++;
    }
    generateLargeCalendar(lCurrentYear, lCurrentMonth);
};

document.getElementById('l-today-btn').onclick = function() {
    lCurrentYear = new Date().getFullYear();
    lCurrentMonth = new Date().getMonth();
    lSelectedYear = lCurrentYear;
    lSelectedMonth = lCurrentMonth;
    lSelectedDay = new Date().getDate();
    generateLargeCalendar(lCurrentYear, lCurrentMonth);
    updateFarmerPanel(lSelectedYear, lSelectedMonth, lSelectedDay);
};

// 頁面初始化
window.onload = function() {
    renderEmployeeTable(employeeData);
    generateCalendar(currentYear, currentMonth);
    updateFarmerPanel(lSelectedYear, lSelectedMonth, lSelectedDay);
};
</script>

<?php
require_once 'includes/footer.php';
?>

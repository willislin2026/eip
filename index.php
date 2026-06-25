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
            <a href="#news" class="sidebar-nav-item"><i class="fa-regular fa-newspaper"></i> 最新消息</a>
            <a href="#news" class="sidebar-nav-item"><i class="fa-solid fa-book-open"></i> 電子月刊</a>
            <a href="#calendar" class="sidebar-nav-item"><i class="fa-regular fa-calendar-days"></i> 萬年曆</a>
            <a href="#directory" class="sidebar-nav-item"><i class="fa-solid fa-address-book"></i> 分機/Email 查詢</a>
            <a href="https://mail.google.com" target="_blank" class="sidebar-nav-item"><i class="fa-solid fa-envelope"></i> 外部 Web-Mail</a>
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
                    <a href="https://mail.google.com" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 6px 4px;"><i class="fa-solid fa-envelope"></i> Mail</a>
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

// 3. 萬年曆生成邏輯
let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth(); // 0-11

function generateCalendar(year, month) {
    const container = document.getElementById('calendar-days-container');
    container.innerHTML = '';
    
    // 星期頭
    const weekDays = ['日', '一', '二', '三', '四', '五', '六'];
    weekDays.forEach(day => {
        const head = document.createElement('div');
        head.className = 'calendar-day-head';
        head.innerText = day;
        container.appendChild(head);
    });
    
    // 設定月份與年份文字
    const months = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'];
    document.getElementById('calendar-month-year').innerText = `${year}年 ${months[month]}`;
    
    // 該月第一天
    const firstDay = new Date(year, month, 1).getDay();
    // 該月天數
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // 填入空格
    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'calendar-day';
        emptyCell.style.visibility = 'hidden';
        container.appendChild(emptyCell);
    }
    
    const today = new Date();
    
    // 填入日期
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'calendar-day';
        dayCell.innerText = day;
        
        // 標記今天
        if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
            dayCell.classList.add('today');
        }
        
        dayCell.onclick = function() {
            alert(`您選取了 ${year} 年 ${month + 1} 月 ${day} 日。這是一個展示功能，您可以結合個人行事曆。`);
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

// 頁面初始化
window.onload = function() {
    renderEmployeeTable(employeeData);
    generateCalendar(currentYear, currentMonth);
};
</script>

<?php
require_once 'includes/footer.php';
?>

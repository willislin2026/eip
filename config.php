<?php
/**
 * EIP 系統設定檔 & 資料庫連線配置
 * 具備自動 Fallback 機制：若 MySQL 連接失敗，將自動啟用 Session-based 模擬資料庫，方便在無資料庫環境下測試完整功能。
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// MySQL 資料庫連線參數設定
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eip_db');

// 動態計算專案根目錄 URL 路徑，解決子資料夾路徑問題
$dir = str_replace('\\', '/', __DIR__);
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$base_url = '';
if (strpos($dir, $doc_root) === 0) {
    $base_url = substr($dir, strlen($doc_root));
}
$base_url = '/' . ltrim($base_url, '/') . '/';
$base_url = preg_replace('/\/+/', '/', $base_url);
define('BASE_URL', $base_url);

$use_mysql = false;
$pdo = null;
$db_error_message = "";

try {
    // 嘗試建立 MySQL 連線
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 2 // 逾時設定，防止在無 DB 時卡住太久
    ]);
    
    // 檢查並建立資料庫
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // 標記成功使用 MySQL
    $use_mysql = true;
    
    // 建立必要的資料表 (如果不存在的話)
    init_mysql_tables($pdo);
    
} catch (Exception $e) {
    $db_error_message = $e->getMessage();
    $use_mysql = false; // Fallback 至 Session 模擬資料庫
}

// 將資料庫連線狀態存於 Session，方便頁面提示
$_SESSION['use_mysql'] = $use_mysql;
$_SESSION['db_error'] = $db_error_message;

// 初始化模擬資料庫 (Session)
if (!$use_mysql) {
    init_session_mock_data();
}

/**
 * 初始化 MySQL 資料表與預設資料
 */
function init_mysql_tables($pdo) {
    // 1. 員工資料表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `employees` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `realname` VARCHAR(50) NOT NULL,
        `department` VARCHAR(50) NOT NULL,
        `ext_no` VARCHAR(20) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `approval_level` VARCHAR(50) NOT NULL DEFAULT 'Employee'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. 最新消息與電子月刊表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `bulletin` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT NOT NULL,
        `type` VARCHAR(20) NOT NULL,
        `publish_date` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. 台北訂餐紀錄表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL,
        `order_date` DATE NOT NULL,
        `meal_option` VARCHAR(100) NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT '已點餐'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. 会議室預約表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `room_reservations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `room_name` VARCHAR(50) NOT NULL,
        `username` VARCHAR(50) NOT NULL,
        `reserve_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `purpose` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. 員工意見箱
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedbacks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 檢查是否有預設帳號，沒有則新增 (預設帳號: admin, user1, user2, user3, user4)
    $stmt = $pdo->query("SELECT COUNT(*) FROM `employees`");
    if ($stmt->fetchColumn() == 0) {
        $users = [
            ['admin', password_hash('admin', PASSWORD_DEFAULT), '系統管理員', '資訊部', '8801', 'admin@company.com', 'Director'],
            ['user1', password_hash('user1', PASSWORD_DEFAULT), '王小明', '總務部', '8201', 'xiaoming@company.com', 'Employee'],
            ['user2', password_hash('user2', PASSWORD_DEFAULT), '李四', '人力資源部', '8301', 'lisi@company.com', 'Manager'],
            ['user3', password_hash('user3', PASSWORD_DEFAULT), '陳大同', '財務部', '8401', 'datong@company.com', 'Employee'],
            ['user4', password_hash('user4', PASSWORD_DEFAULT), '林美玲', '法務部', '8501', 'meiling@company.com', 'Manager']
        ];
        $insert_stmt = $pdo->prepare("INSERT INTO `employees` (username, password, realname, department, ext_no, email, approval_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($users as $u) {
            $insert_stmt->execute($u);
        }
    }

    // 檢查並寫入預設公告
    $stmt = $pdo->query("SELECT COUNT(*) FROM `bulletin`");
    if ($stmt->fetchColumn() == 0) {
        $news = [
            ['115年7月份員工慶生會通知', '本月份員工慶生會將於7/15下午3點在行政大樓一樓大會議室舉行，備有精緻茶點，歡迎各位同仁踴躍参加。', 'news', date('Y-m-d')],
            ['EIP 系統全新 AI 智慧功能上線', '全新 AI 智慧助理已成功整合至 EIP，同仁可以利用 AI 進行公文摘要與工作排程管理。', 'news', date('Y-m-d', strtotime('-2 days'))],
            ['EIP 企業季刊 - 2026年第二季號', '本季季刊主題為「綠色照明與智慧辦公室」，詳細電子書內容請點此下載閱讀。', 'monthly', date('Y-m-d', strtotime('-5 days'))]
        ];
        $insert_stmt = $pdo->prepare("INSERT INTO `bulletin` (title, content, type, publish_date) VALUES (?, ?, ?, ?)");
        foreach ($news as $n) {
            $insert_stmt->execute($n);
        }
    }

    // 檢查並寫入預設訂餐紀錄
    $stmt = $pdo->query("SELECT COUNT(*) FROM `orders`");
    if ($stmt->fetchColumn() == 0) {
        $orders_mock = [
            ['user1', date('Y-m-d'), '招牌雞腿便當', '已點餐'],
            ['user2', date('Y-m-d'), '炭烤排骨便當', '已點餐'],
            ['user3', date('Y-m-d'), '招牌雞腿便當', '已點餐'],
            ['user4', date('Y-m-d'), '健康蔬食餐盒', '已點餐'],
            ['admin', date('Y-m-d'), '招牌雞腿便當', '已點餐'],
            ['user1', date('Y-m-d', strtotime('-1 days')), '炭烤排骨便當', '已點餐'],
            ['user2', date('Y-m-d', strtotime('-1 days')), '招牌雞腿便當', '已點餐'],
            ['user3', date('Y-m-d', strtotime('-1 days')), '健康蔬食餐盒', '已點餐'],
            ['admin', date('Y-m-d', strtotime('-1 days')), '紅燒牛肉麵', '已點餐'],
            ['user1', date('Y-m-d', strtotime('-2 days')), '紅燒牛肉麵', '已點餐'],
            ['user2', date('Y-m-d', strtotime('-2 days')), '炭烤排骨便當', '已點餐'],
            ['user3', date('Y-m-d', strtotime('-2 days')), '招牌雞腿便當', '已點餐'],
            ['user4', date('Y-m-d', strtotime('-2 days')), '招牌雞腿便當', '已點餐'],
            ['admin', date('Y-m-d', strtotime('-2 days')), '招牌雞腿便當', '已點餐']
        ];
        $insert_stmt = $pdo->prepare("INSERT INTO `orders` (username, order_date, meal_option, status) VALUES (?, ?, ?, ?)");
        foreach ($orders_mock as $o) {
            $insert_stmt->execute($o);
        }
    }

    // 檢查並寫入預設會議室預約紀錄
    $stmt = $pdo->query("SELECT COUNT(*) FROM `room_reservations`");
    if ($stmt->fetchColumn() == 0) {
        $reservations_mock = [
            ['A1 視訊會議室', 'user2', date('Y-m-d'), '14:00:00', '16:00:00', '人資季度考核會議'],
            ['A2 視訊會議室', 'admin', date('Y-m-d'), '10:00:00', '12:00:00', 'EIP系統上線進度檢討']
        ];
        $insert_stmt = $pdo->prepare("INSERT INTO `room_reservations` (room_name, username, reserve_date, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($reservations_mock as $r) {
            $insert_stmt->execute($r);
        }
    } else {
        // 舊會議室名稱自動移轉升級
        $pdo->exec("UPDATE `room_reservations` SET `room_name` = 'A1 視訊會議室' WHERE `room_name` = 'A會議室(大)'");
        $pdo->exec("UPDATE `room_reservations` SET `room_name` = 'A2 視訊會議室' WHERE `room_name` = 'B會議室(中)'");
        $pdo->exec("UPDATE `room_reservations` SET `room_name` = 'A3 會議室' WHERE `room_name` = 'C會議室(小)'");
    }
}

/**
 * 初始化 Session 模擬資料
 */
function init_session_mock_data() {
    // 預設員工帳號
    if (!isset($_SESSION['mock_employees'])) {
        $_SESSION['mock_employees'] = [
            'admin' => ['username' => 'admin', 'password' => 'admin', 'realname' => '系統管理員', 'department' => '資訊部', 'ext_no' => '8801', 'email' => 'admin@company.com', 'approval_level' => 'Director'],
            'user1' => ['username' => 'user1', 'password' => 'user1', 'realname' => '王小明', 'department' => '總務部', 'ext_no' => '8201', 'email' => 'xiaoming@company.com', 'approval_level' => 'Employee'],
            'user2' => ['username' => 'user2', 'password' => 'user2', 'realname' => '李四', 'department' => '人力資源部', 'ext_no' => '8301', 'email' => 'lisi@company.com', 'approval_level' => 'Manager'],
            'user3' => ['username' => 'user3', 'password' => 'user3', 'realname' => '陳大同', 'department' => '財務部', 'ext_no' => '8401', 'email' => 'datong@company.com', 'approval_level' => 'Employee'],
            'user4' => ['username' => 'user4', 'password' => 'user4', 'realname' => '林美玲', 'department' => '法務部', 'ext_no' => '8501', 'email' => 'meiling@company.com', 'approval_level' => 'Manager']
        ];
    }
    
    // 預設公告
    if (!isset($_SESSION['mock_bulletin'])) {
        $_SESSION['mock_bulletin'] = [
            ['id' => 1, 'title' => '115年7月份員工慶生會通知', 'content' => '本月份員工慶生會將於7/15下午3點在行政大樓一樓大會議室舉行，備有精緻茶點，歡迎各位同仁踴躍参加。', 'type' => 'news', 'publish_date' => date('Y-m-d')],
            ['id' => 2, 'title' => 'EIP 系統全新 AI 智慧功能上線', 'content' => '全新 AI 智慧助理已成功整合至 EIP，同仁可以利用 AI 進行公文摘要與工作排程管理。', 'type' => 'news', 'publish_date' => date('Y-m-d', strtotime('-2 days'))],
            ['id' => 3, 'title' => 'EIP 企業季刊 - 2026年第二季號', 'content' => '本季季刊主題為「綠色照明與智慧辦公室」，詳細電子書內容請點此下載閱讀。', 'type' => 'monthly', 'publish_date' => date('Y-m-d', strtotime('-5 days'))]
        ];
    }
    
    // 預設訂餐紀錄
    if (!isset($_SESSION['mock_orders'])) {
        $_SESSION['mock_orders'] = [
            ['id' => 1, 'username' => 'user1', 'order_date' => date('Y-m-d'), 'meal_option' => '招牌雞腿便當', 'status' => '已點餐'],
            ['id' => 2, 'username' => 'user2', 'order_date' => date('Y-m-d'), 'meal_option' => '炭烤排骨便當', 'status' => '已點餐'],
            ['id' => 3, 'username' => 'user3', 'order_date' => date('Y-m-d'), 'meal_option' => '招牌雞腿便當', 'status' => '已點餐'],
            ['id' => 4, 'username' => 'user4', 'order_date' => date('Y-m-d'), 'meal_option' => '健康蔬食餐盒', 'status' => '已點餐'],
            ['id' => 5, 'username' => 'admin', 'order_date' => date('Y-m-d'), 'meal_option' => '招牌雞腿便當', 'status' => '已點餐'],
            ['id' => 6, 'username' => 'user1', 'order_date' => date('Y-m-d', strtotime('-1 days')), 'meal_option' => '炭烤排骨便當', 'status' => '已點餐'],
            ['id' => 7, 'username' => 'user2', 'order_date' => date('Y-m-d', strtotime('-1 days')), 'meal_option' => '招牌雞腿便當', 'status' => '已點餐'],
            ['id' => 8, 'username' => 'user3', 'order_date' => date('Y-m-d', strtotime('-1 days')), 'meal_option' => '健康蔬食餐盒', 'status' => '已點餐'],
            ['id' => 9, 'username' => 'admin', 'order_date' => date('Y-m-d', strtotime('-1 days')), 'meal_option' => '紅燒牛肉麵', 'status' => '已點餐'],
            ['id' => 10, 'username' => 'user1', 'order_date' => date('Y-m-d', strtotime('-2 days')), 'meal_option' => '紅燒牛肉麵', 'status' => '已點餐'],
            ['id' => 11, 'username' => 'user2', 'order_date' => date('Y-m-d', strtotime('-2 days')), 'meal_option' => '炭烤排骨便當', 'status' => '已點餐'],
            ['id' => 12, 'username' => 'user3', 'order_date' => date('Y-m-d', strtotime('-2 days')), 'meal_option' => '招牌雞腿便當', 'status' => '已點餐'],
            ['id' => 13, 'username' => 'user4', 'order_date' => date('Y-m-d', strtotime('-2 days')), 'meal_option' => '招牌雞腿便當', 'status' => '已點餐'],
            ['id' => 14, 'username' => 'admin', 'order_date' => date('Y-m-d', strtotime('-2 days')), 'meal_option' => '招牌雞腿便當', 'status' => '已點餐']
        ];
    }
    
    // 預設預約會議室紀錄
    if (!isset($_SESSION['mock_reservations'])) {
        $_SESSION['mock_reservations'] = [
            ['id' => 1, 'room_name' => 'A1 視訊會議室', 'username' => 'user2', 'reserve_date' => date('Y-m-d'), 'start_time' => '14:00:00', 'end_time' => '16:00:00', 'purpose' => '人資季度考核會議'],
            ['id' => 2, 'room_name' => 'A2 視訊會議室', 'username' => 'admin', 'reserve_date' => date('Y-m-d'), 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'purpose' => 'EIP系統上線進度檢討']
        ];
    } else {
        // 如果已經有 Session 預約紀錄，也順便移轉更新
        foreach ($_SESSION['mock_reservations'] as &$res) {
            if ($res['room_name'] === 'A會議室(大)') {
                $res['room_name'] = 'A1 視訊會議室';
            } elseif ($res['room_name'] === 'B會議室(中)') {
                $res['room_name'] = 'A2 視訊會議室';
            } elseif ($res['room_name'] === 'C會議室(小)') {
                $res['room_name'] = 'A3 會議室';
            }
            // 同步將 Session 內時間格式補齊秒數
            if (strlen($res['start_time']) === 5) {
                $res['start_time'] .= ':00';
            }
            if (strlen($res['end_time']) === 5) {
                $res['end_time'] .= ':00';
            }
        }
        unset($res);
    }
    
    // 預設員工意見箱
    if (!isset($_SESSION['mock_feedbacks'])) {
        $_SESSION['mock_feedbacks'] = [];
    }
}

/**
 * 驗證登入
 */
function login_user($username, $password) {
    global $use_mysql, $pdo;
    
    if ($use_mysql) {
        $stmt = $pdo->prepare("SELECT * FROM `employees` WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
    } else {
        if (isset($_SESSION['mock_employees'][$username])) {
            $user = $_SESSION['mock_employees'][$username];
            // 這裡模擬資料庫以 plaintext 比對
            if ($user['password'] === $password) {
                return $user;
            }
        }
    }
    return false;
}
?>

<?php
require_once __DIR__ . '/../config.php';

// 權限檢查：未登入則跳轉至登入頁
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user']) && $current_page !== 'login.php') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EIP 企業入口網站 & AI 協作平台</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome 圖標 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 全域樣式 (加上版本號以強制清除瀏覽器快取) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
</head>
<body>
<div class="app-container">
    <!-- 頂部導覽列 -->
    <header class="navbar">
        <a href="<?php echo BASE_URL; ?>index.php" class="navbar-brand">
            <i class="fa-solid fa-square-poll-vertical"></i> 英群企業網站 <span>Beta</span>
        </a>
        
        <?php if ($user): ?>
        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>index.php" class="nav-link">
                        <i class="fa-solid fa-house"></i> 首頁
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>employee/index.php" class="nav-link">
                        <i class="fa-solid fa-user-tie"></i> 員工專區
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="nav-link">
                        <i class="fa-solid fa-paste"></i> 行政專區
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="alert('連結至外部 ERP 產銷資訊系統...')">
                        <i class="fa-solid fa-database"></i> ERP 資訊
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>contract/index.php" class="nav-link">
                        <i class="fa-solid fa-brain"></i> 知識管理
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="user-panel">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['realname']); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($user['department']); ?> / <?php echo htmlspecialchars($user['approval_level']); ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>logout.php" class="btn-logout">登出 <i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
        <?php endif; ?>
    </header>
    
    <!-- 主內容區 -->
    <main class="main-content">
        
        <!-- MySQL 連線狀態小提示 -->
        <?php if ($user && !$_SESSION['use_mysql']): ?>
        <div style="background-color: #fff9db; border: 1px solid #ffe066; color: #f59f00; padding: 8px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>提示：</strong> 目前未連線至 MySQL 資料庫，系統已啟用 <strong>Session 模擬資料庫模式</strong>（操作將正常運作並保存於當前瀏覽器 Session）。
            </div>
            <span style="font-size: 0.75rem; color: #ae8600; cursor: pointer;" title="<?php echo htmlspecialchars($_SESSION['db_error']); ?>">連線錯誤細節 <i class="fa-solid fa-circle-info"></i></span>
        </div>
        <?php endif; ?>

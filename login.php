<?php
require_once 'config.php';

// 若已登入，直接跳轉至首頁
if (isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = '請輸入帳號與密碼';
    } else {
        $user = login_user($username, $password);
        if ($user) {
            $_SESSION['user'] = $user;
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        } else {
            $error = '帳號或密碼錯誤';
        }
    }
}

// 支援測試帳號一鍵登入功能
if (isset($_GET['quick_login'])) {
    $quick = $_GET['quick_login'];
    $allowed = ['admin', 'user1', 'user2'];
    if (in_array($quick, $allowed)) {
        $user = login_user($quick, $quick);
        if ($user) {
            $_SESSION['user'] = $user;
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EIP 系統登入</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-logo">
        <h1><i class="fa-solid fa-square-poll-vertical" style="color: #0b3c5d;"></i> EIP 系統入口</h1>
        <p>請輸入您的企業帳號進行登入</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username" class="form-label">員工帳號 (Username)</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="請輸入帳號" required autofocus>
        </div>
        
        <div class="form-group">
            <label for="password" class="form-label">密碼 (Password)</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="請輸入密碼" required>
        </div>
        
        <button type="submit" class="btn" style="width: 100%; margin-top: 10px; padding: 0.9rem;">
            登入系統 <i class="fa-solid fa-right-to-bracket"></i>
        </button>
    </form>
    
    <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem; text-align: center;">
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.8rem;">
            <i class="fa-solid fa-vial"></i> <strong>快速測試登入</strong>
        </p>
        <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
            <a href="login.php?quick_login=admin" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">資訊管理員</a>
            <a href="login.php?quick_login=user1" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">總務王小明</a>
            <a href="login.php?quick_login=user2" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">人資李四</a>
        </div>
    </div>
</div>

</body>
</html>

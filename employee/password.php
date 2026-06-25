<?php
require_once '../includes/header.php';

$pw_status = '';
$pw_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pw = $_POST['old_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];
    
    if (empty($old_pw) || empty($new_pw) || empty($confirm_pw)) {
        $pw_error = '請完整填寫所有欄位';
    } elseif ($new_pw !== $confirm_pw) {
        $pw_error = '新密碼與確認密碼不符合';
    } elseif (strlen($new_pw) < 4) {
        $pw_error = '新密碼長度至少需 4 個字元';
    } else {
        if ($_SESSION['use_mysql']) {
            try {
                // 取得資料庫中該員工之密碼
                $stmt = $pdo->prepare("SELECT password FROM `employees` WHERE username = ?");
                $stmt->execute([$user['username']]);
                $db_user = $stmt->fetch();
                
                if ($db_user && password_verify($old_pw, $db_user['password'])) {
                    // 更新密碼
                    $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE `employees` SET password = ? WHERE username = ?");
                    $update_stmt->execute([$new_hash, $user['username']]);
                    $pw_status = '密碼變更成功！請牢記您的新密碼。';
                } else {
                    $pw_error = '舊密碼輸入錯誤';
                }
            } catch(Exception $e) {
                $pw_error = '資料庫更新錯誤: ' . $e->getMessage();
            }
        } else {
            // 模擬 Session 修改
            $username = $user['username'];
            if ($_SESSION['mock_employees'][$username]['password'] === $old_pw) {
                $_SESSION['mock_employees'][$username]['password'] = $new_pw;
                $pw_status = '密碼變更成功！(Session)';
            } else {
                $pw_error = '舊密碼輸入錯誤';
            }
        }
    }
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-key"></i> 變更登入密碼</h1>
    <p class="page-subtitle">為了保障您的企業資安，建議定期更換密碼，且切勿與外部私人帳密共用。</p>
</div>

<div class="dashboard-grid" style="justify-content: center;">
    
    <!-- 密碼修改卡片 (Col-6) -->
    <div class="card col-6">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-shield-halved"></i> 安全安全性設定</h2>
            <i class="fa-solid fa-lock" style="color: var(--primary-light);"></i>
        </div>
        
        <?php if (!empty($pw_status)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $pw_status; ?></div>
        <?php endif; ?>
        <?php if (!empty($pw_error)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pw_error; ?></div>
        <?php endif; ?>
        
        <form action="password.php" method="POST">
            <div class="form-group">
                <label for="old_password" class="form-label">輸入舊密碼</label>
                <input type="password" id="old_password" name="old_password" class="form-control" placeholder="請輸入舊密碼" required>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="form-label">輸入新密碼</label>
                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="請輸入新密碼" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">確認新密碼</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="請再次輸入新密碼" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">
                <i class="fa-solid fa-key"></i> 儲存變更密碼
            </button>
        </form>
    </div>
    
</div>

<?php
require_once '../includes/footer.php';
?>

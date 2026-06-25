<?php
require_once '../includes/header.php';

$error = '';
$success = '';

// 檢查是否有管理權限 (只有 Director 級別能進行編輯操作，Employee / Manager 僅限唯讀檢視)
$is_admin = (isset($user['approval_level']) && $user['approval_level'] === 'Director');

// 處理新增員工帳號
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if (!$is_admin) {
        $error = '您的簽核層級不足，無法執行新增操作！';
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $realname = trim($_POST['realname']);
        $department = trim($_POST['department']);
        $ext_no = trim($_POST['ext_no']);
        $email = trim($_POST['email']);
        $approval_level = trim($_POST['approval_level']);

        if (empty($username) || empty($password) || empty($realname) || empty($department) || empty($ext_no) || empty($email)) {
            $error = '所有欄位皆為必填！';
        } else {
            if ($_SESSION['use_mysql']) {
                try {
                    // 檢查帳號是否重複
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM `employees` WHERE username = ?");
                    $check_stmt->execute([$username]);
                    if ($check_stmt->fetchColumn() > 0) {
                        $error = '此帳號已存在！';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO `employees` (username, password, realname, department, ext_no, email, approval_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $username,
                            password_hash($password, PASSWORD_DEFAULT),
                            $realname,
                            $department,
                            $ext_no,
                            $email,
                            $approval_level
                        ]);
                        $success = '員工帳號 「' . htmlspecialchars($realname) . '」 新增成功！';
                    }
                } catch (Exception $e) {
                    $error = '資料庫錯誤：' . $e->getMessage();
                }
            } else {
                // Session 模擬
                if (isset($_SESSION['mock_employees'][$username])) {
                    $error = '此帳號已存在！';
                } else {
                    $_SESSION['mock_employees'][$username] = [
                        'username' => $username,
                        'password' => $password, // 模擬資料庫為明文
                        'realname' => $realname,
                        'department' => $department,
                        'ext_no' => $ext_no,
                        'email' => $email,
                        'approval_level' => $approval_level
                    ];
                    $success = '員工帳號 「' . htmlspecialchars($realname) . '」 新增成功！(Session 模擬模式)';
                }
            }
        }
    }
}

// 處理刪除員工帳號
if (isset($_GET['delete'])) {
    if (!$is_admin) {
        $error = '您的簽核層級不足，無法執行刪除操作！';
    } else {
        $del_username = $_GET['delete'];
        if ($del_username === $user['username']) {
            $error = '無法刪除目前登入中的帳號！';
        } else {
            if ($_SESSION['use_mysql']) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM `employees` WHERE username = ?");
                    $stmt->execute([$del_username]);
                    $success = '帳號 「' . htmlspecialchars($del_username) . '」 刪除成功！';
                } catch (Exception $e) {
                    $error = '資料庫錯誤：' . $e->getMessage();
                }
            } else {
                // Session 模擬
                if (isset($_SESSION['mock_employees'][$del_username])) {
                    unset($_SESSION['mock_employees'][$del_username]);
                    $success = '帳號 「' . htmlspecialchars($del_username) . '」 刪除成功！(Session 模擬模式)';
                } else {
                    $error = '找不到該帳號！';
                }
            }
        }
    }
}

// 載入所有員工資料
$employee_list = [];
if ($_SESSION['use_mysql']) {
    try {
        $stmt = $pdo->query("SELECT * FROM `employees` ORDER BY department ASC, id ASC");
        $employee_list = $stmt->fetchAll();
    } catch (Exception $e) {
        $employee_list = [];
    }
} else {
    // Session 模擬
    foreach ($_SESSION['mock_employees'] as $emp) {
        $employee_list[] = $emp;
    }
    // 依部門排序
    usort($employee_list, function($a, $b) {
        return strcmp($a['department'], $b['department']);
    });
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-users-gear"></i> 員工帳號維護</h1>
    <p class="page-subtitle">本頁面提供系統管理員檢視、新增或註銷英群同仁之系統帳號、分機、電子信箱及簽核職級。</p>
</div>

<!-- 權限提示 -->
<?php if (!$is_admin): ?>
    <div style="background-color: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; padding: 12px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-info" style="font-size: 1.2rem;"></i>
        <div>
            <strong>唯讀瀏覽提示：</strong> 目前您的權限為 <strong><?php echo htmlspecialchars($user['approval_level']); ?></strong>，本帳號維護系統之「新增」與「刪除」功能僅限 <strong>Director (系統管理員)</strong> 操作。
        </div>
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- 1. 新增員工表單 (Col-4) -->
    <div class="card col-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-user-plus"></i> 新增員工帳號</h2>
            <span class="room-status-badge available"><?php echo $is_admin ? '編輯模式' : '唯讀'; ?></span>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form action="users.php" method="POST">
            <input type="hidden" name="action" value="add_user">
            
            <div class="form-group">
                <label class="form-label">登入帳號</label>
                <input type="text" name="username" class="form-control" placeholder="英文或數字，如 wangs" <?php echo !$is_admin ? 'disabled' : ''; ?> required>
            </div>
            
            <div class="form-group">
                <label class="form-label">登入密碼</label>
                <input type="password" name="password" class="form-control" placeholder="預設密碼" <?php echo !$is_admin ? 'disabled' : ''; ?> required>
            </div>
            
            <div class="form-group">
                <label class="form-label">真實姓名</label>
                <input type="text" name="realname" class="form-control" placeholder="中文姓名，如 王大同" <?php echo !$is_admin ? 'disabled' : ''; ?> required>
            </div>
            
            <div class="form-group">
                <label class="form-label">所屬部門</label>
                <select name="department" class="form-control" <?php echo !$is_admin ? 'disabled' : ''; ?> required>
                    <option value="資訊部">資訊部</option>
                    <option value="總務部">總務部</option>
                    <option value="人力資源部">人力資源部</option>
                    <option value="財務部">財務部</option>
                    <option value="法務部">法務部</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">分機號碼</label>
                <input type="text" name="ext_no" class="form-control" placeholder="四碼分機，如 8802" <?php echo !$is_admin ? 'disabled' : ''; ?> required>
            </div>
            
            <div class="form-group">
                <label class="form-label">電子信箱</label>
                <input type="email" name="email" class="form-control" placeholder="company@company.com" <?php echo !$is_admin ? 'disabled' : ''; ?> required>
            </div>
            
            <div class="form-group">
                <label class="form-label">簽核層級 (權限級別)</label>
                <select name="approval_level" class="form-control" <?php echo !$is_admin ? 'disabled' : ''; ?> required>
                    <option value="Employee">Employee (一般員工)</option>
                    <option value="Manager">Manager (部門主管)</option>
                    <option value="Director">Director (系統管理員/處長)</option>
                </select>
            </div>
            
            <?php if ($is_admin): ?>
                <button type="submit" class="btn" style="width: 100%;"><i class="fa-solid fa-circle-check"></i> 確認建立帳號</button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" style="width: 100%; cursor: not-allowed;" disabled><i class="fa-solid fa-ban"></i> 權限不足無法新增</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- 2. 員工列表展示 (Col-8) -->
    <div class="card col-8">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-users"></i> 英群公司同仁帳號清單</h2>
            <span class="room-status-badge available" style="background-color: var(--primary-color); color: white;">
                共計 <?php echo count($employee_list); ?> 員
            </span>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); background-color: #f8fafc;">
                        <th style="padding: 10px; font-weight: 600; color: var(--primary-color);">帳號</th>
                        <th style="padding: 10px; font-weight: 600; color: var(--primary-color);">姓名</th>
                        <th style="padding: 10px; font-weight: 600; color: var(--primary-color);">部門</th>
                        <th style="padding: 10px; font-weight: 600; color: var(--primary-color);">分機</th>
                        <th style="padding: 10px; font-weight: 600; color: var(--primary-color);">Email</th>
                        <th style="padding: 10px; font-weight: 600; color: var(--primary-color);">簽核層級</th>
                        <?php if ($is_admin): ?>
                            <th style="padding: 10px; font-weight: 600; color: var(--primary-color); text-align: center;">操作</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employee_list as $emp): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0; transition: var(--transition);">
                            <td style="padding: 10px; font-family: 'Outfit'; font-weight: 600;"><?php echo htmlspecialchars($emp['username']); ?></td>
                            <td style="padding: 10px; font-weight: bold;"><?php echo htmlspecialchars($emp['realname']); ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($emp['department']); ?></td>
                            <td style="padding: 10px; font-family: 'Outfit';"><?php echo htmlspecialchars($emp['ext_no']); ?></td>
                            <td style="padding: 10px; font-family: 'Outfit';"><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td style="padding: 10px;">
                                <?php
                                $badge_class = 'available';
                                if ($emp['approval_level'] === 'Director') {
                                    $badge_class = 'booked'; // 紅色
                                } elseif ($emp['approval_level'] === 'Manager') {
                                    $badge_class = ''; // 默認或黃色
                                }
                                ?>
                                <span class="room-status-badge <?php echo $badge_class; ?>" style="<?php echo ($emp['approval_level'] === 'Manager') ? 'background-color: #fef3c7; color: #d97706;' : ''; ?>">
                                    <?php echo htmlspecialchars($emp['approval_level']); ?>
                                </span>
                            </td>
                            <?php if ($is_admin): ?>
                                <td style="padding: 10px; text-align: center;">
                                    <?php if ($emp['username'] === $user['username']): ?>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);">使用中</span>
                                    <?php else: ?>
                                        <a href="users.php?delete=<?php echo urlencode($emp['username']); ?>" 
                                           onclick="return confirm('確定要刪除員工 「<?php echo htmlspecialchars($emp['realname']); ?>」 的系統帳號嗎？');" 
                                           style="color: var(--danger); font-size: 0.8rem; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                            <i class="fa-solid fa-trash-can"></i> 註銷
                                        </a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
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

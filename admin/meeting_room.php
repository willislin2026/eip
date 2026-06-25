<?php
require_once '../includes/header.php';

$res_status = '';
$res_error = '';

// 會議室清單
$rooms = ['A會議室(大)', 'B會議室(中)', 'C會議室(小)'];

// 處理預約送出
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve_room') {
    $selected_room = trim($_POST['room_name']);
    $reserve_date = trim($_POST['reserve_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $purpose = trim($_POST['purpose']);
    
    if (empty($selected_room) || empty($reserve_date) || empty($start_time) || empty($end_time) || empty($purpose)) {
        $res_error = '所有欄位皆為必填';
    } elseif ($start_time >= $end_time) {
        $res_error = '開始時間必須早於結束時間';
    } else {
        // 時段重疊檢查邏輯
        $has_conflict = false;
        
        if ($_SESSION['use_mysql']) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `room_reservations` 
                    WHERE room_name = ? AND reserve_date = ? 
                    AND NOT (end_time <= ? OR start_time >= ?)");
                $stmt->execute([$selected_room, $reserve_date, $start_time, $end_time]);
                if ($stmt->fetchColumn() > 0) {
                    $has_conflict = true;
                }
            } catch(Exception $e) {
                $res_error = '資料庫查詢失敗: ' . $e->getMessage();
            }
        } else {
            // 從 mock 中檢查衝突
            foreach ($_SESSION['mock_reservations'] as $res) {
                if ($res['room_name'] === $selected_room && $res['reserve_date'] === $reserve_date) {
                    // 重疊判定: !(res.end <= start || res.start >= end)
                    if (!($res['end_time'] <= $start_time || $res['start_time'] >= $end_time)) {
                        $has_conflict = true;
                        break;
                    }
                }
            }
        }
        
        if ($has_conflict) {
            $res_error = "此會議室在此時段已被預約，請選擇其他時段或會議室！";
        } else {
            // 寫入預約
            if ($_SESSION['use_mysql']) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO `room_reservations` (room_name, username, reserve_date, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$selected_room, $user['username'], $reserve_date, $start_time, $end_time, $purpose]);
                    $res_status = '會議室預約成功！';
                } catch(Exception $e) {
                    $res_error = '預約失敗: ' . $e->getMessage();
                }
            } else {
                $_SESSION['mock_reservations'][] = [
                    'id' => count($_SESSION['mock_reservations']) + 1,
                    'room_name' => $selected_room,
                    'username' => $user['username'],
                    'realname' => $user['realname'],
                    'reserve_date' => $reserve_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'purpose' => $purpose
                ];
                $res_status = '會議室預約成功！(Session)';
            }
        }
    }
}

// 取得所有預約清單
$all_reservations = [];
if ($_SESSION['use_mysql']) {
    try {
        $stmt = $pdo->query("SELECT r.*, e.realname FROM `room_reservations` r JOIN `employees` e ON r.username = e.username ORDER BY r.reserve_date DESC, r.start_time ASC");
        $all_reservations = $stmt->fetchAll();
    } catch(Exception $e) {}
} else {
    $all_reservations = $_SESSION['mock_reservations'];
    // 排序
    usort($all_reservations, function($a, $b) {
        if ($a['reserve_date'] === $b['reserve_date']) {
            return strcmp($a['start_time'], $b['start_time']);
        }
        return strcmp($b['reserve_date'], $a['reserve_date']);
    });
}
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-door-open"></i> 會議室預約系統</h1>
    <p class="page-subtitle">同仁可在此線上提交會議室時段申請。系統會自動檢測是否有衝突時段。</p>
</div>

<div class="dashboard-grid">
    
    <!-- 1. 預約表單 (Col-5) -->
    <div class="card col-5">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-calendar-plus"></i> 新增會議室預約</h2>
            <i class="fa-regular fa-clock" style="color: var(--primary-light);"></i>
        </div>
        
        <?php if (!empty($res_status)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $res_status; ?></div>
        <?php endif; ?>
        <?php if (!empty($res_error)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $res_error; ?></div>
        <?php endif; ?>
        
        <form action="meeting_room.php" method="POST">
            <input type="hidden" name="action" value="reserve_room">
            
            <div class="form-group">
                <label for="room_name" class="form-label">選擇會議室</label>
                <select id="room_name" name="room_name" class="form-control" required>
                    <option value="">-- 請選擇會議室 --</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo htmlspecialchars($room); ?>"><?php echo htmlspecialchars($room); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="reserve_date" class="form-label">選擇預約日期</label>
                <input type="date" id="reserve_date" name="reserve_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="start_time" class="form-label">開始時間</label>
                    <input type="time" id="start_time" name="start_time" class="form-control" step="1800" min="08:00" max="18:00" required>
                </div>
                <div class="form-group">
                    <label for="end_time" class="form-label">結束時間</label>
                    <input type="time" id="end_time" name="end_time" class="form-control" step="1800" min="08:30" max="19:00" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="purpose" class="form-label">會議目的 / 用途</label>
                <input type="text" id="purpose" name="purpose" class="form-control" placeholder="請簡述會議用途，例如: 周會/考核/面試" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">
                <i class="fa-solid fa-check"></i> 確認送出預約
            </button>
        </form>
    </div>
    
    <!-- 2. 目前已預約排程清單 (Col-7) -->
    <div class="card col-7">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-list-check"></i> 目前會議室使用排程 (已預約)</h2>
            <span class="room-status-badge available">全部排程</span>
        </div>
        
        <div style="max-height: 460px; overflow-y: auto;">
            <?php if (empty($all_reservations)): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 30px;">目前尚無任何預約排程</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 10px;">日期</th>
                            <th style="padding: 10px;">會議室</th>
                            <th style="padding: 10px;">預約時間</th>
                            <th style="padding: 10px;">預約人</th>
                            <th style="padding: 10px;">用途</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_reservations as $res): ?>
                            <?php 
                                // 判斷是否為今天
                                $is_today = ($res['reserve_date'] === date('Y-m-d'));
                                $row_bg = $is_today ? 'background-color: #f0fdf4;' : '';
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; <?php echo $row_bg; ?>">
                                <td style="padding: 10px; font-weight: <?php echo $is_today ? 'bold' : 'normal'; ?>;">
                                    <?php echo htmlspecialchars($res['reserve_date']); ?>
                                    <?php if ($is_today) echo ' <span style="font-size: 0.75rem; background-color: var(--success); color: white; padding: 1px 4px; border-radius: 3px;">今天</span>'; ?>
                                </td>
                                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($res['room_name']); ?></strong></td>
                                <td style="padding: 10px; color: var(--primary-light); font-weight: 500;">
                                    <?php echo substr($res['start_time'], 0, 5) . ' - ' . substr($res['end_time'], 0, 5); ?>
                                </td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars(isset($res['realname']) ? $res['realname'] : $res['username']); ?></td>
                                <td style="padding: 10px; font-size: 0.85rem; color: var(--text-main);"><?php echo htmlspecialchars($res['purpose']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php
require_once '../includes/footer.php';
?>

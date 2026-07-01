<?php
require_once '../includes/header.php';

$res_status = '';
$res_error = '';

// 會議室清單
$rooms = ['A1 視訊會議室', 'A2 視訊會議室', 'A3 會議室'];

// 時段清單 (08:00 ~ 19:00，每小時一格)
$time_slots = [
    '08:00' => '08:00-09:00',
    '09:00' => '09:00-10:00',
    '10:00' => '10:00-11:00',
    '11:00' => '11:00-12:00',
    '12:00' => '12:00-13:00',
    '13:00' => '13:00-14:00',
    '14:00' => '14:00-15:00',
    '15:00' => '15:00-16:00',
    '16:00' => '16:00-17:00',
    '17:00' => '17:00-18:00',
    '18:00' => '18:00-19:00',
];

// 週次偏移 (0 = 本週，正負為前後週，月偏移亦支援)
$week_offset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$selected_room = isset($_GET['room']) ? $_GET['room'] : $rooms[0];

// 計算本週起始日 (星期一)
$today = new DateTime();
$week_start = clone $today;
$week_start->modify('Monday this week');
$week_start->modify("{$week_offset} weeks");

// 產生本週 7 天日期
$week_days = [];
for ($i = 0; $i < 7; $i++) {
    $day = clone $week_start;
    $day->modify("+{$i} days");
    $week_days[] = $day;
}

// 處理預約送出 (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve_room') {
    $post_room    = trim($_POST['room_name'] ?? '');
    $reserve_date = trim($_POST['reserve_date'] ?? '');
    $start_time   = trim($_POST['start_time'] ?? '');
    $end_time     = trim($_POST['end_time'] ?? '');
    $purpose      = trim($_POST['purpose'] ?? '');

    // 補齊秒數格式
    if (strlen($start_time) === 5) $start_time .= ':00';
    if (strlen($end_time) === 5)   $end_time   .= ':00';

    $today_str = date('Y-m-d');

    if (empty($post_room) || empty($reserve_date) || empty($start_time) || empty($end_time) || empty($purpose)) {
        $res_error = '所有欄位皆為必填';
    } elseif ($reserve_date < $today_str) {
        $res_error = '預約日期不得為過去的日期';
    } elseif ($start_time >= $end_time) {
        $res_error = '開始時間必須早於結束時間';
    } else {
        $has_conflict = false;
        if ($_SESSION['use_mysql']) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `room_reservations`
                    WHERE room_name = ? AND reserve_date = ?
                    AND NOT (end_time <= ? OR start_time >= ?)");
                $stmt->execute([$post_room, $reserve_date, $start_time, $end_time]);
                if ($stmt->fetchColumn() > 0) $has_conflict = true;
            } catch (Exception $e) {
                $res_error = '資料庫查詢失敗: ' . $e->getMessage();
            }
        } else {
            foreach ($_SESSION['mock_reservations'] as $res) {
                if ($res['room_name'] === $post_room && $res['reserve_date'] === $reserve_date) {
                    if (!($res['end_time'] <= $start_time || $res['start_time'] >= $end_time)) {
                        $has_conflict = true;
                        break;
                    }
                }
            }
        }

        if ($has_conflict) {
            $res_error = '此會議室在此時段已被預約，請選擇其他時段或會議室！';
        } else {
            if ($_SESSION['use_mysql']) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO `room_reservations` (room_name, username, reserve_date, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$post_room, $user['username'], $reserve_date, $start_time, $end_time, $purpose]);
                    $res_status = '✅ 會議室預約成功！';
                } catch (Exception $e) {
                    $res_error = '預約失敗: ' . $e->getMessage();
                }
            } else {
                $_SESSION['mock_reservations'][] = [
                    'id'           => count($_SESSION['mock_reservations']) + 1,
                    'room_name'    => $post_room,
                    'username'     => $user['username'],
                    'realname'     => $user['realname'],
                    'reserve_date' => $reserve_date,
                    'start_time'   => $start_time,
                    'end_time'     => $end_time,
                    'purpose'      => $purpose,
                ];
                $res_status = '✅ 會議室預約成功！(Session)';
            }
        }
    }
}

// 處理取消預約 (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_room') {
    $cancel_id   = intval($_POST['reservation_id'] ?? 0);
    $cancel_room = trim($_POST['room_name'] ?? '');

    if ($cancel_id <= 0) {
        $res_error = '無效的預約 ID';
    } else {
        if ($_SESSION['use_mysql']) {
            try {
                // 只允許取消自己的預約
                $stmt = $pdo->prepare("DELETE FROM `room_reservations` WHERE id = ? AND username = ?");
                $stmt->execute([$cancel_id, $user['username']]);
                if ($stmt->rowCount() > 0) {
                    $res_status = '🗑️ 預約已成功取消！';
                } else {
                    $res_error = '找不到預約紀錄，或您無權取消此預約。';
                }
            } catch (Exception $e) {
                $res_error = '取消失敗: ' . $e->getMessage();
            }
        } else {
            $found = false;
            foreach ($_SESSION['mock_reservations'] as $k => $res) {
                if ($res['id'] === $cancel_id && $res['username'] === $user['username']) {
                    unset($_SESSION['mock_reservations'][$k]);
                    $_SESSION['mock_reservations'] = array_values($_SESSION['mock_reservations']);
                    $found = true;
                    break;
                }
            }
            $res_status = $found ? '🗑️ 預約已成功取消！(Session)' : '找不到預約或您無權取消此預約。';
            if (!$found) $res_error = $res_status;
            if ($found)  $res_error = '';
        }
    }
}

// 讀取本週指定會議室的預約資料
$week_start_str = $week_start->format('Y-m-d');
$week_end_clone = clone $week_start;
$week_end_clone->modify('+6 days');
$week_end_str = $week_end_clone->format('Y-m-d');

$reservations_map = []; // [date][start_hour] => ['realname'=>..., 'end_time'=>...]
if ($_SESSION['use_mysql']) {
    try {
        $stmt = $pdo->prepare("SELECT r.*, e.realname, e.ext_no FROM `room_reservations` r
            JOIN `employees` e ON r.username = e.username
            WHERE r.room_name = ? AND r.reserve_date BETWEEN ? AND ?
            ORDER BY r.start_time ASC");
        $stmt->execute([$selected_room, $week_start_str, $week_end_str]);
        $rows = $stmt->fetchAll();
    } catch (Exception $e) { $rows = []; }
} else {
    $rows = array_filter($_SESSION['mock_reservations'], function($r) use ($selected_room, $week_start_str, $week_end_str) {
        return $r['room_name'] === $selected_room
            && $r['reserve_date'] >= $week_start_str
            && $r['reserve_date'] <= $week_end_str;
    });
    // 為 mock 資料補齊 realname / ext_no
    $emp_map = $_SESSION['mock_employees'] ?? [];
    $rows = array_map(function($r) use ($emp_map) {
        $emp = $emp_map[$r['username']] ?? [];
        $r['realname'] = $r['realname'] ?? ($emp['realname'] ?? $r['username']);
        $r['ext_no']   = $emp['ext_no'] ?? '';
        return $r;
    }, $rows);
}

// 建立預約映射表
foreach ($rows as $r) {
    $s_hour = substr($r['start_time'], 0, 5); // e.g. '09:00'
    $e_hour = substr($r['end_time'], 0, 5);   // e.g. '11:00'
    // 填滿每個被佔用的小時格
    foreach (array_keys($time_slots) as $slot_start) {
        $slot_end = date('H:i', strtotime($slot_start) + 3600);
        // 如果預約區間與此格有重疊
        if ($s_hour < $slot_end && $e_hour > $slot_start) {
            $reservations_map[$r['reserve_date']][$slot_start] = [
                'id'       => $r['id'],
                'realname' => $r['realname'],
                'ext_no'   => $r['ext_no'],
                'purpose'  => $r['purpose'],
                'username' => $r['username'],
                'is_mine'  => ($r['username'] === $user['username']),
                'start'    => $s_hour,
                'end'      => $e_hour,
            ];
        }
    }
}

$week_names = ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日'];
$now_ts = time();
?>

<!-- 頁面標題 -->
<div class="page-header">
    <h1 class="page-title"><i class="fa-solid fa-door-open"></i> 會議室預約系統</h1>
    <p class="page-subtitle">請選擇會議室後，點選時段（可多選連續時段），再按下確認預約。</p>
</div>

<?php if (!empty($res_status)): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;"><i class="fa-solid fa-circle-check"></i> <?php echo $res_status; ?></div>
<?php endif; ?>
<?php if (!empty($res_error)): ?>
    <div class="alert alert-danger" style="margin-bottom:1rem;"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $res_error; ?></div>
<?php endif; ?>

<!-- 主卡片：週曆預約介面 -->
<div class="card col-12" style="padding: 1.5rem;">

    <!-- 頂部：選擇會議室 + 說明 -->
    <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <label style="font-weight:600; color:var(--primary-color); white-space:nowrap;">1. 預約會議室：</label>
            <select id="roomSelect" class="form-control" style="width:auto; min-width:180px;"
                onchange="changeRoom(this.value)">
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo htmlspecialchars($r); ?>"
                        <?php echo ($r === $selected_room) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="font-size:0.9rem; color:var(--text-muted);">
            <i class="fa-solid fa-circle-info"></i>
            2. 請點選選擇時段（請在您要的時段上點選後，就會出現打勾符號）
        </div>
    </div>

    <!-- 週曆導覽 -->
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
        <a href="?room=<?php echo urlencode($selected_room); ?>&week_offset=<?php echo $week_offset - 4; ?>"
           class="btn btn-secondary" style="padding:5px 10px; font-size:0.85rem;">
            <i class="fa-solid fa-angles-left"></i> 上個月
        </a>
        <a href="?room=<?php echo urlencode($selected_room); ?>&week_offset=<?php echo $week_offset - 1; ?>"
           class="btn btn-secondary" style="padding:5px 10px; font-size:0.85rem;">
            <i class="fa-solid fa-chevron-left"></i> 上週
        </a>
        <a href="?room=<?php echo urlencode($selected_room); ?>&week_offset=0"
           class="btn" style="padding:5px 14px; font-size:0.85rem; background: var(--primary-color);">
            <i class="fa-solid fa-rotate-left"></i> 回到今天
        </a>
        <a href="?room=<?php echo urlencode($selected_room); ?>&week_offset=<?php echo $week_offset + 1; ?>"
           class="btn btn-secondary" style="padding:5px 10px; font-size:0.85rem;">
            下週 <i class="fa-solid fa-chevron-right"></i>
        </a>
        <a href="?room=<?php echo urlencode($selected_room); ?>&week_offset=<?php echo $week_offset + 4; ?>"
           class="btn btn-secondary" style="padding:5px 10px; font-size:0.85rem;">
            下個月 <i class="fa-solid fa-angles-right"></i>
        </a>
    </div>

    <!-- 週曆主表格 -->
    <div style="overflow-x:auto;">
        <table id="calendarTable" style="width:100%; border-collapse:collapse; font-size:0.85rem; min-width:700px;">
            <thead>
                <tr>
                    <th style="background:var(--primary-color); color:white; padding:10px 12px; text-align:center; border:1px solid rgba(255,255,255,0.2); width:105px; min-width:105px;">
                        日期
                    </th>
                    <?php foreach ($week_days as $idx => $day): ?>
                        <?php
                            $is_today_col = ($day->format('Y-m-d') === date('Y-m-d'));
                            $is_weekend = ($idx >= 5); // 週六日
                            $th_bg = $is_today_col ? '#f0a500' : ($is_weekend ? '#5a7db5' : 'var(--primary-color)');
                        ?>
                        <th style="background:<?php echo $th_bg; ?>; color:white; padding:8px 6px; text-align:center; border:1px solid rgba(255,255,255,0.2);">
                            <div style="font-size:0.75rem; opacity:0.85;"><?php echo $day->format('Y/m/d'); ?></div>
                            <div style="font-weight:700;"><?php echo $week_names[$idx]; ?></div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($time_slots as $slot_start => $slot_label): ?>
                    <tr>
                        <td style="background:#eaf1fb; color:var(--primary-dark); font-weight:600; text-align:center; padding:8px 6px; border:1px solid #d0dff0; white-space:nowrap;">
                            <?php echo $slot_label; ?>
                        </td>
                        <?php foreach ($week_days as $day): ?>
                            <?php
                                $date_str = $day->format('Y-m-d');
                                $slot_end = date('H:i', strtotime($slot_start) + 3600);
                                $slot_ts = strtotime($date_str . ' ' . $slot_start);
                                $is_past = ($slot_ts < $now_ts);
                                $reserved = $reservations_map[$date_str][$slot_start] ?? null;

                                if ($is_past) {
                                    $cell_bg    = '#d0d0d0';
                                    $cell_text  = '#888';
                                    $clickable  = false;
                                    $cancelable = false;
                                } elseif ($reserved) {
                                    // 本人預約：橘黃底 + 可取消；他人：淡黃底
                                    $is_mine    = $reserved['is_mine'];
                                    $cell_bg    = $is_mine ? '#ffd87a' : '#fff3b0';
                                    $cell_text  = '#7a5f00';
                                    $clickable  = false;
                                    $cancelable = $is_mine && !$is_past;
                                } else {
                                    $cell_bg    = '#c8f0d4';
                                    $cell_text  = '#1a6b35';
                                    $clickable  = true;
                                    $cancelable = false;
                                }
                                $cell_id = 'cell_' . $date_str . '_' . str_replace(':', '', $slot_start);
                            ?>
                            <td id="<?php echo $cell_id; ?>"
                                style="background:<?php echo $cell_bg; ?>; color:<?php echo $cell_text; ?>; text-align:center; padding:7px 4px; border:1px solid #ccc; vertical-align:middle; position:relative; <?php echo $clickable ? 'cursor:pointer; user-select:none;' : ''; ?>"
                                <?php if ($clickable): ?>
                                    onclick="toggleSlot('<?php echo $cell_id; ?>', '<?php echo $date_str; ?>', '<?php echo $slot_start; ?>', '<?php echo $slot_end; ?>')"
                                    onmouseover="if(!this.classList.contains('selected-slot')) this.style.background='#a5e0b8';"
                                    onmouseout="if(!this.classList.contains('selected-slot')) this.style.background='<?php echo $cell_bg; ?>';"
                                <?php endif; ?>
                                data-date="<?php echo $date_str; ?>"
                                data-start="<?php echo $slot_start; ?>"
                                data-end="<?php echo $slot_end; ?>"
                                data-clickable="<?php echo $clickable ? '1' : '0'; ?>"
                            >
                                <?php if ($reserved): ?>
                                    <div style="font-size:0.78rem; font-weight:700; line-height:1.4;">
                                        <?php echo htmlspecialchars($reserved['ext_no']); ?><br>
                                        <?php echo htmlspecialchars($reserved['realname']); ?>
                                        <?php if ($cancelable): ?>
                                            <div style="margin-top:3px;">
                                                <button type="button"
                                                    onclick="openCancelModal(<?php echo $reserved['id']; ?>, '<?php echo htmlspecialchars($selected_room, ENT_QUOTES); ?>', '<?php echo $date_str; ?>', '<?php echo $reserved['start']; ?>', '<?php echo $reserved['end']; ?>', '<?php echo htmlspecialchars($reserved['purpose'], ENT_QUOTES); ?>')"
                                                    style="background:#e74c3c; color:white; border:none; border-radius:4px; padding:2px 6px; font-size:0.7rem; cursor:pointer; margin-top:2px;">
                                                    <i class="fa-solid fa-trash-can"></i> 取消
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="cell-label"><?php echo $is_past ? '已過期' : '空'; ?></span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 確認預約按鈕 -->
    <div style="text-align:center; margin-top:1.5rem;">
        <button type="button" class="btn" id="confirmBtn"
            style="padding:0.7rem 2.5rem; font-size:1rem; opacity:0.5; cursor:not-allowed;"
            onclick="openConfirmModal()" disabled>
            <i class="fa-solid fa-calendar-check"></i> 確認預約
        </button>
        <div id="selectedHint" style="font-size:0.85rem; color:var(--text-muted); margin-top:0.5rem;"></div>
    </div>
</div>

<!-- 確認預約 Modal -->
<div id="reserveModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:16px; padding:2rem; max-width:480px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="color:var(--primary-color); margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-calendar-plus"></i> 確認預約時段
        </h3>
        <div id="modalSummary" style="background:#f0f7ff; border-radius:8px; padding:1rem; margin-bottom:1rem; font-size:0.9rem; line-height:1.8;"></div>

        <form id="reserveForm" action="meeting_room.php" method="POST">
            <input type="hidden" name="action"       value="reserve_room">
            <input type="hidden" name="room_name"    id="formRoom">
            <input type="hidden" name="reserve_date" id="formDate">
            <input type="hidden" name="start_time"   id="formStart">
            <input type="hidden" name="end_time"     id="formEnd">

            <div class="form-group">
                <label class="form-label">會議目的 / 用途 <span style="color:var(--danger);">*</span></label>
                <input type="text" name="purpose" id="formPurpose" class="form-control"
                    placeholder="請簡述會議用途，例如: 周會/考核/面試" required autofocus>
            </div>
            <div style="display:flex; gap:10px; margin-top:1.2rem;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeModal()">
                    <i class="fa-solid fa-xmark"></i> 取消
                </button>
                <button type="submit" class="btn" style="flex:2;">
                    <i class="fa-solid fa-check"></i> 送出預約
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 取消預約 Modal -->
<div id="cancelModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:16px; padding:2rem; max-width:460px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="color:var(--danger); margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-trash-can"></i> 取消預約確認
        </h3>
        <div id="cancelSummary" style="background:#fff5f5; border:1px solid #fca5a5; border-radius:8px; padding:1rem; margin-bottom:1.2rem; font-size:0.9rem; line-height:1.8;"></div>
        <p style="font-size:0.9rem; color:#555; margin-bottom:1.2rem;">確定要取消此預約嗎？此操作無法復原。</p>
        <form id="cancelForm" action="meeting_room.php?room=<?php echo urlencode($selected_room); ?>&week_offset=<?php echo $week_offset; ?>" method="POST">
            <input type="hidden" name="action"         value="cancel_room">
            <input type="hidden" name="reservation_id" id="cancelResId">
            <input type="hidden" name="room_name"      id="cancelRoom">
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeCancelModal()">
                    <i class="fa-solid fa-xmark"></i> 保留預約
                </button>
                <button type="submit" class="btn" style="flex:1; background:var(--danger); border-color:var(--danger);">
                    <i class="fa-solid fa-trash-can"></i> 確認取消
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// 已選格子狀態
const selectedSlots = {}; // { 'date_start' : { date, start, end } }
const ROOM = <?php echo json_encode($selected_room); ?>;

function toggleSlot(cellId, date, start, end) {
    const cell = document.getElementById(cellId);
    const key  = date + '_' + start;

    if (selectedSlots[key]) {
        // 取消選取
        delete selectedSlots[key];
        cell.classList.remove('selected-slot');
        cell.style.background = '#c8f0d4';
        cell.querySelector('.cell-label').textContent = '空';
    } else {
        // 選取
        selectedSlots[key] = { date, start, end };
        cell.classList.add('selected-slot');
        cell.style.background = '#1d6cb5';
        cell.style.color = 'white';
        cell.querySelector('.cell-label').textContent = '✔';
    }
    updateConfirmButton();
}

function updateConfirmButton() {
    const btn  = document.getElementById('confirmBtn');
    const hint = document.getElementById('selectedHint');
    const count = Object.keys(selectedSlots).length;

    if (count === 0) {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor  = 'not-allowed';
        hint.textContent  = '';
    } else {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor  = 'pointer';
        hint.textContent  = `已選取 ${count} 個時段，請按「確認預約」送出。`;
    }
}

function openConfirmModal() {
    const slots = Object.values(selectedSlots);
    if (slots.length === 0) return;

    // 依日期與開始時間排序
    slots.sort((a, b) => {
        if (a.date !== b.date) return a.date.localeCompare(b.date);
        return a.start.localeCompare(b.start);
    });

    // 嘗試合併連續時段（同日期、連續小時）
    const groups = [];
    let cur = { ...slots[0] };
    for (let i = 1; i < slots.length; i++) {
        const s = slots[i];
        if (s.date === cur.date && s.start === cur.end) {
            cur.end = s.end; // 合併
        } else {
            groups.push({ ...cur });
            cur = { ...s };
        }
    }
    groups.push(cur);

    // 不允許跨日或多段（一次只能預約同日連續時段）
    if (groups.length > 1) {
        alert('一次只能預約同一天的連續時段，請重新選取。');
        return;
    }

    const g = groups[0];
    document.getElementById('formRoom').value  = ROOM;
    document.getElementById('formDate').value  = g.date;
    document.getElementById('formStart').value = g.start;
    document.getElementById('formEnd').value   = g.end;
    document.getElementById('formPurpose').value = '';

    // 格式化顯示
    const dayNames = ['日','一','二','三','四','五','六'];
    const d = new Date(g.date);
    const dayName = '星期' + dayNames[d.getDay()];
    document.getElementById('modalSummary').innerHTML = `
        <div><strong>📍 會議室：</strong>${ROOM}</div>
        <div><strong>📅 日期：</strong>${g.date}（${dayName}）</div>
        <div><strong>🕐 時段：</strong>${g.start} ～ ${g.end}</div>
    `;

    const modal = document.getElementById('reserveModal');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('formPurpose').focus(), 100);
}

function closeModal() {
    document.getElementById('reserveModal').style.display = 'none';
}

function changeRoom(roomName) {
    const offset = <?php echo $week_offset; ?>;
    window.location.href = 'meeting_room.php?room=' + encodeURIComponent(roomName) + '&week_offset=' + offset;
}

// 點 Modal 外側關閉
document.getElementById('reserveModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

// 取消預約 Modal
function openCancelModal(resId, room, date, start, end, purpose) {
    document.getElementById('cancelResId').value = resId;
    document.getElementById('cancelRoom').value  = room;

    const dayNames = ['日','一','二','三','四','五','六'];
    const d = new Date(date);
    const dayName = '星期' + dayNames[d.getDay()];
    document.getElementById('cancelSummary').innerHTML = `
        <div><strong>📍 會議室：</strong>${room}</div>
        <div><strong>📅 日期：</strong>${date}（${dayName}）</div>
        <div><strong>🕐 時段：</strong>${start} ～ ${end}</div>
        <div><strong>📝 用途：</strong>${purpose}</div>
    `;

    document.getElementById('cancelModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}
</script>

<?php
require_once '../includes/footer.php';
?>

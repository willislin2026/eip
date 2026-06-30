# EIP 會議室預約系統調整與修復說明

本文件記錄了本次針對 EIP 企業入口網站中「總務部 - 會議室預約系統」進行的修復、更名與排版優化。

---

## 📋 調整與優化重點

1. **會議室更名與清單更新**：
   - 配合公司現有會議室配置，將會議室選項更新為：
     * `A1 視訊會議室`
     * `A2 視訊會議室`
     * `A3 會議室`
2. **舊預約資料無縫移轉 (Migration)**：
   - 當系統以 MySQL 資料庫或 Session 模式載入時，會自動檢查並將歷史紀錄中的舊會議室名稱（如 `A會議室(大)`）更新為對應的新名稱，確保升級後歷史紀錄不會破圖或出錯。
3. **修復時間比對精度 Bug**：
   - 修正了 HTML5 time 提交的時間字串（長度為 5，例如 `14:00`）與 MySQL `TIME` 欄位（格式為 `14:00:00`）在比對時可能產生的精度問題。後端現在會自動將時間格式補齊為標準的 `HH:MM:SS`，確保時段衝突判定百分之百準確。
4. **預約日期防範機制**：
   - 限制不得預約過去的日期。若使用者選擇今天之前的日期提交，系統將阻擋並提示錯誤。
5. **補齊 CSS Grid 欄位寬度樣式**：
   - 會議室預約頁面使用 `col-5` 與 `col-7` 的雙欄比例，但原 CSS 缺乏定義。已在 `style.css` 中定義完整的 `.col-1` 到 `.col-12` 欄位樣式，修復卡片極窄縮水的問題。
   - 同步修正了響應式媒體查詢，確保所有寬度欄位在窄螢幕（小於 `992px`，如手機）下皆能以 `span 12` 滿版垂直堆疊，防堵手機排版破損。
6. **引入快取破除 (Cache Busting)**：
   - 在 `includes/header.php` 與 `login.php` 中載入 `style.css` 時，使用 PHP 的 `filemtime()` 動態追加檔案最後修改時間戳記：
     `style.css?v=<?php echo filemtime(...); ?>`
   - 只要 CSS 有修改，瀏覽器便會立刻強制下載最新樣式，徹底避免因瀏覽器快取舊 CSS 導致的排版異常。

---

## 📂 修改檔案與核心程式碼對比

### 1. 樣式表排版修正
* 📄 修改檔案：`style.css`
```diff
- .col-12 { grid-column: span 12; }
- .col-8 { grid-column: span 8; }
- .col-6 { grid-column: span 6; }
- .col-4 { grid-column: span 4; }
- .col-3 { grid-column: span 3; }
- 
- @media (max-width: 992px) {
-     .col-8, .col-6, .col-4, .col-3 { grid-column: span 12; }
- }
+ .col-1 { grid-column: span 1; }
+ .col-2 { grid-column: span 2; }
+ .col-3 { grid-column: span 3; }
+ .col-4 { grid-column: span 4; }
+ .col-5 { grid-column: span 5; }
+ .col-6 { grid-column: span 6; }
+ .col-7 { grid-column: span 7; }
+ .col-8 { grid-column: span 8; }
+ .col-9 { grid-column: span 9; }
+ .col-10 { grid-column: span 10; }
+ .col-11 { grid-column: span 11; }
+ .col-12 { grid-column: span 12; }
+ 
+ @media (max-width: 992px) {
+     .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12 {
+         grid-column: span 12 !important;
+     }
+ }
```

### 2. 會議室名稱、時間格式化與安全機制
* 📄 修改檔案：`admin/meeting_room.php`
```diff
- // 會議室清單
- $rooms = ['A會議室(大)', 'B會議室(中)', 'C會議室(小)'];
+ // 會議室清單
+ $rooms = ['A1 視訊會議室', 'A2 視訊會議室', 'A3 會議室'];
  
  // 處理預約送出
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve_room') {
      $selected_room = trim($_POST['room_name']);
      $reserve_date = trim($_POST['reserve_date']);
      $start_time = trim($_POST['start_time']);
      $end_time = trim($_POST['end_time']);
      $purpose = trim($_POST['purpose']);
      
+     // 將時間格式補齊為 HH:MM:SS，以便與資料庫/Session 的標準格式精準比對
+     if (strlen($start_time) === 5) {
+         $start_time .= ':00';
+     }
+     if (strlen($end_time) === 5) {
+         $end_time .= ':00';
+     }
+     
+     $today = date('Y-m-d');
+     
      if (empty($selected_room) || empty($reserve_date) || empty($start_time) || empty($end_time) || empty($purpose)) {
          $res_error = '所有欄位皆為必填';
+     } elseif ($reserve_date < $today) {
+         $res_error = '預約日期不得為過去的日期';
      } elseif ($start_time >= $end_time) {
          $res_error = '開始時間必須早於結束時間';
      } else {
```

### 3. 快取破除 (Cache Busting) 引入
* 📄 修改檔案：`includes/header.php`
```diff
- <!-- 全域樣式 -->
- <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css">
+ <!-- 全域樣式 (加上版本號以強制清除瀏覽器快取) -->
+ <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
```
* 📄 修改檔案：`login.php`
```diff
- <link rel="stylesheet" href="style.css">
+ <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
```

### 4. 資料庫預設紀錄更新與歷史資料移轉
* 📄 修改檔案：`config.php`
```diff
+     // 檢查並寫入預設會議室預約紀錄
+     $stmt = $pdo->query("SELECT COUNT(*) FROM `room_reservations`");
+     if ($stmt->fetchColumn() == 0) {
+         $reservations_mock = [
+             ['A1 視訊會議室', 'user2', date('Y-m-d'), '14:00:00', '16:00:00', '人資季度考核會議'],
+             ['A2 視訊會議室', 'admin', date('Y-m-d'), '10:00:00', '12:00:00', 'EIP系統上線進度檢討']
+         ];
+         $insert_stmt = $pdo->prepare("INSERT INTO `room_reservations` (room_name, username, reserve_date, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?, ?)");
+         foreach ($reservations_mock as $r) {
+             $insert_stmt->execute($r);
+         }
+     } else {
+         // 舊會議室名稱自動移轉升級
+         $pdo->exec("UPDATE `room_reservations` SET `room_name` = 'A1 視訊會議室' WHERE `room_name` = 'A會議室(大)'");
+         $pdo->exec("UPDATE `room_reservations` SET `room_name` = 'A2 視訊會議室' WHERE `room_name` = 'B會議室(中)'");
+         $pdo->exec("UPDATE `room_reservations` SET `room_name` = 'A3 會議室' WHERE `room_name` = 'C會議室(小)'");
+     }
```
以及 Session Mock 部分：
```diff
      // 預設預約會議室紀錄
      if (!isset($_SESSION['mock_reservations'])) {
          $_SESSION['mock_reservations'] = [
-             ['id' => 1, 'room_name' => 'A會議室(大)', 'username' => 'user2', 'reserve_date' => date('Y-m-d'), 'start_time' => '14:00', 'end_time' => '16:00', 'purpose' => '人資季度考核會議'],
-             ['id' => 2, 'room_name' => 'B會議室(中)', 'username' => 'admin', 'reserve_date' => date('Y-m-d'), 'start_time' => '10:00', 'end_time' => '12:00', 'purpose' => 'EIP系統上線進度檢討']
+             ['id' => 1, 'room_name' => 'A1 視訊會議室', 'username' => 'user2', 'reserve_date' => date('Y-m-d'), 'start_time' => '14:00:00', 'end_time' => '16:00:00', 'purpose' => '人資季度考核會議'],
+             ['id' => 2, 'room_name' => 'A2 視訊會議室', 'username' => 'admin', 'reserve_date' => date('Y-m-d'), 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'purpose' => 'EIP系統上線進度檢討']
          ];
+     } else {
+         // 如果已經有 Session 預約紀錄，也順便移轉更新
+         foreach ($_SESSION['mock_reservations'] as &$res) {
+             if ($res['room_name'] === 'A會議室(大)') {
+                 $res['room_name'] = 'A1 視訊會議室';
+             } elseif ($res['room_name'] === 'B會議室(中)') {
+                 $res['room_name'] = 'A2 視訊會議室';
+             } elseif ($res['room_name'] === 'C會議室(小)') {
+                 $res['room_name'] = 'A3 會議室';
+             }
+             // 同步將 Session 內時間格式補齊秒數
+             if (strlen($res['start_time']) === 5) {
+                 $res['start_time'] .= ':00';
+             }
+             if (strlen($res['end_time']) === 5) {
+                 $res['end_time'] .= ':00';
+             }
+         }
+         unset($res);
      }
```

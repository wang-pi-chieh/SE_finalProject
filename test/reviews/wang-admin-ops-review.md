# 王碧婕 Admin Ops 功能測試紀錄

這份文件是在記錄王碧婕負責的「管理員營運管理」功能目前測到哪裡、檢查了哪些檔案，以及還有哪些項目需要手動確認。

## 這個功能在做什麼

王碧婕這個分支主要負責管理員端的維運功能，包含：

- 管理員可以快速預覽不同角色的頁面，例如學生、老師、審查者、管理員。
- 管理員可以查看問題回報，並更新處理狀態。
- 管理員可以查看系統備份任務的狀態。
- 資料庫新增備份、還原、問題追蹤、資料封存相關資料表。

## 對應需求

- UC010：管理員檢視不同角色頁面。
- UC012：備份狀態、排程、還原驗證。
- UC013：問題回報與追蹤。
- DBS：資料庫備份、還原、封存、一致性控制。

## 這次檢查的檔案

- `SE_finalProject/獎助學金申請系統2/admin/js/admin-ops.js`
  - 管理員營運管理功能的前端 JavaScript。
  - 負責呼叫 API、載入問題回報、載入備份任務、顯示角色預覽連結。

- `SE_finalProject/獎助學金申請系統2/api/admin/get_issue_reports.php`
  - 從資料庫取得問題回報列表。

- `SE_finalProject/獎助學金申請系統2/api/admin/update_issue_report.php`
  - 更新問題回報的處理狀態，例如從 `open` 改成 `processing` 或 `resolved`。

- `SE_finalProject/獎助學金申請系統2/api/admin/get_backup_jobs.php`
  - 取得備份任務列表。
  - 顯示每個備份任務目前是排隊中、執行中、完成或失敗。

- `SE_finalProject/獎助學金申請系統2/api/admin/create_backup_job.php`
  - 建立新的備份任務紀錄。

- `SE_finalProject/獎助學金申請系統2/api/admin/get_role_preview_links.php`
  - 提供管理員預覽不同角色頁面的連結。

- `SE_finalProject/migrations/001_wang_issue_backup.sql`
  - 新增王碧婕功能需要的資料表。

## 已完成的檢查

### 1. JavaScript 語法檢查

執行指令：

```bash
node --check SE_finalProject/獎助學金申請系統2/admin/js/admin-ops.js
```

檢查結果：通過，沒有 JavaScript 語法錯誤。

### 2. PHP 語法檢查

檢查方式：

```bash
php -l
```

檢查範圍：所有新增的 admin API PHP 檔案。

檢查結果：通過，沒有 PHP 語法錯誤。

### 3. 功能覆蓋檢查

目前從程式碼檢查到以下功能都有對應實作：

- 問題回報列表可以依狀態篩選。
- 問題回報可以更新處理狀態。
- 備份任務列表可以顯示 `queued`、`running`、`completed`、`failed` 等狀態。
- 備份任務可以顯示驗證訊息。
- 角色預覽 API 會回傳學生、老師、審查者、管理員的預覽連結。
- migration 會建立以下資料表：
  - `issue_reports`：儲存問題回報。
  - `backup_jobs`：儲存備份任務。
  - `restore_logs`：儲存還原紀錄。
  - `data_archives`：儲存封存資料。

## 還需要手動測試的項目

以下項目尚未只靠語法檢查就能確認，需要在本機環境實際操作：

1. 將 `SE_finalProject/migrations/001_wang_issue_backup.sql` 匯入本機 MySQL 資料庫。
2. 開啟 `SE_finalProject/獎助學金申請系統2/admin/admin-dashboard.html`。
3. 確認「管理員營運管理」面板可以正常載入。
4. 手動新增一筆問題回報資料。
5. 確認問題回報狀態可以從 `open` 改成 `processing` 或 `resolved`。
6. 建立一筆備份任務。
7. 確認新的備份任務會出現在備份任務列表中。

## 總結

目前這個功能已經完成基本程式碼檢查，JavaScript 和 PHP 都沒有語法錯誤。從程式碼來看，問題回報、備份任務、角色預覽連結和資料庫 migration 都有對應實作。

不過，這份紀錄還不能代表功能已經完整驗收。最後仍需要在本機 MySQL 和管理員頁面上實際操作一次，確認資料表、API、前端畫面三者可以正常串接。

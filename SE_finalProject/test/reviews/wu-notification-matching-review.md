# Wu Ru-Ting — Student Notification & Eligibility Matching Review

**Owner:** 吳茹婷  
**Branch suggestion:** `feature/wu-student-notifications`  
**Date:** 2026-05-28（更新：Email 通知）

## Scope

- UC015 / NSAMS016：依學生系所、成績比對符合資格的獎學金推薦
- UC006 / UC022 / UC030（部分）：站內通知、截止提醒、審查結果通知、已讀狀態
- Email 通知：產生站內通知時，同步寄送至學生 Gmail（PHPMailer + Gmail SMTP）

## Changed Files

| File | Change |
| --- | --- |
| `migrations/005_wu_notification_matching.sql` | 新增 `student_notifications`、`scholarship_eligibility_rules`、種子規則；`email_sent_at` / `email_last_error` 欄位 |
| `api/student/matching_utils.php` | 資格比對、通知同步、Email 寄送與去重 |
| `api/common/mailer.php` | PHPMailer Gmail SMTP 封裝（讀環境變數，不含密碼） |
| `api/student/get_eligible_scholarships.php` | 依學生資料回傳符合獎學金與推薦原因 |
| `api/student/get_student_notifications.php` | 同步並取得站內通知 |
| `api/student/mark_notification_read.php` | 單筆 / 全部標記已讀 |
| `api/student/README.md` | Gmail SMTP 與 `composer install` 設定說明 |
| `student/js/student-notifications.js` | 通知 UI、推薦卡片、已讀互動 |
| `student/student-dashboard.js` | 最小接線，改呼叫新模組 |
| `student/student-dashboard.html` | 新增「全部已讀」按鈕 |
| `composer.json` / `composer.lock` | PHPMailer 依賴（`vendor/` 不上傳，本機 `composer install`） |

## Setup

1. 匯入 migration：

```sql
SOURCE SE_finalProject/migrations/005_wu_notification_matching.sql;
```

2. 確認 `api/db_connect.php` 的 `$dbname` 指向本機資料庫（例如 `database5`）。
3. 專案根目錄安裝 PHPMailer：

```bash
composer install
```

4. （Email 功能）在本機 Apache `httpd.conf` 設定環境變數（**勿 commit 密碼**）：

```apache
SetEnv NSAMS_SMTP_HOST "smtp.gmail.com"
SetEnv NSAMS_SMTP_PORT "587"
SetEnv NSAMS_SMTP_SECURE "tls"
SetEnv NSAMS_SMTP_USER "你的gmail@gmail.com"
SetEnv NSAMS_SMTP_PASS "你的16碼AppPassword"
SetEnv NSAMS_MAIL_FROM "你的gmail@gmail.com"
SetEnv NSAMS_MAIL_FROM_NAME "NSAMS 通知系統"
```

5. 重啟 Apache，以學生帳號登入後開啟 `student/student-dashboard.html`。

## Manual Test Cases

### T1 — 資格推薦（資工系 + 有成績）

- **Account:** `a1125544`（資訊工程學系，GPA 3.92，班排 3/45）
- **Steps:**
  1. 登入學生端
  2. 查看「為您推薦的獎學金」區塊
- **Expected:**
  - 不再固定顯示前三筆獎學金
  - 顯示符合系所 / GPA / 班排條件的項目
  - 卡片上可看到「推薦原因」與「符合資格」標籤

### T2 — 資格推薦（非資工系）

- **Account:** `a1125532`（財經法律學系，無 grades 資料）
- **Expected:**
  - 資工系專屬獎學金不應出現在推薦清單
  - 若無符合條件項目，顯示「目前沒有符合您系所與成績條件的推薦獎學金」

### T3 — 站內通知同步

- **Account:** 任一有申請紀錄的學生（如 `a1125532`）
- **Steps:**
  1. 開啟 dashboard
  2. 查看「最新通知」（首頁僅顯示最新 **3 筆**）
  3. 點「查看全部」開啟彈窗
- **Expected:**
  - 已通過 / 需補件 / 未通過申請會產生對應通知
  - 未讀通知顯示「未讀」標籤
  - 標題列顯示未讀數 badge
  - 彈窗內顯示**全部**通知（筆數可大於 3）

### T4 — 標記已讀

- **Steps:**
  1. 點擊單則通知
  2. 或點「全部已讀」
- **Expected:**
  - 單則點擊後未讀標籤消失（不需重整頁面）
  - 全部已讀後 badge 歸零
  - 重新整理頁面後狀態仍保留（資料寫入 DB）

### T5 — 截止提醒

- **Precondition:** 存在 5 日內截止、且學生尚未申請的獎學金
- **Expected:**
  - 通知類型為「截止提醒」
  - 可點「立即申請」導向申請表單

### T6 — Email 通知（Gmail SMTP）

- **Precondition:** 已完成 Setup 步驟 3～5；學生帳號在 `users` 表有有效 `email`
- **Steps:**
  1. 登入學生端並觸發通知同步（開啟 dashboard 或呼叫 `get_student_notifications.php?sync=1`）
  2. 至 phpMyAdmin 查看 `student_notifications` 最新一筆
  3. 檢查學生 Gmail 收件匣
- **Expected:**
  - `email_sent_at` 有時間、`email_last_error` 為 NULL → 寄送成功
  - 學生信箱收到標題與內容與站內通知一致的通知信
- **若失敗:** 查看 `email_last_error`（常見：`vendor` 未安裝、SMTP 環境變數未設、路徑錯誤）
- **重測:** 同一則通知已寄過不會重寄；可將該筆 `email_sent_at` / `email_last_error` 清為 NULL 後再觸發 sync

## API Smoke Test

```text
GET  /api/student/get_eligible_scholarships.php?student_username=a1125544
GET  /api/student/get_student_notifications.php?student_username=a1125532&sync=1
POST /api/student/mark_notification_read.php
     {"student_username":"a1125532","notification_id":1}
```

## Known Limits

- Email 需各開發者在本機 `httpd.conf` 設定 `NSAMS_*` 環境變數；**Gmail 密碼不上傳 Git**
- `vendor/` 由 `composer install` 產生，不納入版本庫
- 資格規則目前由 `scholarship_eligibility_rules` 種子資料維護，尚未提供管理端 UI
- 同一 `dedup_key` 的通知不會重複寄 Email（已寫入 `email_sent_at` 則跳過）
- 若 migration 尚未執行，API 會回傳「請先執行 migrations/005_wu_notification_matching.sql」

## Result

**實作狀態（開發完成）**

- [x] Migration 已建立（含 Email 狀態欄位）
- [x] 2+ API 已建立
- [x] 前端 JS 模組已實作並接線
- [x] Gmail Email 通知已整合（PHPMailer）
- [x] Review 紀錄已更新

**手動測試紀錄（請自行勾選，測過再填）**

| 項目 | 結果 | 測試者 | 日期 | 備註 |
| --- | --- | --- | --- | --- |
| T1 資格推薦（資工） | [ ] Pass / [ ] Fail | | | |
| T2 資格推薦（非資工） | [ ] Pass / [ ] Fail | | | |
| T3 站內通知 | [ ] Pass / [ ] Fail | | | |
| T4 標記已讀 | [ ] Pass / [ ] Fail | | | |
| T5 截止提醒 | [ ] Pass / [ ] Fail | | | |
| T6 Email 通知 | [ ] Pass / [ ] Fail | | | |

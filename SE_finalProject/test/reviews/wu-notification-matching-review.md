# Wu Ru-Ting — Student Notification & Eligibility Matching Review

**Owner:** 吳茹婷  
**Branch suggestion:** `feature/wu-student-notifications`  
**Date:** 2026-05-27

## Scope

- UC015 / NSAMS016：依學生系所、成績比對符合資格的獎學金推薦
- UC006 / UC022 / UC030（部分）：站內通知、截止提醒、審查結果通知、已讀狀態

## Changed Files

| File | Change |
| --- | --- |
| `migrations/005_wu_notification_matching.sql` | 新增 `student_notifications`、`scholarship_eligibility_rules` 與種子規則 |
| `api/student/matching_utils.php` | 資格比對與通知同步共用邏輯 |
| `api/student/get_eligible_scholarships.php` | 依學生資料回傳符合獎學金與推薦原因 |
| `api/student/get_student_notifications.php` | 同步並取得站內通知 |
| `api/student/mark_notification_read.php` | 單筆 / 全部標記已讀 |
| `student/js/student-notifications.js` | 通知 UI、推薦卡片、已讀互動 |
| `student/student-dashboard.js` | 最小接線，改呼叫新模組 |
| `student/student-dashboard.html` | 新增「全部已讀」按鈕 |

## Setup

1. 匯入 migration：

```sql
SOURCE SE_finalProject/migrations/005_wu_notification_matching.sql;
```

2. 確認資料庫連線設定在 `api/db_connect.php` 指向 `database4`。
3. 以學生帳號登入後開啟 `student/student-dashboard.html`。

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
  2. 查看「最新通知」
- **Expected:**
  - 已通過 / 需補件 / 未通過申請會產生對應通知
  - 未讀通知顯示「未讀」標籤
  - 標題列顯示未讀數 badge

### T4 — 標記已讀

- **Steps:**
  1. 點擊單則通知
  2. 或點「全部已讀」
- **Expected:**
  - 單則點擊後未讀標籤消失
  - 全部已讀後 badge 歸零
  - 重新整理頁面後狀態仍保留（資料寫入 DB）

### T5 — 截止提醒

- **Precondition:** 存在 5 日內截止、且學生尚未申請的獎學金
- **Expected:**
  - 通知類型為「截止提醒」
  - 可點「立即申請」導向申請表單

## API Smoke Test

```text
GET  /api/student/get_eligible_scholarships.php?student_username=a1125544
GET  /api/student/get_student_notifications.php?student_username=a1125532&sync=1
POST /api/student/mark_notification_read.php
     {"student_username":"a1125532","notification_id":1}
```

## Known Limits

- 本階段為站內通知，尚未整合 Email / SMTP（與陳鐿中、胡詠瀚分工項目重疊部分留待後續）
- 資格規則目前由 `scholarship_eligibility_rules` 種子資料維護，尚未提供管理端 UI
- 若 migration 尚未執行，API 會回傳「請先執行 migrations/005_wu_notification_matching.sql」

## Result

- [x] Migration 已建立
- [x] 2+ API 已建立
- [x] 前端 JS 模組已實作並接線
- [x] Review 紀錄已填寫

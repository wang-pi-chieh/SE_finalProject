# 高雄大學獎助學金申請與管理系統 NSAMS

本專案為第 1 組「高雄大學獎助學金申請與管理系統」實作與需求報告整理。為了符合老師要求的 git 分工紀錄，後續開發以六位組員各自負責一個功能模組為原則，讓每個人的 git diff、commit 與測試紀錄都能明確呈現。

## 分工規劃

| 組員 | 負責功能 | 主要改的檔案 |
| --- | --- | --- |
| 王碧婕 | 管理員營運管理：問題回報、備份狀態、操作日誌、角色頁面預覽 | `SE_finalProject/獎助學金申請系統2/admin/js/admin-ops.js`、`SE_finalProject/獎助學金申請系統2/api/admin/`、`SE_finalProject/migrations/001_wang_issue_backup.sql` |
| 陳鐿中 | 管理員獎助資料管理：獎助單位資料匯入、匯入預覽、人工確認、公告通知、報表匯出 | `SE_finalProject/獎助學金申請系統2/admin/js/scholarship-admin.js`、`SE_finalProject/獎助學金申請系統2/api/admin/`、`SE_finalProject/migrations/002_chen_import_export.sql` |
| 蔡博宇 | 獎助及審查單位：審查評分、結果整合、最終錄取名單、發放紀錄、結果匯出 | `SE_finalProject/獎助學金申請系統2/reviewer/js/award-list.js`、`SE_finalProject/獎助學金申請系統2/reviewer/js/disbursement.js`、`SE_finalProject/獎助學金申請系統2/api/reviewer/`、`SE_finalProject/migrations/003_tsai_award_disbursement.sql` |
| 謝從峰 | 學生申請流程：草稿暫存、草稿修改、檔案格式檢查、申請期限檢查、送出前資格檢查 | `SE_finalProject/獎助學金申請系統2/student/js/application-draft.js`、`SE_finalProject/獎助學金申請系統2/api/student/`、`SE_finalProject/migrations/004_xie_application_draft.sql` |
| 吳茹婷 | 學生推薦與通知：符合資格獎學金推薦、推薦原因、截止提醒、結果通知、通知已讀 | `SE_finalProject/獎助學金申請系統2/student/js/student-notifications.js`、`SE_finalProject/獎助學金申請系統2/api/student/`、`SE_finalProject/migrations/005_wu_notification_matching.sql` |
| 胡詠瀚 | 導師功能：奇偶導師學生名單、成績圖表、推薦信範本、退回補件、推薦信截止提醒 | `SE_finalProject/獎助學金申請系統2/teacher/js/mentor-students.js`、`SE_finalProject/獎助學金申請系統2/teacher/js/recommendation-template.js`、`SE_finalProject/獎助學金申請系統2/api/teacher/`、`SE_finalProject/migrations/006_hu_mentor_template.sql` |

## 每位組員至少要交付的內容

每位組員至少要在自己的 branch 中完成以下四類改動：

1. 一個前端 JS 檔案改動。
2. 一到兩個 API 檔案。
3. 一個 migration SQL 檔案。
4. 一份 `SE_finalProject/test/reviews/` 裡面的測試或 review 紀錄。

建議測試紀錄命名：

```text
SE_finalProject/test/reviews/wang-admin-ops-review.md
SE_finalProject/test/reviews/chen-import-export-review.md
SE_finalProject/test/reviews/tsai-reviewer-award-review.md
SE_finalProject/test/reviews/xie-student-draft-review.md
SE_finalProject/test/reviews/wu-notification-matching-review.md
SE_finalProject/test/reviews/hu-mentor-review.md
```

## Branch 建議

```text
feature/wang-admin-ops
feature/chen-import-export
feature/tsai-reviewer-award
feature/xie-student-draft
feature/wu-student-notifications
feature/hu-mentor-tools
```

## Commit Message 建議

```text
feat(admin): 王碧婕 add issue tracking and backup status
feat(admin): 陳鐿中 add sponsor import and report export
feat(reviewer): 蔡博宇 add award list and disbursement records
feat(student): 謝從峰 add application draft workflow
feat(student): 吳茹婷 add eligibility recommendation and notifications
feat(teacher): 胡詠瀚 add mentor templates and student charts
```

## 避免 Git 衝突的方式

這裡的「避免衝突」不是靠 git 自動解決，而是靠事先分好檔案邊界，讓大家不要同時改同一個檔案的同一段內容。

### 1. 每個人主要改自己的 JS 模組

已經將新功能入口拆成不同 JS 檔案並接回舊頁面。後續開發時，盡量把新邏輯寫在自己的模組裡，不要直接大改原本很長的 HTML inline script。

例如：

- 王碧婕主要改 `admin/js/admin-ops.js`。
- 陳鐿中主要改 `admin/js/scholarship-admin.js`。
- 謝從峰主要改 `student/js/application-draft.js`。
- 吳茹婷主要改 `student/js/student-notifications.js`。
- 胡詠瀚主要改 `teacher/js/mentor-students.js`、`teacher/js/recommendation-template.js`。
- 蔡博宇主要改 `reviewer/js/award-list.js`、`reviewer/js/disbursement.js`。

### 2. API 檔案用功能命名，不要大家改同一支

即使同在 `api/admin/` 或 `api/student/`，也要用不同檔名分開。

建議：

```text
api/admin/get_issue_reports.php
api/admin/update_issue_report.php
api/admin/import_sponsor_data.php
api/admin/export_system_report.php
api/student/save_application_draft.php
api/student/get_student_notifications.php
api/teacher/get_mentor_students.php
api/teacher/get_recommendation_templates.php
api/reviewer/generate_award_list.php
api/reviewer/update_disbursement.php
```

這樣王碧婕和陳鐿中雖然都在 `api/admin/`，但不會改同一個 PHP 檔；謝從峰和吳茹婷雖然都在 `api/student/`，也不會改同一個 PHP 檔。

### 3. SQL 用 migrations 分開，不一起改 `database4.sql`

不要大家同時改 `SE_finalProject/database4.sql`，因為這會很容易衝突。每個人改自己的 migration：

```text
SE_finalProject/migrations/001_wang_issue_backup.sql
SE_finalProject/migrations/002_chen_import_export.sql
SE_finalProject/migrations/003_tsai_award_disbursement.sql
SE_finalProject/migrations/004_xie_application_draft.sql
SE_finalProject/migrations/005_wu_notification_matching.sql
SE_finalProject/migrations/006_hu_mentor_template.sql
```

等功能都完成後，再由一個人統一整理或重匯出 baseline schema。

### 4. 舊 HTML 只做最小接線

如果一定要改舊頁面，例如要加一個按鈕或容器，原則是只加必要 DOM 區塊，不在同一個 HTML 裡塞大量新 JS。新邏輯要放進各自的 `js/` 模組。

### 5. 測試紀錄每人一份，避免互蓋

測試或 review 紀錄放在 `SE_finalProject/test/reviews/`，每人一個檔案。不要大家共同編輯同一份測試紀錄。

## 目前拆分狀態

目前已完成並行開發骨架：

```text
SE_finalProject/獎助學金申請系統2/admin/js/
SE_finalProject/獎助學金申請系統2/student/js/
SE_finalProject/獎助學金申請系統2/teacher/js/
SE_finalProject/獎助學金申請系統2/reviewer/js/
SE_finalProject/獎助學金申請系統2/api/admin/
SE_finalProject/獎助學金申請系統2/api/student/
SE_finalProject/獎助學金申請系統2/api/teacher/
SE_finalProject/獎助學金申請系統2/api/reviewer/
SE_finalProject/獎助學金申請系統2/api/common/
SE_finalProject/migrations/
SE_finalProject/test/reviews/
```

舊頁面已經接上新 JS 模組，但新模組目前尚未實作功能，因此應維持原本頁面行為。

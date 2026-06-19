# 高雄大學獎助學金申請與管理系統 NSAMS

本專案為第 1 組「高雄大學獎助學金申請與管理系統」實作與需求報告整理。為了符合老師要求的 git 分工紀錄，後續開發以六位組員各自負責一個功能模組為原則，讓每個人的 git diff、commit 與測試紀錄都能明確呈現。

## 報告需求缺口與負責分工

下表為依照目前程式碼比對需求報告後整理出的缺少功能與待補項目。後續六位組員應以自己的 branch 完成對應功能，並在 `SE_finalProject/test/reviews/` 補上實際測試或 review 紀錄。

| 報告需求 | 目前狀態 | 負責分工 |
| --- | --- | --- |
| UC005 匯入獎助單位資料 | 已補。管理端可下載 CSV 範本、上傳 CSV 預覽、顯示錯誤列、人工確認有效資料並記錄匯入批次。 | 陳鐿中 |
| UC006/UC022/UC030 Email 或站內通知 | 部分。已有 `student_notifications`、`teacher_notifications`、通知 API 與 Gmail SMTP helper；若未安裝 PHPMailer 或未設定 `NSAMS_SMTP_*`，系統會記錄 `email_last_error`，不會假裝寄送成功。仍缺 Zeabur 正式 SMTP 憑證設定與排程器。 | 吳茹婷、胡詠瀚、陳鐿中 |
| UC010 管理員檢視不同角色頁面 | 已補。新增 `api/admin/get_role_preview_links.php`，可建立學生、老師、審查單位 preview 帳號並回傳預覽入口。 | 王碧婕 |
| UC011 可選欄位匯出系統報表 | 部分。已有預算 PDF、審查端 PDF，並新增獎學金資料 CSV 匯出與匯出紀錄；仍未完成任意欄位選擇、期間選擇與隱私檢查。 | 陳鐿中 |
| UC012 備份狀態、排程、還原驗證 | 部分。已有 `backup_jobs`、`restore_logs`、`restore_uploads`、備份工作 API、SQL 上傳紀錄與管理端狀態列表；仍缺真正自動排程與還原後資料一致性測試報告。舊 `api/create_backup.php` 仍有 SQL fallback，新的 `api/admin/create_backup_job.php` 會把 ZIP 失敗記錄為 failed。 | 王碧婕 |
| UC013 問題回報與追蹤 | 已補。新增 `issue_reports`、`issue_report_notifications`、問題回報建立 API、管理端列表與狀態更新，會同步學生/老師/一般通知與 Gmail 狀態欄位。 | 王碧婕 |
| UC008/UC039 審查與核發/發放紀錄 | 已補。新增 `award_disbursements`、審查端撥款管理 tab、撥款狀態更新 API 與學生通知紀錄；發放狀態不再只用核准金額估算。 | 蔡博宇 |
| UC015/NSAMS016 學生資格自動推薦 | 已補。學生端改用 `api/student/get_eligible_scholarships.php`，依學生系所、GPA、平均成績、班排與申請期間比對；舊 `get_recommended_scholarships.php` 已改為委派到同一套 matcher，不再回傳固定前三筆。 | 吳茹婷 |
| UC018 學生申請暫存 | 已補。學生申請表每 30 秒將草稿同步到 `application_drafts`，重新開啟可恢復網站暫存；檔案本體仍在正式送出時上傳，草稿只保存檔名 metadata。 | 謝從峰 |
| UC024 導師名下學生名單 | 已補。新增 `mentor_assignments` 與 `api/teacher/get_mentor_students.php`，可依導師系所與奇偶規則取得學生名單。 | 胡詠瀚 |
| UC025 成績視覺化圖表 | 部分。新增 `api/teacher/get_student_grade_chart.php` 與導師工具面板可讀 DB 成績；既有 `teacher-dashboard.html` 內仍保留一段舊硬寫 Modal，需後續移除或改接 API。 | 胡詠瀚 |
| UC028 推薦信範本 | 已補。新增 `recommendation_templates`、`api/teacher/get_recommendation_templates.php`、`api/teacher/generate_recommendation_letter.php` 與導師端自動代入工具。 | 胡詠瀚 |
| UC029 導師退回學生補件 | 已補。新增 `mentor_return_records` 與 `api/teacher/return_application_for_supplement.php`，會更新申請狀態並建立學生通知。 | 胡詠瀚 |
| UC030 截止日前 5 天提醒導師 | 部分。已有 `teacher_notifications` 與 `api/teacher/get_teacher_reminders.php` 讀取提醒；仍缺伺服器排程器自動每日觸發與正式 Gmail SMTP 設定。 | 胡詠瀚 |
| UC031 推薦符合資格學生名單給導師 | 已補。新增 `mentor_scholarship_rules` 與 `api/teacher/get_eligible_students_for_scholarship.php`，依導師名下學生、系所、成績與排名條件比對。 | 胡詠瀚 |
| UC036 審查評分 | 已補。審查詳情頁新增 0-100 評分與審查階段，`submit_review.php` 會驗證並寫入 `applications.review_score`、`review_records.score`。 | 蔡博宇 |
| UC037 多階段審查結果整合 | 已補。`review_records.stage` 記錄初審/複審/決審，`api/reviewer/get_review_results.php` 回傳階段數、整合分數與審查歷程。 | 蔡博宇 |
| UC038 最終錄取名單 | 已補。新增 `final_award_results`、`api/reviewer/get_final_award_list.php`、`confirm_final_award_list.php` 與審查端「最終名單」tab，依 quota 和整合分數產生錄取/備取並可確認。 | 蔡博宇 |
| DBS 備份還原、封存、一致性控制 | 部分。已有 `backup_jobs`、`restore_logs`、`restore_uploads`、`data_archives` 與封存/下載/刪除原資料 API；仍缺自動排程、還原後自動比對與正式一致性驗證報告。 | 王碧婕 |

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

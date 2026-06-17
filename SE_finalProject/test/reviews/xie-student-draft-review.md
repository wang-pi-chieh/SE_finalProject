# 謝從峰 Student Draft Test Review

## Scope

- UC018 學生申請暫存
- 學生線上填寫申請資料與檔案格式檢查
- 申請期限檢查、重複申請檢查、送出前資格檢查

## Files Reviewed

- `SE_finalProject/獎助學金申請系統2/student/js/application-draft.js`
- `SE_finalProject/獎助學金申請系統2/api/student/save_application_draft.php`
- `SE_finalProject/獎助學金申請系統2/api/student/get_application_draft.php`
- `SE_finalProject/獎助學金申請系統2/api/student/validate_application_submission.php`
- `SE_finalProject/獎助學金申請系統2/api/student/submit_draft_application.php`
- `SE_finalProject/migrations/004_xie_application_draft.sql`

## Checks Performed

1. JavaScript syntax check
   - Command: `node --check SE_finalProject/獎助學金申請系統2/student/js/application-draft.js`
   - Result: passed.

2. PHP syntax check
   - Command: `php -l` on all four new student draft API files.
   - Result: passed, no syntax errors detected.

3. Parameter review
   - Confirmed `submit_draft_application.php` insert bind type string contains 16 types for 16 parameters.

4. Requirement coverage review
   - Draft save/load persists form payload in `application_drafts`.
   - Client-side file validation checks PDF/PNG/JPG/JPEG and 10MB limit before saving or validating.
   - Server-side validation checks required fields, application date window, duplicate application in academic year/semester, and grade-data availability.
   - Draft submission converts a draft into an `applications` row and marks the draft as submitted.

## Remaining Manual Test

- Apply migration `004_xie_application_draft.sql` to MySQL.
- Open `student/application-form.html`, fill part of the form, click 暫存草稿, then reload and click 載入草稿.
- Attach a non-PDF/image file and confirm client-side rejection.
- Run 送出前檢查 with an expired scholarship and confirm it blocks submission.
- Submit a valid draft and confirm an application row is created.

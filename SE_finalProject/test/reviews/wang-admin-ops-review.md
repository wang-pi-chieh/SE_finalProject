# 王碧婕 Admin Ops Test Review

## Scope

- UC010 管理員檢視不同角色頁面
- UC012 備份狀態、排程、還原驗證
- UC013 問題回報與追蹤
- DBS 備份還原、封存、一致性控制

## Files Reviewed

- `SE_finalProject/獎助學金申請系統2/admin/js/admin-ops.js`
- `SE_finalProject/獎助學金申請系統2/api/admin/get_issue_reports.php`
- `SE_finalProject/獎助學金申請系統2/api/admin/update_issue_report.php`
- `SE_finalProject/獎助學金申請系統2/api/admin/get_backup_jobs.php`
- `SE_finalProject/獎助學金申請系統2/api/admin/create_backup_job.php`
- `SE_finalProject/獎助學金申請系統2/api/admin/get_role_preview_links.php`
- `SE_finalProject/migrations/001_wang_issue_backup.sql`

## Checks Performed

1. JavaScript syntax check
   - Command: `node --check SE_finalProject/獎助學金申請系統2/admin/js/admin-ops.js`
   - Result: passed.

2. PHP syntax check
   - Command: `php -l` on all five new admin API files.
   - Result: passed, no syntax errors detected.

3. Requirement coverage review
   - Issue report list supports status filtering and status update.
   - Backup job list exposes queued/running/completed/failed state and verification message.
   - Role preview API returns readonly preview links for student, teacher, reviewer, and admin pages.
   - Migration creates `issue_reports`, `backup_jobs`, `restore_logs`, and `data_archives` to support tracking, backup verification, restore logs, and archive records.

## Remaining Manual Test

- Apply migration `001_wang_issue_backup.sql` to the local MySQL database.
- Open `admin/admin-dashboard.html` and confirm the 管理員營運管理 panel loads.
- Create one issue report row manually, then verify status can be changed from open to processing/resolved.
- Create a backup job and confirm it appears in the backup job list.

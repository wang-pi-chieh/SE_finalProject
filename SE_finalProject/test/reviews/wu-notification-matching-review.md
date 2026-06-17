# 吳茹婷 Notification Matching Test Review

## Scope

- UC015/NSAMS016 學生資格自動推薦
- UC022 學生截止提醒與審核結果通知
- 通知已讀狀態管理

## Files Reviewed

- `SE_finalProject/獎助學金申請系統2/student/js/student-notifications.js`
- `SE_finalProject/獎助學金申請系統2/api/student/get_eligible_scholarships.php`
- `SE_finalProject/獎助學金申請系統2/api/student/get_student_notifications.php`
- `SE_finalProject/獎助學金申請系統2/api/student/mark_notification_read.php`
- `SE_finalProject/獎助學金申請系統2/api/student/scan_student_notifications.php`
- `SE_finalProject/migrations/005_wu_notification_matching.sql`

## Checks Performed

1. JavaScript syntax check
   - Command: `node --check SE_finalProject/獎助學金申請系統2/student/js/student-notifications.js`
   - Result: passed.

2. PHP syntax check
   - Command: `php -l` on all four new student notification/matching API files.
   - Result: passed, no syntax errors detected.

3. Requirement coverage review
   - Eligibility matching reads student department and latest grade data.
   - Rule table supports department filter, minimum average score, maximum rank percentage, and tuition waiver requirement.
   - Recommendation response includes reasons so students can see why an item is recommended.
   - Notification scan creates deadline reminders and result notices with unique keys to avoid duplicate notifications.
   - Student can mark notifications as read.
   - Migration adds `student_notifications` and `notification_outbox` so notification data is no longer only a temporary frontend card.

## Remaining Manual Test

- Apply migration `005_wu_notification_matching.sql` to MySQL.
- Insert one `scholarship_eligibility_rules` row for a scholarship and verify matching changes by student grade/rank.
- Open `student/student-dashboard.html` and confirm the notification center loads.
- Create an application with status approved/rejected/revision and run notification scan.
- Click 已讀 and confirm `is_read` changes to 1.

# API Module Folder

Place role-specific API endpoints here to avoid editing the crowded legacy `api/` root during parallel development.

## Wu Ru-Ting (student notifications & matching)

| File | Purpose |
| --- | --- |
| `matching_utils.php` | Shared eligibility evaluation and notification sync helpers |
| `get_eligible_scholarships.php` | Return scholarships matched by department, GPA, average score, and class rank |
| `get_student_notifications.php` | Sync and fetch in-app notifications with unread count |
| `mark_notification_read.php` | Mark one or all notifications as read |

Run `SE_finalProject/migrations/005_wu_notification_matching.sql` before using these endpoints.

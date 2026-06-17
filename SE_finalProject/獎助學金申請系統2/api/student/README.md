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

## Gmail SMTP setup (optional but required for email notification)

1. Install PHPMailer in project root:
   - `composer require phpmailer/phpmailer`
2. Set environment variables for Apache/PHP runtime:
   - `NSAMS_SMTP_HOST=smtp.gmail.com`
   - `NSAMS_SMTP_PORT=587`
   - `NSAMS_SMTP_SECURE=tls`
   - `NSAMS_SMTP_USER=<your-gmail-account@gmail.com>`
   - `NSAMS_SMTP_PASS=<gmail-app-password>`
   - `NSAMS_MAIL_FROM=<your-gmail-account@gmail.com>`
   - `NSAMS_MAIL_FROM_NAME=NSAMS 通知系統`
3. Re-run migration `005_wu_notification_matching.sql` to add email status columns if needed.

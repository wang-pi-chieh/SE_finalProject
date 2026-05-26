# Contribution Breakdown

This document records the planned parallel work split for the NSAMS project. Each member should mainly edit their assigned module files to reduce merge conflicts and make git history easier to review.

| Member | Area | Primary Files |
| --- | --- | --- |
| Wang Bih-Jie | Admin operations: issue tracking, backup status, audit logs, role preview | `admin/js/admin-ops.js`, `api/admin/`, `SE_finalProject/migrations/001_wang_issue_backup.sql` |
| Chen Yi-Zhong | Scholarship administration: sponsor import, import confirmation, announcements, report export | `admin/js/scholarship-admin.js`, `api/admin/`, `SE_finalProject/migrations/002_chen_import_export.sql` |
| Tsai Bo-Yu | Reviewer and awarding: scoring, final award list, disbursement, result export | `reviewer/js/award-list.js`, `reviewer/js/disbursement.js`, `api/reviewer/`, `SE_finalProject/migrations/003_tsai_award_disbursement.sql` |
| Hsieh Tsung-Feng | Student application: draft save/edit, upload validation, deadline and eligibility checks | `student/js/application-draft.js`, `api/student/`, `SE_finalProject/migrations/004_xie_application_draft.sql` |
| Wu Ru-Ting | Student matching and notifications: eligible scholarships, reminders, result notices | `student/js/student-notifications.js`, `api/student/`, `SE_finalProject/migrations/005_wu_notification_matching.sql` |
| Hu Yong-Han | Mentor features: assigned students, charts, recommendation templates, return workflow, reminders | `teacher/js/mentor-students.js`, `teacher/js/recommendation-template.js`, `api/teacher/`, `SE_finalProject/migrations/006_hu_mentor_template.sql` |

## Parallel Development Rule

Avoid editing another member's primary files unless the team agrees first. If a shared legacy page must call a new module, keep the legacy-page change minimal and document it in the commit message.

# 胡詠瀚 Mentor Tools Test Review

## Scope

- UC024 導師名下學生名單與奇偶數導師規則
- UC025 學生成績視覺化圖表
- UC028 推薦信範本
- UC029 導師退回學生補件
- UC030 截止日前 5 天提醒導師
- UC031 推薦符合資格學生名單給導師

## Files Reviewed

- `SE_finalProject/獎助學金申請系統2/teacher/js/mentor-students.js`
- `SE_finalProject/獎助學金申請系統2/teacher/js/recommendation-template.js`
- `SE_finalProject/獎助學金申請系統2/api/teacher/get_mentor_students.php`
- `SE_finalProject/獎助學金申請系統2/api/teacher/get_student_grade_chart.php`
- `SE_finalProject/獎助學金申請系統2/api/teacher/get_recommendation_templates.php`
- `SE_finalProject/獎助學金申請系統2/api/teacher/generate_recommendation_letter.php`
- `SE_finalProject/獎助學金申請系統2/api/teacher/return_application_for_supplement.php`
- `SE_finalProject/獎助學金申請系統2/api/teacher/get_teacher_reminders.php`
- `SE_finalProject/獎助學金申請系統2/api/teacher/get_eligible_students_for_scholarship.php`
- `SE_finalProject/migrations/006_hu_mentor_template.sql`

## Checks Performed

1. JavaScript syntax check
   - Command: `node --check` on `teacher/js/mentor-students.js` and `teacher/js/recommendation-template.js`.
   - Result: passed.

2. PHP syntax check
   - Command: `php -l` on all seven new teacher API files.
   - Result: passed, no syntax errors detected.

3. Requirement coverage review
   - `get_mentor_students.php` supports mentor assignment by department and odd/even/all student number rule.
   - `get_student_grade_chart.php` returns semester grade rows and summary values for visual display.
   - `recommendation_templates` migration seeds general, financial-need, and academic templates.
   - `generate_recommendation_letter.php` replaces student, scholarship, grade, rank, and teacher placeholders.
   - `return_application_for_supplement.php` requires a non-empty reason, changes application status to supplement, and records the return.
   - `get_teacher_reminders.php` strictly checks recommendation cases exactly 5 days before scholarship deadline.
   - `get_eligible_students_for_scholarship.php` compares mentor-owned students with scholarship rule thresholds.

## Remaining Manual Test

- Apply migration `006_hu_mentor_template.sql` to MySQL.
- Insert a `mentor_assignments` row with `parity_rule = odd` and verify only odd-ending student IDs are listed.
- Open `teacher/teacher-dashboard.html`, click 查看成績, and confirm grade bars render from DB grades.
- Generate a recommendation letter from each seeded template and edit the output text.
- Try returning an application without a reason and confirm it is rejected.
- Create a scholarship deadline exactly 5 days from today and confirm reminder appears.

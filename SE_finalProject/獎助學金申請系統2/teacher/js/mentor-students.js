// Mentor student management module owned by Hu Yong-Han.
// Scope: assigned student list, odd/even mentor rules, and academic visualization helpers.

window.MentorStudentsModule = {
    async loadMentorStudents(user) {
        try {
            const res = await fetch(`../api/teacher/get_mentor_students.php?teacher_username=${user.username}`);
            const data = await res.json();

            if (data.success) {
                let mentorTypeDesc = '';
                if (data.mentor_type === 1) mentorTypeDesc = ' (單數導師)';
                else if (data.mentor_type === 0) mentorTypeDesc = ' (雙數導師)';

                document.getElementById('dept-info').innerHTML = `系所：<span class="font-bold text-primary">${data.department}</span>${mentorTypeDesc} | 學生列表`;
                
                // Call global functions defined in student-search.html
                if (typeof renderTable === 'function') {
                    renderTable(data.data);
                }
                if (typeof setupSearch === 'function') {
                    setupSearch(data.data);
                }
            } else {
                document.getElementById('dept-info').textContent = '載入失敗';
                document.getElementById('student-table-body').innerHTML = `<tr><td colspan="4" class="p-4 text-center text-red-500">${data.message || '無法取得資料'}</td></tr>`;
            }
        } catch (err) {
            console.error(err);
            document.getElementById('student-table-body').innerHTML = `<tr><td colspan="4" class="p-4 text-center text-red-500">連線錯誤</td></tr>`;
        }
    }
};

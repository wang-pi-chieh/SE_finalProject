// Mentor student management module owned by Hu Yong-Han.
// Scope: assigned student list, odd/even mentor rules, and academic visualization helpers.
(function () {
    const API_BASE = '../api/teacher';

    function getCurrentUser() {
        try {
            const raw = localStorage.getItem('currentUser') || sessionStorage.getItem('currentUser');
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            return {};
        }
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, options);
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || `Request failed: ${response.status}`);
        }
        return payload;
    }

    function buildPanel() {
        const panel = document.createElement('section');
        panel.id = 'mentor-students-panel';
        panel.className = 'my-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm';
        panel.innerHTML = `
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-blue-600">胡詠瀚負責模組</p>
                    <h2 class="text-2xl font-bold text-slate-900">導師學生管理與成績圖表</h2>
                    <p class="mt-1 text-sm text-slate-500">依奇偶導師規則列出名下學生，並顯示平均成績、GPA 與排名趨勢。</p>
                </div>
                <button id="refreshMentorStudents" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">重新整理</button>
            </div>
            <div class="mt-6 grid gap-4 xl:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">名下學生</h3>
                    <div id="mentorStudentList" class="mt-3 space-y-3 text-sm text-slate-600"></div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">成績視覺化資料</h3>
                    <div id="mentorGradeChart" class="mt-3 text-sm text-slate-600"></div>
                </div>
            </div>
        `;
        return panel;
    }

    function mountPanel() {
        if (document.getElementById('mentor-students-panel')) return;
        const target = document.querySelector('main') || document.querySelector('.main-content') || document.body;
        target.prepend(buildPanel());
        document.getElementById('refreshMentorStudents')?.addEventListener('click', loadStudents);
        loadStudents();
    }

    async function loadStudents() {
        const user = getCurrentUser();
        const payload = await fetchJson(`${API_BASE}/get_mentor_students.php?teacher_username=${encodeURIComponent(user.username || '')}`);
        renderStudents(payload.data || [], payload.assignment || {});
    }

    function renderStudents(students, assignment) {
        const container = document.getElementById('mentorStudentList');
        if (!container) return;
        if (students.length === 0) {
            container.innerHTML = `<p class="rounded-lg bg-slate-50 p-3 text-slate-500">目前沒有符合 ${escapeHtml(assignment.parity_rule || 'all')} 規則的學生。</p>`;
            return;
        }
        container.innerHTML = `
            <p class="rounded-lg bg-blue-50 p-3 text-blue-700">系所：${escapeHtml(assignment.department || '未設定')}，導師規則：${escapeHtml(assignment.parity_rule || 'all')}</p>
            ${students.map(student => `
                <article class="rounded-xl bg-slate-50 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900">${escapeHtml(student.real_name)} / ${escapeHtml(student.username)}</p>
                            <p class="mt-1 text-xs text-slate-500">${escapeHtml(student.email || '')} · ${escapeHtml(student.grade_level || '')} ${escapeHtml(student.class_name || '')}</p>
                        </div>
                        <button class="viewGrade rounded-md bg-slate-900 px-2 py-1 text-xs font-semibold text-white" data-username="${escapeHtml(student.username)}">查看成績</button>
                    </div>
                </article>
            `).join('')}
        `;
        container.querySelectorAll('.viewGrade').forEach(button => {
            button.addEventListener('click', () => loadGradeChart(button.dataset.username));
        });
    }

    async function loadGradeChart(studentUsername) {
        const payload = await fetchJson(`${API_BASE}/get_student_grade_chart.php?student_username=${encodeURIComponent(studentUsername)}`);
        renderGradeChart(payload.data || [], payload.summary || {});
    }

    function renderGradeChart(rows, summary) {
        const container = document.getElementById('mentorGradeChart');
        if (!container) return;
        if (rows.length === 0) {
            container.innerHTML = '<p class="rounded-lg bg-slate-50 p-3 text-slate-500">沒有成績資料。</p>';
            return;
        }
        const maxScore = 100;
        container.innerHTML = `
            <div class="mb-3 rounded-lg bg-slate-50 p-3">
                <p class="font-semibold text-slate-900">平均分數：${escapeHtml(summary.latest_avg_score || '-')}，GPA：${escapeHtml(summary.latest_gpa || '-')}，班排百分比：${escapeHtml(summary.latest_rank_percent || '-')}%</p>
            </div>
            <div class="space-y-3">
                ${rows.map(row => {
                    const score = Number(row.avg_score || 0);
                    const width = Math.max(0, Math.min(100, score / maxScore * 100));
                    return `<div>
                        <div class="mb-1 flex justify-between text-xs"><span>${escapeHtml(row.academic_year)} ${escapeHtml(row.semester)}</span><span>${score}</span></div>
                        <div class="h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full bg-blue-500" style="width:${width}%"></div></div>
                    </div>`;
                }).join('')}
            </div>
        `;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
    }

    document.addEventListener('DOMContentLoaded', mountPanel);
})();

// Mentor recommendation template module owned by Hu Yong-Han.
// Scope: recommendation letter templates, auto-fill helpers, return-for-supplement actions, and reminders.
(function () {
    const API_BASE = '../api/teacher';
    let templateDraftHandle = null;
    let returnDraftHandle = null;

    function getCurrentUser() {
        try {
            const raw = localStorage.getItem('user') || sessionStorage.getItem('user');
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
        panel.id = 'recommendation-template-panel';
        panel.className = 'my-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm';
        panel.innerHTML = `
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">推薦信範本與提醒</h2>
                    <p class="mt-1 text-sm text-slate-500">快速產生推薦信、退回補件，並列出截止日前 5 天提醒。</p>
                </div>
                <button id="refreshMentorTools" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">重新整理</button>
            </div>
            <div class="mt-6 grid gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">推薦信範本</h3>
                    <div class="mt-3 grid gap-2">
                        <input id="templateApplicationId" class="rounded-lg border border-slate-300 p-2 text-sm" placeholder="申請 ID">
                        <select id="templateKey" class="rounded-lg border border-slate-300 p-2 text-sm"></select>
                        <button id="generateRecommendationLetter" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white">產生範本</button>
                    </div>
                    <textarea id="generatedRecommendationLetter" class="mt-3 min-h-44 w-full rounded-lg border border-slate-300 p-3 text-sm" placeholder="產生後可手動修改"></textarea>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">退回學生補件</h3>
                    <input id="returnApplicationId" class="mt-3 w-full rounded-lg border border-slate-300 p-2 text-sm" placeholder="申請 ID">
                    <textarea id="returnReason" class="mt-3 min-h-28 w-full rounded-lg border border-slate-300 p-3 text-sm" placeholder="退回理由，必填"></textarea>
                    <button id="returnSupplement" class="mt-3 rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">退回補件</button>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">截止提醒與學生推薦</h3>
                    <div id="teacherReminderList" class="mt-3 space-y-3 text-sm text-slate-600"></div>
                    <input id="eligibleScholarshipId" class="mt-3 w-full rounded-lg border border-slate-300 p-2 text-sm" placeholder="獎學金 ID">
                    <button id="loadEligibleStudents" class="mt-2 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">推薦符合資格學生</button>
                    <div id="eligibleStudentList" class="mt-3 space-y-2 text-sm text-slate-600"></div>
                </div>
            </div>
        `;
        return panel;
    }

    function mountPanel() {
        if (document.getElementById('recommendation-template-panel')) return;
        const target = document.querySelector('main') || document.querySelector('.main-content') || document.body;
        target.prepend(buildPanel());
        document.getElementById('refreshMentorTools')?.addEventListener('click', refreshTools);
        document.getElementById('generateRecommendationLetter')?.addEventListener('click', generateLetter);
        document.getElementById('returnSupplement')?.addEventListener('click', returnSupplement);
        document.getElementById('loadEligibleStudents')?.addEventListener('click', loadEligibleStudents);
        initTeacherToolAutosave();
        refreshTools();
    }

    function initTeacherToolAutosave() {
        if (!window.ServerDraftAutosave) return;
        const user = getCurrentUser();
        if (!user.username) return;

        const templateApplicationId = document.getElementById('templateApplicationId');
        const generatedRecommendationLetter = document.getElementById('generatedRecommendationLetter');
        if (templateApplicationId && generatedRecommendationLetter) {
            templateDraftHandle = window.ServerDraftAutosave.register({
                actorUsername: user.username,
                draftType: 'teacher_tool',
                draftKey: 'teacher:recommendation-template',
                fields: [templateApplicationId, generatedRecommendationLetter],
                collect() {
                    return {
                        application_id: templateApplicationId.value,
                        content: generatedRecommendationLetter.value
                    };
                },
                apply(data) {
                    templateApplicationId.value = data.application_id || '';
                    generatedRecommendationLetter.value = data.content || '';
                },
                shouldSave() {
                    return true;
                }
            });
        }

        const returnApplicationId = document.getElementById('returnApplicationId');
        const returnReason = document.getElementById('returnReason');
        if (returnApplicationId && returnReason) {
            returnDraftHandle = window.ServerDraftAutosave.register({
                actorUsername: user.username,
                draftType: 'teacher_tool',
                draftKey: 'teacher:return-supplement',
                fields: [returnApplicationId, returnReason],
                collect() {
                    return {
                        application_id: returnApplicationId.value,
                        reason: returnReason.value
                    };
                },
                apply(data) {
                    returnApplicationId.value = data.application_id || '';
                    returnReason.value = data.reason || '';
                },
                shouldSave() {
                    return true;
                }
            });
        }
    }

    async function refreshTools() {
        const user = getCurrentUser();
        const [templates, reminders] = await Promise.all([
            fetchJson(`${API_BASE}/get_recommendation_templates.php`),
            fetchJson(`${API_BASE}/get_teacher_reminders.php?teacher_username=${encodeURIComponent(user.username || '')}`)
        ]);
        renderTemplates(templates.data || []);
        renderReminders(reminders.data || []);
    }

    function renderTemplates(items) {
        const select = document.getElementById('templateKey');
        if (!select) return;
        select.innerHTML = items.map(item => `<option value="${escapeHtml(item.template_key)}">${escapeHtml(item.title)}</option>`).join('');
    }

    function renderReminders(items) {
        const box = document.getElementById('teacherReminderList');
        if (!box) return;
        if (items.length === 0) {
            box.innerHTML = '<p class="rounded-lg bg-slate-50 p-3 text-slate-500">目前沒有截止日前 5 天提醒。</p>';
            return;
        }
        box.innerHTML = items.map(item => `<div class="rounded-lg bg-yellow-50 p-3 text-yellow-800">${escapeHtml(item.student_name)} 的 ${escapeHtml(item.scholarship_name)} 推薦信需在 ${escapeHtml(item.application_end_date)} 前完成。</div>`).join('');
    }

    async function generateLetter() {
        try {
            const user = getCurrentUser();
            const payload = await fetchJson(`${API_BASE}/generate_recommendation_letter.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    teacher_username: user.username || '',
                    application_id: document.getElementById('templateApplicationId')?.value,
                    template_key: document.getElementById('templateKey')?.value
                })
            });
            document.getElementById('generatedRecommendationLetter').value = payload.content || '';
            templateDraftHandle?.markDirty();
        } catch (err) {
            alert(`錯誤：${err.message}`);
        }
    }

    async function returnSupplement() {
        try {
            const user = getCurrentUser();
            await fetchJson(`${API_BASE}/return_application_for_supplement.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    teacher_username: user.username || '',
                    application_id: document.getElementById('returnApplicationId')?.value,
                    reason: document.getElementById('returnReason')?.value
                })
            });
            alert('已退回學生補件');
            returnDraftHandle?.clear();
        } catch (err) {
            alert(`退回失敗：${err.message}`);
        }
    }

    async function loadEligibleStudents() {
        const box = document.getElementById('eligibleStudentList');
        try {
            const user = getCurrentUser();
            const scholarshipId = document.getElementById('eligibleScholarshipId')?.value;
            if (!scholarshipId) throw new Error('請輸入獎學金 ID');
            
            const payload = await fetchJson(`${API_BASE}/get_eligible_students_for_scholarship.php?teacher_username=${encodeURIComponent(user.username || '')}&scholarship_id=${encodeURIComponent(scholarshipId)}`);
            box.innerHTML = (payload.data || []).map(item => `<div class="rounded-lg bg-green-50 p-3 text-green-800">${escapeHtml(item.real_name)} / ${escapeHtml(item.username)}：${escapeHtml((item.reasons || []).join('；'))}</div>`).join('') || '<p class="rounded-lg bg-slate-50 p-3 text-slate-500">沒有符合資格的學生。</p>';
        } catch (err) {
            if (box) box.innerHTML = `<p class="rounded-lg bg-red-50 p-3 text-red-600">載入錯誤：${escapeHtml(err.message)}</p>`;
            else alert(`錯誤：${err.message}`);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
    }

    document.addEventListener('DOMContentLoaded', mountPanel);
})();

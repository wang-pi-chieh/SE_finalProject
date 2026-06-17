// Student application draft module owned by Hsieh Tsung-Feng.
// Scope: draft save/load, draft editing, upload validation, deadline and eligibility checks.
(function () {
    const API_BASE = '../api/student';
    const allowedFileTypes = ['pdf', 'png', 'jpg', 'jpeg'];
    const maxFileSize = 10 * 1024 * 1024;

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

    function findForm() {
        return document.querySelector('form') || document.querySelector('#applicationForm');
    }

    function findScholarshipId() {
        const input = document.querySelector('[name="scholarship_id"], #scholarship_id, #scholarshipId');
        const params = new URLSearchParams(window.location.search);
        return input?.value || params.get('scholarship_id') || params.get('id') || '';
    }

    function serializeForm(form) {
        const data = {};
        if (!form) return data;
        new FormData(form).forEach((value, key) => {
            if (value instanceof File) return;
            if (data[key] !== undefined) {
                data[key] = Array.isArray(data[key]) ? [...data[key], value] : [data[key], value];
            } else {
                data[key] = value;
            }
        });
        return data;
    }

    function applyFormData(form, data) {
        if (!form || !data) return;
        Object.entries(data).forEach(([key, value]) => {
            const fields = form.querySelectorAll(`[name="${CSS.escape(key)}"]`);
            fields.forEach(field => {
                if (field.type === 'file') return;
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = Array.isArray(value) ? value.includes(field.value) : String(value) === field.value;
                } else {
                    field.value = Array.isArray(value) ? value[0] : value;
                }
            });
        });
    }

    function validateFiles(form) {
        const errors = [];
        if (!form) return errors;
        form.querySelectorAll('input[type="file"]').forEach(input => {
            Array.from(input.files || []).forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedFileTypes.includes(ext)) {
                    errors.push(`${file.name} 格式不符，只允許 PDF/PNG/JPG`);
                }
                if (file.size > maxFileSize) {
                    errors.push(`${file.name} 超過 10MB`);
                }
            });
        });
        return errors;
    }

    function ensurePanel() {
        if (document.getElementById('application-draft-panel')) return;
        const panel = document.createElement('section');
        panel.id = 'application-draft-panel';
        panel.className = 'my-6 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-slate-700';
        panel.innerHTML = `
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="font-semibold text-blue-700">謝從峰負責模組：申請草稿與送出前檢查</p>
                    <p class="mt-1 text-slate-600">可先暫存未完成申請，送出前檢查期限、重複申請、必填欄位與檔案格式。</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button id="loadApplicationDraft" type="button" class="rounded-lg border border-blue-300 px-3 py-2 font-semibold text-blue-700 hover:bg-white">載入草稿</button>
                    <button id="saveApplicationDraft" type="button" class="rounded-lg bg-blue-600 px-3 py-2 font-semibold text-white hover:bg-blue-700">暫存草稿</button>
                    <button id="validateApplicationDraft" type="button" class="rounded-lg bg-slate-900 px-3 py-2 font-semibold text-white hover:bg-slate-700">送出前檢查</button>
                </div>
            </div>
            <div id="applicationDraftMessage" class="mt-3 hidden rounded-lg p-3"></div>
        `;
        const form = findForm();
        if (form) {
            form.parentNode.insertBefore(panel, form);
        } else {
            document.body.prepend(panel);
        }
        document.getElementById('loadApplicationDraft')?.addEventListener('click', loadDraft);
        document.getElementById('saveApplicationDraft')?.addEventListener('click', saveDraft);
        document.getElementById('validateApplicationDraft')?.addEventListener('click', validateBeforeSubmit);
        bindFileValidation();
    }

    function showMessage(message, type = 'info') {
        const box = document.getElementById('applicationDraftMessage');
        if (!box) return;
        const classes = {
            info: 'bg-blue-100 text-blue-800',
            success: 'bg-green-100 text-green-800',
            error: 'bg-red-100 text-red-800',
            warning: 'bg-yellow-100 text-yellow-800'
        };
        box.className = `mt-3 rounded-lg p-3 ${classes[type] || classes.info}`;
        box.innerHTML = Array.isArray(message) ? message.map(item => `<p>${escapeHtml(item)}</p>`).join('') : escapeHtml(message);
        box.classList.remove('hidden');
    }

    async function saveDraft() {
        const form = findForm();
        const user = getCurrentUser();
        const fileErrors = validateFiles(form);
        if (fileErrors.length) {
            showMessage(fileErrors, 'error');
            return;
        }
        const payload = {
            student_username: user.username || form?.querySelector('[name="student_username"]')?.value || '',
            scholarship_id: findScholarshipId(),
            draft_payload: serializeForm(form)
        };
        const result = await fetchJson(`${API_BASE}/save_application_draft.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        showMessage(`草稿已儲存，草稿編號 ${result.draft_id}`, 'success');
    }

    async function loadDraft() {
        const form = findForm();
        const user = getCurrentUser();
        const params = new URLSearchParams({
            student_username: user.username || form?.querySelector('[name="student_username"]')?.value || '',
            scholarship_id: findScholarshipId()
        });
        const result = await fetchJson(`${API_BASE}/get_application_draft.php?${params.toString()}`);
        applyFormData(form, result.data?.draft_payload || {});
        showMessage(result.data ? '已載入最新草稿。' : '目前沒有草稿。', result.data ? 'success' : 'warning');
    }

    async function validateBeforeSubmit() {
        const form = findForm();
        const user = getCurrentUser();
        const fileErrors = validateFiles(form);
        if (fileErrors.length) {
            showMessage(fileErrors, 'error');
            return;
        }
        const result = await fetchJson(`${API_BASE}/validate_application_submission.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_username: user.username || form?.querySelector('[name="student_username"]')?.value || '',
                scholarship_id: findScholarshipId(),
                academic_year: form?.querySelector('[name="academic_year"]')?.value || '',
                semester: form?.querySelector('[name="semester"]')?.value || '',
                form_payload: serializeForm(form)
            })
        });
        showMessage(result.messages || ['檢查通過，可以送出。'], result.can_submit ? 'success' : 'error');
    }

    function bindFileValidation() {
        const form = findForm();
        form?.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', () => {
                const errors = validateFiles(form);
                if (errors.length) showMessage(errors, 'error');
            });
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
    }

    document.addEventListener('DOMContentLoaded', ensurePanel);
})();

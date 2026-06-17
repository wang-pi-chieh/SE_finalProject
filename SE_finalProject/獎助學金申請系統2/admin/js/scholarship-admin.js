// Scholarship administration module.
(function () {
    const API_BASE = '../api';
    const PAGE_LIMIT = 6;

    let currentPage = 1;
    let scholarshipsById = new Map();
    let scholarshipUnits = [];
    let unitsLoaded = false;
    let detailMode = 'view';
    let activeScholarshipId = null;

    const fieldClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm p-2 border';

    document.addEventListener('DOMContentLoaded', () => {
        const storedUser = localStorage.getItem('user');
        if (!storedUser) {
            window.location.href = '../login.html';
            return;
        }

        const user = JSON.parse(storedUser);
        if (user.role !== 'system_admin' && user.role !== '系統管理員') {
            // alert('Access Denied'); window.location.href = '../index.html';
        }

        ensureScholarshipDetailModal();
        fetchScholarships(currentPage);
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function asDateValue(value) {
        return value ? String(value).slice(0, 10) : '';
    }

    function isTruthyFlag(value) {
        return value === true || value === 1 || value === '1' || value === 'true';
    }

    function getScholarship(id) {
        return scholarshipsById.get(Number(id));
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, options);
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (error) {
            console.error('Server Text:', text);
            throw new Error('伺服器回傳格式錯誤');
        }
    }

    async function fetchScholarships(page = 1) {
        currentPage = page;

        try {
            const result = await fetchJson(`${API_BASE}/get_admin_scholarships.php?page=${page}&limit=${PAGE_LIMIT}`);
            const tbody = document.getElementById('scholarship-table-body');
            const paginationDiv = document.getElementById('pagination-controls');
            if (!tbody || !paginationDiv) return;

            tbody.innerHTML = '';
            scholarshipsById = new Map();

            if (result.success && Array.isArray(result.data) && result.data.length > 0) {
                result.data.forEach(item => {
                    scholarshipsById.set(Number(item.id), item);
                    tbody.appendChild(renderScholarshipRow(item));
                });
                renderPagination(result.pagination, paginationDiv);
            } else {
                tbody.innerHTML = '<tr><td colspan="8" class="p-4 text-center text-gray-500">目前沒有獎學金項目</td></tr>';
                paginationDiv.innerHTML = '';
            }
        } catch (error) {
            console.error(error);
            alert('讀取獎學金列表失敗：' + error.message);
        }
    }

    function renderScholarshipRow(item) {
        const providerLabel = item.unit_name || item.provider_username || '-';
        const desc = item.description || '';
        const shortDesc = desc.length > 30 ? desc.substring(0, 30) + '...' : desc || '-';
        const reviewBadge = isTruthyFlag(item.review_completed)
            ? '<span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">審核完成</span>'
            : '';

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#2a3441] transition-colors';
        tr.innerHTML = `
            <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(item.id)}</td>
            <td class="p-4 text-sm text-gray-900 dark:text-white font-bold">${escapeHtml(item.name)}${reviewBadge}</td>
            <td class="p-4 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(item.amount)}</td>
            <td class="p-4 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(item.quota)}</td>
            <td class="p-4 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(providerLabel)}</td>
            <td class="p-4 text-sm text-gray-500 dark:text-gray-400" title="${escapeHtml(desc)}">${escapeHtml(shortDesc)}</td>
            <td class="p-4 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(item.application_end_date || item.deadline || '-')}</td>
            <td class="p-4 text-right">
                <div class="inline-flex items-center justify-end gap-2">
                    <button type="button" onclick="openScholarshipDetail(${Number(item.id)}, 'view')" class="text-primary hover:text-blue-700 font-medium text-sm">檢視</button>
                    <button type="button" onclick="openScholarshipDetail(${Number(item.id)}, 'edit')" class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white font-medium text-sm">編輯</button>
                </div>
            </td>
        `;
        return tr;
    }

    function renderPagination(pagination, container) {
        if (!pagination) {
            container.innerHTML = '';
            return;
        }

        const current = Number(pagination.current_page || 1);
        const total = Number(pagination.total_pages || 1);
        let html = '';

        html += current > 1
            ? `<button type="button" onclick="fetchScholarships(${current - 1})" class="px-3 py-1 rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm transition-colors">上一頁</button>`
            : '<button type="button" disabled class="px-3 py-1 rounded-md bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 text-sm cursor-not-allowed">上一頁</button>';

        for (let i = 1; i <= total; i += 1) {
            html += i === current
                ? `<button type="button" class="px-3 py-1 rounded-md bg-primary text-white text-sm font-bold shadow-sm">${i}</button>`
                : `<button type="button" onclick="fetchScholarships(${i})" class="px-3 py-1 rounded-md bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 text-sm transition-colors">${i}</button>`;
        }

        html += current < total
            ? `<button type="button" onclick="fetchScholarships(${current + 1})" class="px-3 py-1 rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm transition-colors">下一頁</button>`
            : '<button type="button" disabled class="px-3 py-1 rounded-md bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 text-sm cursor-not-allowed">下一頁</button>';

        container.innerHTML = html;
    }

    function ensureScholarshipDetailModal() {
        if (document.getElementById('scholarship-detail-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'scholarship-detail-modal';
        modal.className = 'fixed inset-0 z-[9999] hidden';
        modal.setAttribute('aria-labelledby', 'scholarship-detail-title');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML = `
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeScholarshipDetail()"></div>
            <div class="fixed inset-0 z-[10000] w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                        <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div>
                                    <h3 id="scholarship-detail-title" class="text-xl font-semibold leading-6 text-gray-900 dark:text-white">獎學金資料</h3>
                                    <p id="scholarship-detail-subtitle" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                                </div>
                                <button type="button" onclick="closeScholarshipDetail()" class="rounded-md p-1 text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                    <span class="material-symbols-outlined text-[22px]">close</span>
                                </button>
                            </div>
                            <div id="scholarship-detail-content"></div>
                        </div>
                        <div id="scholarship-detail-actions" class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:items-center sm:justify-between sm:px-6"></div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function renderInfo(label, value) {
        return `
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">${escapeHtml(label)}</dt>
                <dd class="mt-1 whitespace-pre-wrap break-words text-sm text-gray-900 dark:text-white">${escapeHtml(value || '-')}</dd>
            </div>
        `;
    }

    function renderViewContent(item) {
        const providerLabel = item.unit_name
            ? `${item.unit_name} (${item.provider_username || '-'})`
            : item.provider_username || '-';

        return `
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                ${renderInfo('ID', item.id)}
                ${renderInfo('獎學金名稱', item.name)}
                ${renderInfo('金額', item.amount)}
                ${renderInfo('名額', item.quota)}
                ${renderInfo('獎助單位', providerLabel)}
                ${renderInfo('啟用狀態', isTruthyFlag(item.is_active) ? '啟用' : '停用')}
                ${renderInfo('申請開始日期', item.application_start_date)}
                ${renderInfo('申請截止日期', item.application_end_date || item.deadline)}
                ${renderInfo('建立時間', item.created_at)}
                ${renderInfo('審核完成', isTruthyFlag(item.review_completed) ? '是' : '否')}
                <div class="sm:col-span-2">${renderInfo('詳細說明 / 申請條件', item.description)}</div>
            </dl>
        `;
    }

    function renderEditContent(item) {
        const activeChecked = isTruthyFlag(item.is_active) ? 'checked' : '';
        const reviewChecked = isTruthyFlag(item.review_completed) ? 'checked' : '';

        return `
            <form id="edit-scholarship-form" class="space-y-4">
                <input type="hidden" name="id" value="${escapeHtml(item.id)}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">獎學金名稱</label>
                        <input type="text" name="name" required value="${escapeHtml(item.name)}" class="${fieldClass}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">金額</label>
                        <input type="text" name="amount" required value="${escapeHtml(item.amount)}" class="${fieldClass}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">名額</label>
                        <input type="number" name="quota" required min="0" value="${escapeHtml(item.quota)}" class="${fieldClass}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">申請開始日期</label>
                        <input type="date" name="application_start_date" value="${escapeHtml(asDateValue(item.application_start_date))}" class="${fieldClass}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">申請截止日期</label>
                        <input type="date" name="application_end_date" required value="${escapeHtml(asDateValue(item.application_end_date || item.deadline))}" class="${fieldClass}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">負責單位</label>
                        <select name="provider_username" id="edit-provider-select" class="${fieldClass}">
                            ${renderUnitOptions(item.provider_username)}
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">詳細說明 / 申請條件</label>
                        <textarea name="description" rows="4" class="${fieldClass}">${escapeHtml(item.description)}</textarea>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <label class="flex items-center gap-3 rounded-md border border-gray-200 dark:border-gray-600 p-3 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" name="is_active" value="1" ${activeChecked} class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span>啟用此獎學金項目</span>
                    </label>
                    <label class="flex items-center gap-3 rounded-md border border-gray-200 dark:border-gray-600 p-3 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" name="review_completed" value="1" ${reviewChecked} class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span>審核完成</span>
                    </label>
                </div>
            </form>
        `;
    }

    function renderUnitOptions(selectedUsername) {
        const selected = selectedUsername || '';
        const hasCurrentUnit = scholarshipUnits.some(unit => unit.username === selected);
        let options = '';

        if (selected && !hasCurrentUnit) {
            options += `<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)}</option>`;
        }

        scholarshipUnits.forEach(unit => {
            const label = `${unit.unit_name || unit.username} (${unit.username})`;
            options += `<option value="${escapeHtml(unit.username)}" ${unit.username === selected ? 'selected' : ''}>${escapeHtml(label)}</option>`;
        });

        return options || '<option value="">無可用單位</option>';
    }

    async function openScholarshipDetail(id, mode = 'view') {
        const item = getScholarship(id);
        if (!item) return;

        detailMode = mode === 'edit' ? 'edit' : 'view';
        activeScholarshipId = Number(id);

        if (detailMode === 'edit') {
            await fetchScholarshipUnits();
        }

        const modal = document.getElementById('scholarship-detail-modal');
        const title = document.getElementById('scholarship-detail-title');
        const subtitle = document.getElementById('scholarship-detail-subtitle');
        const content = document.getElementById('scholarship-detail-content');

        title.textContent = detailMode === 'edit' ? '編輯獎學金' : '檢視獎學金';
        subtitle.textContent = `ID：${item.id}`;
        content.innerHTML = detailMode === 'edit' ? renderEditContent(item) : renderViewContent(item);
        renderDetailActions();
        modal.classList.remove('hidden');
    }

    function renderDetailActions() {
        const actions = document.getElementById('scholarship-detail-actions');
        if (!actions) return;

        if (detailMode === 'edit') {
            actions.innerHTML = `
                <div class="flex w-full flex-col-reverse gap-2 sm:w-auto sm:flex-row">
                    <button type="button" onclick="deleteScholarship(${activeScholarshipId})" class="inline-flex justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">刪除</button>
                </div>
                <div class="flex w-full flex-col-reverse gap-2 sm:w-auto sm:flex-row-reverse">
                    <button type="button" onclick="submitEditScholarship()" class="inline-flex justify-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600">儲存</button>
                    <button type="button" onclick="openScholarshipDetail(${activeScholarshipId}, 'view')" class="inline-flex justify-center rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">返回檢視</button>
                </div>
            `;
        } else {
            actions.innerHTML = `
                <div class="flex w-full flex-col-reverse gap-2 sm:w-auto sm:flex-row">
                    <button type="button" onclick="deleteScholarship(${activeScholarshipId})" class="inline-flex justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">刪除</button>
                </div>
                <div class="flex w-full flex-col-reverse gap-2 sm:w-auto sm:flex-row-reverse">
                    <button type="button" onclick="openScholarshipDetail(${activeScholarshipId}, 'edit')" class="inline-flex justify-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600">編輯</button>
                    <button type="button" onclick="closeScholarshipDetail()" class="inline-flex justify-center rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">關閉</button>
                </div>
            `;
        }
    }

    function closeScholarshipDetail() {
        document.getElementById('scholarship-detail-modal')?.classList.add('hidden');
        activeScholarshipId = null;
    }

    async function fetchScholarshipUnits(targetSelectId = 'provider-select') {
        const select = document.getElementById(targetSelectId);

        if (!unitsLoaded) {
            try {
                const result = await fetchJson(`${API_BASE}/get_scholarship_units.php`);
                if (result.success && Array.isArray(result.data)) {
                    scholarshipUnits = result.data;
                    unitsLoaded = true;
                }
            } catch (error) {
                console.error('Error fetching units:', error);
            }
        }

        if (select && select.id === 'provider-select' && select.options.length <= 1) {
            select.innerHTML = '<option value="">(預設：系統管理員)</option>';
            scholarshipUnits.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit.username;
                option.textContent = `${unit.unit_name || unit.username} (${unit.username})`;
                select.appendChild(option);
            });
        }
    }

    function openAddModal() {
        document.getElementById('add-modal')?.classList.remove('hidden');
        fetchScholarshipUnits();
    }

    function closeAddModal() {
        document.getElementById('add-modal')?.classList.add('hidden');
    }

    async function submitAddForm() {
        const form = document.getElementById('add-scholarship-form');
        if (!form) return;
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        try {
            const result = await fetchJson(`${API_BASE}/create_scholarship.php`, {
                method: 'POST',
                body: new FormData(form)
            });

            if (result.success) {
                alert('新增成功！');
                closeAddModal();
                form.reset();
                fetchScholarships(1);
            } else {
                alert('新增失敗：' + (result.message || '未知錯誤'));
            }
        } catch (error) {
            console.error(error);
            alert('發生錯誤：' + error.message);
        }
    }

    async function submitScholarshipCsvImport() {
        const form = document.getElementById('import-scholarship-csv-form');
        if (!form) return;
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const fileInput = form.elements.csv_file;
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('請選擇 CSV 檔案');
            return;
        }

        try {
            const result = await fetchJson(`${API_BASE}/import_scholarships_csv.php`, {
                method: 'POST',
                body: new FormData(form)
            });

            if (result.success) {
                const errors = Array.isArray(result.errors) && result.errors.length > 0
                    ? `\n\n未匯入資料：\n${result.errors.slice(0, 8).join('\n')}${result.errors.length > 8 ? '\n...' : ''}`
                    : '';
                alert(`CSV 匯入完成，成功新增 ${result.imported_count || 0} 筆。${errors}`);
                form.reset();
                closeAddModal();
                fetchScholarships(1);
            } else {
                const errors = Array.isArray(result.errors) && result.errors.length > 0
                    ? `\n${result.errors.slice(0, 8).join('\n')}${result.errors.length > 8 ? '\n...' : ''}`
                    : '';
                alert('CSV 匯入失敗：' + (result.message || '未知錯誤') + errors);
            }
        } catch (error) {
            console.error(error);
            alert('CSV 匯入失敗：' + error.message);
        }
    }

    async function submitEditScholarship() {
        const form = document.getElementById('edit-scholarship-form');
        if (!form) return;
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const fd = new FormData(form);
        fd.set('is_active', form.elements.is_active.checked ? '1' : '0');
        fd.set('review_completed', form.elements.review_completed.checked ? '1' : '0');

        try {
            const result = await fetchJson(`${API_BASE}/update_scholarship.php`, {
                method: 'POST',
                body: fd
            });

            if (result.success) {
                alert('儲存成功！');
                await fetchScholarships(currentPage);
                if (activeScholarshipId) {
                    openScholarshipDetail(activeScholarshipId, 'view');
                }
            } else {
                alert('儲存失敗：' + (result.message || '未知錯誤'));
            }
        } catch (error) {
            console.error(error);
            alert('儲存失敗：' + error.message);
        }
    }

    async function deleteScholarship(id) {
        const item = getScholarship(id);
        const name = item?.name ? `「${item.name}」` : `ID ${id}`;
        if (!confirm(`確定要刪除獎學金 ${name} 嗎？此動作無法復原。`)) return;

        try {
            const result = await fetchJson(`${API_BASE}/delete_scholarship.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            });

            if (result.success) {
                closeScholarshipDetail();
                fetchScholarships(currentPage);
            } else {
                alert('刪除失敗：' + (result.message || '未知錯誤'));
            }
        } catch (error) {
            console.error(error);
            alert('刪除失敗：' + error.message);
        }
    }

    window.fetchScholarships = fetchScholarships;
    window.openAddModal = openAddModal;
    window.closeAddModal = closeAddModal;
    window.submitAddForm = submitAddForm;
    window.submitScholarshipCsvImport = submitScholarshipCsvImport;
    window.fetchScholarshipUnits = fetchScholarshipUnits;
    window.openScholarshipDetail = openScholarshipDetail;
    window.closeScholarshipDetail = closeScholarshipDetail;
    window.submitEditScholarship = submitEditScholarship;
    window.deleteScholarship = deleteScholarship;
})();

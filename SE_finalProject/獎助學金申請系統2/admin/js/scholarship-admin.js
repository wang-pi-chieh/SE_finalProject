// Scholarship administration module owned by Chen Yi-Zhong.
// Scope: sponsor-data import, import confirmation, announcements, and report export helpers.

(function () {
    let activeBatchId = null;
    let activeBatchCanConfirm = false;

    function setStatus(message, type = 'info') {
        const status = document.getElementById('scholarship-import-status');
        if (!status) return;

        const classes = {
            info: 'bg-blue-50 text-blue-700 border-blue-200',
            success: 'bg-green-50 text-green-700 border-green-200',
            error: 'bg-red-50 text-red-700 border-red-200',
            warning: 'bg-amber-50 text-amber-700 border-amber-200',
        };
        status.className = `mt-3 rounded-md border px-3 py-2 text-sm ${classes[type] || classes.info}`;
        status.textContent = message;
        status.classList.remove('hidden');
    }

    function clearStatus() {
        const status = document.getElementById('scholarship-import-status');
        if (!status) return;
        status.classList.add('hidden');
        status.textContent = '';
    }

    function downloadTemplate() {
        const headers = [
            'name',
            'provider_username',
            'description',
            'amount',
            'quota',
            'application_start_date',
            'application_end_date',
            'is_active',
        ];
        const example = [
            '校友急難救助獎學金',
            'alumni_association',
            '提供急難救助需求學生申請',
            '10000',
            '5',
            '2026-07-01',
            '2026-07-31',
            '1',
        ];
        const csv = '\uFEFF' + headers.join(',') + '\n' + example.map(escapeCsv).join(',') + '\n';
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'scholarship_import_template.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    function escapeCsv(value) {
        const text = String(value ?? '');
        if (/[",\n\r]/.test(text)) {
            return `"${text.replace(/"/g, '""')}"`;
        }
        return text;
    }

    function renderPreview(payload) {
        const preview = document.getElementById('scholarship-import-preview');
        const confirmBtn = document.getElementById('scholarship-import-confirm-btn');
        if (!preview || !confirmBtn) return;

        activeBatchId = payload.batch_id;
        activeBatchCanConfirm = payload.valid_rows > 0;
        confirmBtn.disabled = !activeBatchCanConfirm;
        confirmBtn.classList.toggle('opacity-50', !activeBatchCanConfirm);
        confirmBtn.classList.toggle('cursor-not-allowed', !activeBatchCanConfirm);

        const rows = payload.rows || [];
        const tableRows = rows.map(row => {
            const data = row.data || {};
            const errors = (row.errors || []).join('；');
            const statusClass = row.valid ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50';
            const statusText = row.valid ? '可匯入' : '需修正';
            return `
                <tr class="border-t border-gray-100 dark:border-gray-700">
                    <td class="px-3 py-2 text-xs text-gray-500">${row.line}</td>
                    <td class="px-3 py-2 text-sm font-medium">${escapeHtml(data.name || '-')}</td>
                    <td class="px-3 py-2 text-sm">${escapeHtml(data.provider_username || '-')}</td>
                    <td class="px-3 py-2 text-sm">${escapeHtml(data.amount || '-')}</td>
                    <td class="px-3 py-2 text-sm">${escapeHtml(data.quota ?? '-')}</td>
                    <td class="px-3 py-2 text-sm">${escapeHtml(data.application_end_date || '-')}</td>
                    <td class="px-3 py-2"><span class="rounded-full px-2 py-1 text-xs font-semibold ${statusClass}">${statusText}</span></td>
                    <td class="px-3 py-2 text-xs text-red-600">${escapeHtml(errors)}</td>
                </tr>
            `;
        }).join('');

        preview.innerHTML = `
            <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-3 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                    <span>批次 #${payload.batch_id}</span>
                    <span>總列數：${payload.total_rows}</span>
                    <span class="text-green-700">有效：${payload.valid_rows}</span>
                    <span class="text-red-700">錯誤：${payload.error_rows}</span>
                    ${payload.truncated ? '<span class="text-gray-500">只顯示前 30 列</span>' : ''}
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead class="bg-white dark:bg-gray-900 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-3 py-2">列</th>
                                <th class="px-3 py-2">名稱</th>
                                <th class="px-3 py-2">單位帳號</th>
                                <th class="px-3 py-2">金額</th>
                                <th class="px-3 py-2">名額</th>
                                <th class="px-3 py-2">截止日</th>
                                <th class="px-3 py-2">狀態</th>
                                <th class="px-3 py-2">問題</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900">${tableRows}</tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function uploadPreview() {
        const fileInput = document.getElementById('scholarship-import-file');
        const confirmBtn = document.getElementById('scholarship-import-confirm-btn');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            setStatus('請先選擇 CSV 檔案。', 'warning');
            return;
        }

        clearStatus();
        activeBatchId = null;
        activeBatchCanConfirm = false;
        if (confirmBtn) {
            confirmBtn.disabled = true;
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        try {
            setStatus('正在解析 CSV...', 'info');
            const response = await fetch('../api/admin/scholarship_import_preview.php', {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'CSV 解析失敗');
            }
            renderPreview(result.data);
            setStatus(result.message, result.data.error_rows > 0 ? 'warning' : 'success');
            loadImportHistory();
        } catch (error) {
            setStatus(`匯入預覽失敗：${error.message}`, 'error');
        }
    }

    async function confirmImport() {
        if (!activeBatchId || !activeBatchCanConfirm) {
            setStatus('沒有可確認的匯入批次。', 'warning');
            return;
        }

        const announcement = document.getElementById('scholarship-import-announcement');
        const shouldAnnounce = Boolean(announcement && announcement.checked);

        try {
            setStatus('正在寫入獎學金資料...', 'info');
            const response = await fetch('../api/admin/confirm_scholarship_import.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    batch_id: activeBatchId,
                    create_announcement: shouldAnnounce,
                }),
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || '確認匯入失敗');
            }

            setStatus(`匯入完成：新增 ${result.data.inserted_rows} 筆獎學金。`, 'success');
            activeBatchCanConfirm = false;
            const confirmBtn = document.getElementById('scholarship-import-confirm-btn');
            if (confirmBtn) confirmBtn.disabled = true;
            if (typeof window.fetchScholarships === 'function') {
                window.fetchScholarships(1);
            }
            loadImportHistory();
        } catch (error) {
            setStatus(`確認匯入失敗：${error.message}`, 'error');
        }
    }

    function exportScholarships() {
        window.location.href = '../api/admin/export_scholarships_csv.php';
    }

    async function loadImportHistory() {
        const container = document.getElementById('scholarship-import-history');
        if (!container) return;

        try {
            const response = await fetch('../api/admin/get_scholarship_import_batches.php?limit=5');
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || '讀取匯入紀錄失敗');
            }

            const rows = result.data || [];
            if (rows.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500">尚無匯入紀錄</p>';
                return;
            }

            container.innerHTML = rows.map(row => {
                const statusLabel = {
                    uploaded: '待確認',
                    confirmed: '已匯入',
                    failed: '失敗',
                }[row.status] || row.status;
                return `
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 dark:border-gray-700 py-2 text-sm">
                        <div>
                            <div class="font-medium">${escapeHtml(row.original_filename)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(row.created_at || '')} / ${escapeHtml(row.uploaded_by || '-')}</div>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            <div>${statusLabel}</div>
                            <div>有效 ${row.valid_rows} / 錯誤 ${row.error_rows}</div>
                        </div>
                    </div>
                `;
            }).join('');
        } catch (error) {
            container.innerHTML = `<p class="text-sm text-red-600">匯入紀錄讀取失敗：${escapeHtml(error.message)}</p>`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const previewBtn = document.getElementById('scholarship-import-preview-btn');
        const confirmBtn = document.getElementById('scholarship-import-confirm-btn');
        const exportBtn = document.getElementById('scholarship-export-csv-btn');
        const templateBtn = document.getElementById('scholarship-template-btn');

        if (previewBtn) previewBtn.addEventListener('click', uploadPreview);
        if (confirmBtn) {
            confirmBtn.addEventListener('click', confirmImport);
            confirmBtn.disabled = true;
        }
        if (exportBtn) exportBtn.addEventListener('click', exportScholarships);
        if (templateBtn) templateBtn.addEventListener('click', downloadTemplate);

        loadImportHistory();
    });
})();

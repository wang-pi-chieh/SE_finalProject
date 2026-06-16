// Admin operations module owned by Wang Bih-Jie.
// Scope: issue reports, backup status, audit log views, and role preview helpers.
(function () {
    const API_BASE = '../api/admin';
    const state = {
        issueReports: [],
        backupJobs: [],
        restoreUploads: [],
        dataArchives: [],
        archiveCandidates: [],
        phpMyAdminUrl: 'http://localhost/phpmyadmin/index.php?route=/database/import&db=se_finalproject',
        roleLinks: []
    };

    document.addEventListener('DOMContentLoaded', () => {
        mountAdminOpsPanel();
        bindAdminOpsEvents();
        refreshAdminOps();
    });

    function mountAdminOpsPanel() {
        if (document.getElementById('admin-ops-panel')) return;

        const main = document.querySelector('main');
        if (!main) return;

        const section = document.createElement('section');
        section.id = 'admin-ops-panel';
        section.className = 'grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8';
        section.innerHTML = `
            <div class="xl:col-span-2 bg-white dark:bg-[#1e2634] rounded-xl border border-[#e5e7eb] dark:border-[#2d3748] shadow-sm overflow-hidden">
                <div class="p-6 border-b border-[#e5e7eb] dark:border-[#2d3748] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-[#111318] dark:text-white text-lg font-bold">問題回報處理</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">追蹤使用者回報並更新處理狀態</p>
                    </div>
                    <button id="admin-ops-refresh-btn" type="button" class="px-3 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90">
                        重新整理
                    </button>
                </div>
                <div id="issue-report-list" class="p-4 space-y-3">
                    <p class="p-6 text-center text-gray-500">載入中...</p>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <div class="bg-white dark:bg-[#1e2634] rounded-xl border border-[#e5e7eb] dark:border-[#2d3748] shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-[#111318] dark:text-white text-lg font-bold">備份工作</h3>
                        <button id="create-backup-job-btn" type="button" class="px-3 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90">
                            建立
                        </button>
                    </div>
                    <div id="backup-job-list" class="space-y-3">
                        <p class="text-sm text-gray-500">載入中...</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e2634] rounded-xl border border-[#e5e7eb] dark:border-[#2d3748] shadow-sm p-6">
                    <h3 class="text-[#111318] dark:text-white text-lg font-bold mb-2">資料還原</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">先上傳 SQL 檔，再開啟 phpMyAdmin 匯入 se_finalproject。</p>
                    <form id="restore-sql-form" class="space-y-3">
                        <input id="restore-sql-file" name="restore_sql" type="file" accept=".sql" class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-primary/90">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button id="upload-restore-sql-btn" type="submit" class="px-3 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90">上傳 SQL</button>
                            <a id="open-phpmyadmin-import-link" href="${state.phpMyAdminUrl}" target="_blank" rel="noopener" class="px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 text-center text-sm font-bold text-primary hover:bg-primary/5">開啟 phpMyAdmin</a>
                        </div>
                    </form>
                    <div id="restore-upload-list" class="space-y-3 mt-4">
                        <p class="text-sm text-gray-500">載入中...</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e2634] rounded-xl border border-[#e5e7eb] dark:border-[#2d3748] shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <h3 class="text-[#111318] dark:text-white text-lg font-bold">資料封存</h3>
                        <button id="create-archive-btn" type="button" class="px-3 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90">
                            封存
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">只匯出封存檔，不刪除原資料。</p>
                    <div class="space-y-3 mb-4">
                        <select id="archive-type-select" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="resolved_issue_reports">已解決問題回報</option>
                            <option value="students_over_years">入學超過 4 年學生</option>
                            <option value="applications_by_year">年度申請紀錄</option>
                            <option value="expired_scholarships">過期/停用獎學金項目</option>
                            <option value="old_announcements">過期公告</option>
                        </select>
                        <div id="archive-extra-fields" class="space-y-3"></div>
                    </div>
                    <div id="data-archive-list" class="space-y-3">
                        <p class="text-sm text-gray-500">載入中...</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e2634] rounded-xl border border-[#e5e7eb] dark:border-[#2d3748] shadow-sm p-6">
                    <h3 class="text-[#111318] dark:text-white text-lg font-bold mb-4">角色預覽</h3>
                    <div id="role-preview-list" class="grid grid-cols-1 gap-2">
                        <p class="text-sm text-gray-500">載入中...</p>
                    </div>
                </div>
            </div>
        `;

        main.appendChild(section);
        mountIssueReportsModal();
        mountBackupJobsModal();
        mountRestoreUploadsModal();
        mountDataArchivesModal();
    }

    function mountIssueReportsModal() {
        if (document.getElementById('issue-reports-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'issue-reports-modal';
        modal.className = 'fixed inset-0 z-[10000] hidden';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" data-issue-modal-close></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-3xl max-h-[80vh] overflow-hidden rounded-xl bg-white dark:bg-[#1e2634] shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-[#111318] dark:text-white">問題回報紀錄</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">查看全部問題回報與處理狀態</p>
                        </div>
                        <button type="button" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500" data-issue-modal-close>
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div id="issue-reports-modal-list" class="p-6 overflow-y-auto max-h-[58vh] space-y-3">
                    </div>
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end bg-gray-50 dark:bg-gray-800/60">
                        <button type="button" class="px-4 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90" data-issue-modal-close>關閉</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        modal.querySelectorAll('[data-issue-modal-close]').forEach((button) => {
            button.addEventListener('click', closeIssueReportsModal);
        });
    }

    function mountBackupJobsModal() {
        if (document.getElementById('backup-jobs-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'backup-jobs-modal';
        modal.className = 'fixed inset-0 z-[10000] hidden';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" data-backup-modal-close></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-2xl max-h-[80vh] overflow-hidden rounded-xl bg-white dark:bg-[#1e2634] shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-[#111318] dark:text-white">備份工作紀錄</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">查看全部備份建立與下載紀錄</p>
                        </div>
                        <button type="button" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500" data-backup-modal-close>
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div id="backup-jobs-modal-list" class="p-6 overflow-y-auto max-h-[58vh] space-y-3">
                    </div>
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end bg-gray-50 dark:bg-gray-800/60">
                        <button type="button" class="px-4 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90" data-backup-modal-close>關閉</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        modal.querySelectorAll('[data-backup-modal-close]').forEach((button) => {
            button.addEventListener('click', closeBackupJobsModal);
        });
    }

    function mountRestoreUploadsModal() {
        if (document.getElementById('restore-uploads-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'restore-uploads-modal';
        modal.className = 'fixed inset-0 z-[10000] hidden';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" data-restore-modal-close></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-2xl max-h-[80vh] overflow-hidden rounded-xl bg-white dark:bg-[#1e2634] shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-[#111318] dark:text-white">資料還原紀錄</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">查看全部 SQL 上傳與還原匯入紀錄</p>
                        </div>
                        <button type="button" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500" data-restore-modal-close>
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div id="restore-uploads-modal-list" class="p-6 overflow-y-auto max-h-[58vh] space-y-3">
                    </div>
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end bg-gray-50 dark:bg-gray-800/60">
                        <button type="button" class="px-4 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90" data-restore-modal-close>關閉</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        modal.querySelectorAll('[data-restore-modal-close]').forEach((button) => {
            button.addEventListener('click', closeRestoreUploadsModal);
        });
    }

    function mountDataArchivesModal() {
        if (document.getElementById('data-archives-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'data-archives-modal';
        modal.className = 'fixed inset-0 z-[10000] hidden';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" data-archives-modal-close></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-2xl max-h-[80vh] overflow-hidden rounded-xl bg-white dark:bg-[#1e2634] shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-[#111318] dark:text-white">資料封存紀錄</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">查看全部資料封存與下載紀錄</p>
                        </div>
                        <button type="button" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500" data-archives-modal-close>
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div id="data-archives-modal-list" class="p-6 overflow-y-auto max-h-[58vh] space-y-3">
                    </div>
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end bg-gray-50 dark:bg-gray-800/60">
                        <button type="button" class="px-4 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90" data-archives-modal-close>關閉</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        modal.querySelectorAll('[data-archives-modal-close]').forEach((button) => {
            button.addEventListener('click', closeDataArchivesModal);
        });
    }

    function bindAdminOpsEvents() {
        document.getElementById('admin-ops-refresh-btn')?.addEventListener('click', refreshAdminOps);
        document.getElementById('create-backup-job-btn')?.addEventListener('click', createBackupJob);
        document.getElementById('restore-sql-form')?.addEventListener('submit', uploadRestoreSql);
        document.getElementById('create-archive-btn')?.addEventListener('click', createDataArchive);
        document.getElementById('archive-type-select')?.addEventListener('change', renderArchiveExtraFields);
        renderArchiveExtraFields();
    }

    async function refreshAdminOps() {
        await Promise.all([
            loadIssueReports(),
            loadBackupJobs(),
            loadRestoreUploads(),
            loadDataArchives(),
            loadRolePreviewLinks()
        ]);
    }

    async function loadIssueReports() {
        const container = document.getElementById('issue-report-list');
        if (!container) return;

        container.innerHTML = '<p class="p-6 text-center text-gray-500">載入中...</p>';

        try {
            const result = await fetchJson(`${API_BASE}/get_issue_reports.php`);
            if (!result.success) throw new Error(result.message || '讀取失敗');
            state.issueReports = sortIssueReports(result.data || []);
            renderIssueReports();
        } catch (error) {
            container.innerHTML = `<p class="p-6 text-center text-red-500">${escapeHtml(error.message)}</p>`;
        }
    }

    function renderIssueReports() {
        const container = document.getElementById('issue-report-list');
        if (!container) return;

        if (state.issueReports.length === 0) {
            container.innerHTML = '<p class="p-6 text-center text-gray-500">目前沒有問題回報</p>';
            return;
        }

        container.innerHTML = '';
        state.issueReports = sortIssueReports(state.issueReports);
        state.issueReports.slice(0, 8).forEach((report) => {
            container.appendChild(createIssueReportCard(report));
        });

        if (state.issueReports.length > 8) {
            const showMoreButton = document.createElement('button');
            showMoreButton.type = 'button';
            showMoreButton.className = 'w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-bold text-primary hover:bg-primary/5 dark:hover:bg-primary/10';
            showMoreButton.textContent = `顯示更多 (${state.issueReports.length})`;
            showMoreButton.addEventListener('click', openIssueReportsModal);
            container.appendChild(showMoreButton);
        }

        bindIssueStatusSelects(container);
    }

    function createIssueReportCard(report) {
        const card = document.createElement('article');
        const email = report.contact_email || '';
        const phone = report.contact_phone || '';
        card.className = 'rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/60 p-4';
        card.innerHTML = `
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <h4 class="font-bold text-[#111318] dark:text-white truncate" title="${escapeHtml(report.title || '未命名問題')}">${escapeHtml(report.title || '未命名問題')}</h4>
                        <div class="shrink-0">${renderStatusBadge(report.status)}</div>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <span class="whitespace-nowrap truncate max-w-[180px]" title="${escapeHtml(report.reporter_username || 'anonymous')}">${escapeHtml(report.reporter_username || 'anonymous')}</span>
                        ${report.reporter_role ? `<span class="text-gray-300 dark:text-gray-600">|</span><span class="whitespace-nowrap">${escapeHtml(report.reporter_role)}</span>` : ''}
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="whitespace-nowrap">${escapeHtml(report.created_at || '')}</span>
                    </div>
                </div>
                <div class="shrink-0 w-full md:w-36">
                    <select data-issue-id="${Number(report.id)}" class="issue-status-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        ${renderIssueStatusOptions(report.status)}
                    </select>
                </div>
            </div>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">${escapeHtml(report.description || '')}</p>
            ${(email || phone) ? `
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    ${email ? `<a href="mailto:${escapeHtml(email)}" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 font-bold text-blue-700 hover:bg-blue-100"><span class="material-symbols-outlined text-[14px]">mail</span>${escapeHtml(email)}</a>` : ''}
                    ${phone ? `<a href="tel:${escapeHtml(phone)}" class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-1 font-bold text-green-700 hover:bg-green-100"><span class="material-symbols-outlined text-[14px]">call</span>${escapeHtml(phone)}</a>` : ''}
                </div>
            ` : '<p class="mt-3 text-xs text-gray-400">未提供聯絡方式</p>'}
        `;
        return card;
    }

    function bindIssueStatusSelects(container) {
        container.querySelectorAll('.issue-status-select').forEach((select) => {
            select.addEventListener('change', updateIssueStatus);
        });
    }

    function openIssueReportsModal() {
        const modal = document.getElementById('issue-reports-modal');
        const list = document.getElementById('issue-reports-modal-list');
        if (!modal || !list) return;

        list.innerHTML = '';
        sortIssueReports(state.issueReports).forEach((report) => {
            list.appendChild(createIssueReportCard(report));
        });
        bindIssueStatusSelects(list);
        modal.classList.remove('hidden');
    }

    function closeIssueReportsModal() {
        document.getElementById('issue-reports-modal')?.classList.add('hidden');
    }

    async function updateIssueStatus(event) {
        const select = event.currentTarget;
        const issueId = Number(select.dataset.issueId);
        const nextStatus = select.value;
        const previousStatus = state.issueReports.find((report) => Number(report.id) === issueId)?.status;
        select.disabled = true;

        try {
            const result = await fetchJson(`${API_BASE}/update_issue_report.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: issueId,
                    status: nextStatus,
                    operator: getCurrentOperator()
                })
            });
            if (!result.success) throw new Error(result.message || '更新失敗');
            state.issueReports = sortIssueReports(state.issueReports.map((report) => {
                if (Number(report.id) !== issueId) return report;
                return {
                    ...report,
                    status: nextStatus,
                    updated_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
                };
            }));
            renderIssueReports();
            if (!document.getElementById('issue-reports-modal')?.classList.contains('hidden')) {
                openIssueReportsModal();
            }
        } catch (error) {
            state.issueReports = sortIssueReports(state.issueReports.map((report) => {
                if (Number(report.id) !== issueId) return report;
                return { ...report, status: previousStatus || report.status };
            }));
            alert(error.message);
            renderIssueReports();
            if (!document.getElementById('issue-reports-modal')?.classList.contains('hidden')) {
                openIssueReportsModal();
            }
        } finally {
            select.disabled = false;
        }
    }

    async function loadBackupJobs() {
        const container = document.getElementById('backup-job-list');
        if (!container) return;

        container.innerHTML = '<p class="text-sm text-gray-500">載入中...</p>';

        try {
            const result = await fetchJson(`${API_BASE}/get_backup_jobs.php`);
            if (!result.success) throw new Error(result.message || '讀取失敗');
            state.backupJobs = result.data || [];
            renderBackupJobs();
        } catch (error) {
            container.innerHTML = `<p class="text-sm text-red-500">${escapeHtml(error.message)}</p>`;
        }
    }

    function renderBackupJobs() {
        const container = document.getElementById('backup-job-list');
        if (!container) return;

        if (state.backupJobs.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">目前沒有備份工作</p>';
            return;
        }

        container.innerHTML = '';
        state.backupJobs.slice(0, 1).forEach((job) => {
            container.appendChild(createBackupJobCard(job));
        });

        if (state.backupJobs.length > 1) {
            const showMoreButton = document.createElement('button');
            showMoreButton.type = 'button';
            showMoreButton.className = 'w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-bold text-primary hover:bg-primary/5 dark:hover:bg-primary/10';
            showMoreButton.textContent = `顯示更多 (${state.backupJobs.length})`;
            showMoreButton.addEventListener('click', openBackupJobsModal);
            container.appendChild(showMoreButton);
        }
    }

    function createBackupJobCard(job) {
        const item = document.createElement('div');
        item.className = 'p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700';
        item.innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-bold text-[#111318] dark:text-white truncate" title="${escapeHtml(job.job_name || `備份 #${job.id}`)}">${escapeHtml(job.job_name || `備份 #${job.id}`)}</p>
                <div class="shrink-0">${renderBackupStatusBadge(job.status)}</div>
            </div>
            <p class="text-xs text-gray-500 mt-2">${escapeHtml(job.created_at || '')}</p>
            ${job.message ? `<p class="text-xs text-gray-500 mt-1">${escapeHtml(job.message)}</p>` : ''}
            ${job.status === 'completed' ? `<a href="${API_BASE}/download_backup_job.php?id=${Number(job.id)}" class="inline-flex items-center justify-center mt-3 px-3 py-2 rounded-md bg-primary text-white text-xs font-bold hover:bg-primary/90">下載備份</a>` : ''}
        `;
        return item;
    }

    function openBackupJobsModal() {
        const modal = document.getElementById('backup-jobs-modal');
        const list = document.getElementById('backup-jobs-modal-list');
        if (!modal || !list) return;

        list.innerHTML = '';
        state.backupJobs.forEach((job) => {
            list.appendChild(createBackupJobCard(job));
        });
        modal.classList.remove('hidden');
    }

    function closeBackupJobsModal() {
        document.getElementById('backup-jobs-modal')?.classList.add('hidden');
    }

    async function createBackupJob() {
        const button = document.getElementById('create-backup-job-btn');
        const tempJob = {
            id: `temp-${Date.now()}`,
            job_name: '正在建立備份...',
            status: 'running',
            created_at: new Date().toLocaleString('zh-TW', { hour12: false }),
            message: '處理中，正在產生 ZIP 備份檔。'
        };

        state.backupJobs = [tempJob, ...state.backupJobs];
        renderBackupJobs();

        if (button) {
            button.disabled = true;
            button.classList.add('opacity-60', 'cursor-not-allowed');
        }

        try {
            const result = await fetchJson(`${API_BASE}/create_backup_job.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ operator: getCurrentOperator() })
            });
            if (!result.success) throw new Error(result.message || '建立失敗');
            await loadBackupJobs();
        } catch (error) {
            tempJob.status = 'failed';
            tempJob.job_name = '備份建立失敗';
            tempJob.message = error.message;
            state.backupJobs = state.backupJobs.map((job) => job.id === tempJob.id ? tempJob : job);
            renderBackupJobs();
            alert(error.message);
        } finally {
            if (button) {
                button.disabled = false;
                button.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        }
    }

    async function loadRestoreUploads() {
        const container = document.getElementById('restore-upload-list');
        if (!container) return;

        container.innerHTML = '<p class="text-sm text-gray-500">載入中...</p>';

        try {
            const result = await fetchJson(`${API_BASE}/get_restore_uploads.php`);
            if (!result.success) throw new Error(result.message || '讀取還原紀錄失敗');
            state.restoreUploads = result.data || [];
            if (result.phpmyadmin_url) {
                state.phpMyAdminUrl = result.phpmyadmin_url;
                const link = document.getElementById('open-phpmyadmin-import-link');
                if (link) link.href = state.phpMyAdminUrl;
            }
            renderRestoreUploads();
        } catch (error) {
            container.innerHTML = `<p class="text-sm text-red-500">${escapeHtml(error.message)}</p>`;
        }
    }

    function renderRestoreUploads() {
        const container = document.getElementById('restore-upload-list');
        if (!container) return;

        if (state.restoreUploads.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">目前沒有 SQL 上傳紀錄。</p>';
            return;
        }

        container.innerHTML = '';
        state.restoreUploads.slice(0, 1).forEach((upload) => {
            container.appendChild(createRestoreUploadCard(upload));
        });

        if (state.restoreUploads.length > 1) {
            const showMoreButton = document.createElement('button');
            showMoreButton.type = 'button';
            showMoreButton.className = 'w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-bold text-primary hover:bg-primary/5 dark:hover:bg-primary/10';
            showMoreButton.textContent = `顯示更多 (${state.restoreUploads.length})`;
            showMoreButton.addEventListener('click', openRestoreUploadsModal);
            container.appendChild(showMoreButton);
        }
    }

    function createRestoreUploadCard(upload) {
        const item = document.createElement('div');
        item.className = 'p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700';
        item.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-[#111318] dark:text-white truncate" title="${escapeHtml(upload.original_name || '')}">${escapeHtml(upload.original_name || 'SQL 還原檔')}</p>
                    <p class="text-xs text-gray-500 mt-1">${escapeHtml(upload.created_at || '')}</p>
                </div>
                <span class="shrink-0 px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">待匯入</span>
            </div>
            <p class="text-xs text-gray-500 mt-2 break-all">檔案位置：${escapeHtml(upload.stored_path || '')}</p>
            <a href="${state.phpMyAdminUrl}" target="_blank" rel="noopener" class="inline-flex items-center justify-center mt-3 px-3 py-2 rounded-md bg-primary text-white text-xs font-bold hover:bg-primary/90">開啟 phpMyAdmin 匯入</a>
        `;
        return item;
    }

    function openRestoreUploadsModal() {
        const modal = document.getElementById('restore-uploads-modal');
        const list = document.getElementById('restore-uploads-modal-list');
        if (!modal || !list) return;

        list.innerHTML = '';
        state.restoreUploads.forEach((upload) => {
            list.appendChild(createRestoreUploadCard(upload));
        });
        modal.classList.remove('hidden');
    }

    function closeRestoreUploadsModal() {
        document.getElementById('restore-uploads-modal')?.classList.add('hidden');
    }

    async function uploadRestoreSql(event) {
        event.preventDefault();

        const fileInput = document.getElementById('restore-sql-file');
        const button = document.getElementById('upload-restore-sql-btn');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('請先選擇 .sql 檔案。');
            return;
        }

        const formData = new FormData();
        formData.append('restore_sql', fileInput.files[0]);

        if (button) {
            button.disabled = true;
            button.textContent = '上傳中...';
            button.classList.add('opacity-60', 'cursor-not-allowed');
        }

        try {
            const response = await fetch(`${API_BASE}/upload_restore_sql.php`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'SQL 上傳失敗');

            fileInput.value = '';
            if (result.data?.phpmyadmin_url) {
                state.phpMyAdminUrl = result.data.phpmyadmin_url;
            }
            await loadRestoreUploads();
            window.open(state.phpMyAdminUrl, '_blank', 'noopener');
            alert(`SQL 已上傳。請在 phpMyAdmin 匯入：${result.data?.absolute_path || result.data?.stored_path || ''}`);
        } catch (error) {
            alert(error.message);
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = '上傳 SQL';
                button.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        }
    }

    function renderArchiveExtraFields() {
        const type = document.getElementById('archive-type-select')?.value || 'resolved_issue_reports';
        const container = document.getElementById('archive-extra-fields');
        if (!container) return;

        state.archiveCandidates = [];

        if (type === 'students_over_years') {
            const defaultCutoff = new Date().getFullYear() - 1911 - 4;
            container.innerHTML = `
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300" for="archive-student-cutoff-year">入學民國年小於等於</label>
                <div class="flex gap-2">
                    <input id="archive-student-cutoff-year" type="number" min="1" value="${defaultCutoff}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <button id="load-student-archive-candidates-btn" type="button" class="shrink-0 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 text-sm font-bold text-primary hover:bg-primary/5">載入</button>
                </div>
                <div id="archive-student-candidates" class="max-h-48 overflow-y-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-500">
                    按「載入」查看可封存學生。
                </div>
            `;
            document.getElementById('load-student-archive-candidates-btn')?.addEventListener('click', loadStudentArchiveCandidates);
            return;
        }

        if (type === 'applications_by_year') {
            container.innerHTML = `
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300" for="archive-application-year">申請學年度</label>
                <input id="archive-application-year" type="text" inputmode="numeric" placeholder="例如：113" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            `;
            return;
        }

        if (type === 'expired_scholarships') {
            container.innerHTML = '<p class="text-xs text-gray-500">會封存申請截止日早於今天，或已停用的獎學金項目。</p>';
            return;
        }

        if (type === 'old_announcements') {
            container.innerHTML = '<p class="text-xs text-gray-500">會封存顯示日期早於今天的首頁公告。</p>';
            return;
        }

        container.innerHTML = '<p class="text-xs text-gray-500">會封存狀態為已解決的問題回報。</p>';
    }

    async function loadStudentArchiveCandidates() {
        const container = document.getElementById('archive-student-candidates');
        const cutoffInput = document.getElementById('archive-student-cutoff-year');
        if (!container || !cutoffInput) return;

        container.innerHTML = '<p class="text-sm text-gray-500">載入中...</p>';

        try {
            const cutoff = encodeURIComponent(cutoffInput.value || '');
            const result = await fetchJson(`${API_BASE}/get_archive_candidates.php?archive_type=students_over_years&cutoff_year=${cutoff}`);
            if (!result.success) throw new Error(result.message || '讀取候選學生失敗');
            state.archiveCandidates = result.data || [];
            renderStudentArchiveCandidates();
        } catch (error) {
            container.innerHTML = `<p class="text-sm text-red-500">${escapeHtml(error.message)}</p>`;
        }
    }

    function renderStudentArchiveCandidates() {
        const container = document.getElementById('archive-student-candidates');
        if (!container) return;

        if (state.archiveCandidates.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">沒有符合條件的學生。</p>';
            return;
        }

        container.innerHTML = `
            <label class="flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-300 mb-2">
                <input id="archive-select-all-students" type="checkbox" checked>
                全選 ${state.archiveCandidates.length} 位學生
            </label>
            <div class="space-y-2">
                ${state.archiveCandidates.map((student) => `
                    <label class="flex items-start gap-2 rounded-md bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 p-2">
                        <input type="checkbox" class="archive-student-checkbox mt-1" value="${escapeHtml(student.username)}" checked>
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-[#111318] dark:text-white">${escapeHtml(student.real_name || student.username)}</span>
                            <span class="block text-xs text-gray-500 truncate">${escapeHtml(student.username)} · 入學 ${escapeHtml(student.admission_year || '')} · ${escapeHtml(student.department || '')}</span>
                        </span>
                    </label>
                `).join('')}
            </div>
        `;

        document.getElementById('archive-select-all-students')?.addEventListener('change', (event) => {
            container.querySelectorAll('.archive-student-checkbox').forEach((checkbox) => {
                checkbox.checked = event.currentTarget.checked;
            });
        });
    }

    async function loadDataArchives() {
        const container = document.getElementById('data-archive-list');
        if (!container) return;

        container.innerHTML = '<p class="text-sm text-gray-500">載入中...</p>';

        try {
            const result = await fetchJson(`${API_BASE}/get_data_archives.php`);
            if (!result.success) throw new Error(result.message || '讀取封存紀錄失敗');
            state.dataArchives = result.data || [];
            renderDataArchives();
        } catch (error) {
            container.innerHTML = `<p class="text-sm text-red-500">${escapeHtml(error.message)}</p>`;
        }
    }

    function renderDataArchives() {
        const container = document.getElementById('data-archive-list');
        if (!container) return;

        if (state.dataArchives.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500">目前沒有資料封存紀錄。</p>';
            return;
        }

        container.innerHTML = '';
        state.dataArchives.slice(0, 1).forEach((archive) => {
            container.appendChild(createDataArchiveCard(archive));
        });

        if (state.dataArchives.length > 1) {
            const showMoreButton = document.createElement('button');
            showMoreButton.type = 'button';
            showMoreButton.className = 'w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-bold text-primary hover:bg-primary/5 dark:hover:bg-primary/10';
            showMoreButton.textContent = `顯示更多 (${state.dataArchives.length})`;
            showMoreButton.addEventListener('click', openDataArchivesModal);
            container.appendChild(showMoreButton);
        }
    }

    function createDataArchiveCard(archive) {
        const item = document.createElement('div');
        const hasDownloaded = Boolean(archive.downloaded_at);
        item.className = 'p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700';
        item.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-[#111318] dark:text-white truncate" title="${escapeHtml(archive.archive_name || '')}">${escapeHtml(archive.archive_name || '資料封存')}</p>
                    <p class="text-xs text-gray-500 mt-1">${escapeHtml(archive.created_at || '')}</p>
                </div>
                <span class="shrink-0 px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">${Number(archive.record_count || 0)} 筆</span>
            </div>
            <p class="text-xs text-gray-500 mt-2">來源：${escapeHtml(archive.source_table || '')} · ${formatFileSize(archive.file_size || 0)}</p>
            ${archive.downloaded_at ? `<p class="mt-2 text-xs text-gray-500">已於 ${escapeHtml(archive.downloaded_at)} 下載封存檔</p>` : '<p class="mt-2 text-xs text-amber-600 font-bold">移除前請先下載封存檔</p>'}
            ${archive.original_deleted_at ? `
                <p class="mt-3 text-xs font-bold text-red-600">原資料已於 ${escapeHtml(archive.original_deleted_at)} 移除，共 ${Number(archive.original_deleted_count || 0)} 筆。</p>
            ` : ''}
            ${archive.file_exists ? `
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="${API_BASE}/download_data_archive.php?id=${Number(archive.id)}" data-archive-download-id="${Number(archive.id)}" class="inline-flex items-center justify-center px-3 py-2 rounded-md bg-primary text-white text-xs font-bold hover:bg-primary/90">下載封存檔</a>
                    ${!archive.original_deleted_at ? `<button type="button" data-archive-delete-id="${Number(archive.id)}" data-archive-downloaded="${hasDownloaded ? '1' : '0'}" class="inline-flex items-center justify-center px-3 py-2 rounded-md text-xs font-bold ${hasDownloaded ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-200 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-400'}">移除原資料</button>` : ''}
                </div>
            ` : '<p class="mt-3 text-xs text-red-500">封存檔案不存在</p>'}
        `;
        item.querySelector('[data-archive-download-id]')?.addEventListener('click', markArchiveDownloadedInUi);
        item.querySelector('[data-archive-delete-id]')?.addEventListener('click', deleteArchivedOriginalData);
        return item;
    }

    function openDataArchivesModal() {
        const modal = document.getElementById('data-archives-modal');
        const list = document.getElementById('data-archives-modal-list');
        if (!modal || !list) return;

        list.innerHTML = '';
        state.dataArchives.forEach((archive) => {
            list.appendChild(createDataArchiveCard(archive));
        });
        modal.classList.remove('hidden');
    }

    function closeDataArchivesModal() {
        document.getElementById('data-archives-modal')?.classList.add('hidden');
    }

    async function deleteArchivedOriginalData(event) {
        const archiveId = Number(event.currentTarget.dataset.archiveDeleteId);
        const archive = state.dataArchives.find((item) => Number(item.id) === archiveId);
        if (event.currentTarget.dataset.archiveDownloaded !== '1' && !archive?.downloaded_at) {
            alert('請先行下載封存檔');
            return;
        }
        const name = archive?.archive_name || `封存 #${archiveId}`;
        const confirmed = confirm(`確定要從資料庫移除「${name}」對應的原資料嗎？\n\n請先確認你已下載封存檔。這個動作會真的刪除資料。`);
        if (!confirmed) return;

        const button = event.currentTarget;
        button.disabled = true;
        button.textContent = '移除中...';
        button.classList.add('opacity-60', 'cursor-not-allowed');

        try {
            const result = await fetchJson(`${API_BASE}/delete_archived_data.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: archiveId, operator: getCurrentOperator() })
            });
            if (!result.success) throw new Error(result.message || '移除原資料失敗');
            await loadDataArchives();
            if (!document.getElementById('data-archives-modal')?.classList.contains('hidden')) {
                openDataArchivesModal();
            }
            alert(`原資料已移除，共 ${Number(result.data?.deleted_count || 0)} 筆。`);
        } catch (error) {
            alert(error.message);
            button.disabled = false;
            button.textContent = '移除原資料';
            button.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    }

    function markArchiveDownloadedInUi(event) {
        const archiveId = Number(event.currentTarget.dataset.archiveDownloadId);
        const timestamp = new Date().toLocaleString('zh-TW', { hour12: false });
        state.dataArchives = state.dataArchives.map((archive) => {
            if (Number(archive.id) !== archiveId) return archive;
            return { ...archive, downloaded_at: archive.downloaded_at || timestamp };
        });
        setTimeout(() => {
            renderDataArchives();
            if (!document.getElementById('data-archives-modal')?.classList.contains('hidden')) {
                openDataArchivesModal();
            }
        }, 400);
    }

    async function createDataArchive() {
        const button = document.getElementById('create-archive-btn');
        const payload = buildArchivePayload();
        if (!payload) return;

        if (button) {
            button.disabled = true;
            button.textContent = '封存中...';
            button.classList.add('opacity-60', 'cursor-not-allowed');
        }

        try {
            const result = await fetchJson(`${API_BASE}/create_data_archive.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (!result.success) throw new Error(result.message || '封存失敗');
            await loadDataArchives();
            alert(`封存完成，共 ${Number(result.data?.record_count || 0)} 筆。`);
        } catch (error) {
            alert(error.message);
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = '封存';
                button.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        }
    }

    function buildArchivePayload() {
        const archiveType = document.getElementById('archive-type-select')?.value || 'resolved_issue_reports';
        const payload = {
            archive_type: archiveType,
            operator: getCurrentOperator()
        };

        if (archiveType === 'students_over_years') {
            const cutoff = document.getElementById('archive-student-cutoff-year')?.value || '';
            const selected = Array.from(document.querySelectorAll('.archive-student-checkbox:checked')).map((item) => item.value);
            payload.cutoff_year = cutoff;
            payload.selected_usernames = selected;
            if (state.archiveCandidates.length > 0 && selected.length === 0) {
                alert('請至少選擇一位要封存的學生。');
                return null;
            }
        }

        if (archiveType === 'applications_by_year') {
            const academicYear = (document.getElementById('archive-application-year')?.value || '').trim();
            if (!academicYear) {
                alert('請輸入要封存的申請學年度。');
                return null;
            }
            payload.academic_year = academicYear;
        }

        return payload;
    }

    async function loadRolePreviewLinks() {
        const container = document.getElementById('role-preview-list');
        if (!container) return;

        try {
            const result = await fetchJson(`${API_BASE}/get_role_preview_links.php`);
            if (!result.success) throw new Error(result.message || '讀取失敗');
            state.roleLinks = result.data || [];
            renderRolePreviewLinks();
        } catch (error) {
            container.innerHTML = `<p class="text-sm text-red-500">${escapeHtml(error.message)}</p>`;
        }
    }

    function renderRolePreviewLinks() {
        const container = document.getElementById('role-preview-list');
        if (!container) return;

        container.innerHTML = '';
        state.roleLinks.forEach((link) => {
            const anchor = document.createElement('a');
            anchor.href = link.url;
            anchor.target = '_blank';
            anchor.rel = 'noopener noreferrer';
            anchor.className = 'flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm font-bold text-[#111318] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700';
            anchor.innerHTML = `<span>${escapeHtml(link.label)}</span><span class="material-symbols-outlined text-base">open_in_new</span>`;
            container.appendChild(anchor);
        });
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, options);
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch {
            throw new Error('API 回傳格式錯誤');
        }

        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return data;
    }

    function renderIssueStatusOptions(currentStatus) {
        const statuses = [
            ['open', '待處理'],
            ['processing', '處理中'],
            ['resolved', '已解決']
        ];

        return statuses.map(([value, label]) => {
            const selected = value === currentStatus ? 'selected' : '';
            return `<option value="${value}" ${selected}>${label}</option>`;
        }).join('');
    }

    function renderStatusBadge(status) {
        const map = {
            open: ['待處理', 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
            processing: ['處理中', 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'],
            resolved: ['已解決', 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300']
        };
        const [label, classes] = map[status] || [status || '未知', 'bg-gray-100 text-gray-700'];
        return `<span class="px-2 py-1 rounded-full text-xs font-bold ${classes}">${escapeHtml(label)}</span>`;
    }

    function sortIssueReports(reports) {
        const order = { open: 0, processing: 1, resolved: 2 };
        return [...reports].sort((a, b) => {
            const statusDiff = (order[a.status] ?? 99) - (order[b.status] ?? 99);
            if (statusDiff !== 0) return statusDiff;

            const aTime = new Date(a.updated_at || a.created_at || 0).getTime();
            const bTime = new Date(b.updated_at || b.created_at || 0).getTime();
            return bTime - aTime;
        });
    }

    function renderBackupStatusBadge(status) {
        const map = {
            queued: ['排程中', 'bg-gray-100 text-gray-700'],
            running: ['處理中', 'bg-blue-100 text-blue-700'],
            completed: ['已完成', 'bg-green-100 text-green-700'],
            failed: ['失敗', 'bg-red-100 text-red-700']
        };
        const [label, classes] = map[status] || [status || '未知', 'bg-gray-100 text-gray-700'];
        return `<span class="px-2 py-1 rounded-full text-xs font-bold ${classes}">${escapeHtml(label)}</span>`;
    }

    function getCurrentOperator() {
        try {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            return user.username || user.name || user.real_name || 'System Admin';
        } catch {
            return 'System Admin';
        }
    }

    function formatFileSize(bytes) {
        const size = Number(bytes || 0);
        if (size < 1024) return `${size} B`;
        if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
        return `${(size / 1024 / 1024).toFixed(1)} MB`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();

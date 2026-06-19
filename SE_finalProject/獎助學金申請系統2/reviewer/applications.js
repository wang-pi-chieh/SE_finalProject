document.addEventListener('DOMContentLoaded', () => {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        window.location.href = '../login.html';
        return;
    }

    const applicationsTableBody = document.getElementById('applications-table-body');
    const applicationsSection = applicationsTableBody ? applicationsTableBody.closest('.animate-on-scroll') : null;
    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    const scholarshipFilter = document.getElementById('scholarship-filter');
    const paginationControls = document.getElementById('footer-pagination'); // Ensure this ID exists or add it
    const finalAwardsSection = document.getElementById("tab-final-awards-content");

    let allApplications = [];
    let currentTab = 'pending';
    let currentPage = 1;
    const pageSize = 6;
    let filteredApps = [];

    // Global function for tab switching
    window.switchTab = function (tab) {
        currentTab = tab;
        currentPage = 1; // Reset to page 1

        // Update UI
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'text-primary', 'border-primary');
            btn.classList.add('text-gray-500', 'border-transparent');
        });

        const activeBtn = document.getElementById(`tab-${tab}`);
        if (activeBtn) {
            activeBtn.classList.add('active', 'text-primary', 'border-primary');
            activeBtn.classList.remove('text-gray-500', 'border-transparent');
        }

        // Update URL
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('tab', tab);
        window.history.pushState({}, '', newUrl);

        // Update Header Navigation Sync
        if (window.updateHeaderActiveState) {
            window.updateHeaderActiveState(tab);
        }
        const disbursement = document.getElementById("tab-disbursement-content");

        if (tab === "disbursement") {
            if (disbursement) disbursement.classList.remove("hidden");
            if (finalAwardsSection) finalAwardsSection.classList.add("hidden");
            if (applicationsSection) applicationsSection.classList.add("hidden");
            loadDisbursement();
            return;
        } else if (tab === "final-awards") {
            if (disbursement) disbursement.classList.add("hidden");
            if (finalAwardsSection) finalAwardsSection.classList.remove("hidden");
            if (applicationsSection) applicationsSection.classList.add("hidden");
            loadFinalAwards();
            return;
        } else {
            if (disbursement) disbursement.classList.add("hidden");
            if (finalAwardsSection) finalAwardsSection.classList.add("hidden");
            if (applicationsSection) applicationsSection.classList.remove("hidden");
        }
        filterApplications();
    };

    function fetchApplications() {
        // Show loading state
        applicationsTableBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">載入中...</td></tr>';

        // Add timestamp to prevent caching
        fetch(`../api/get_reviewer_applications.php?provider_username=${user.username}&t=${new Date().getTime()}`)
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    allApplications = result.data;
                    allApplications = result.data;
                    // populateScholarshipFilter(); // Moved to init
                    filterApplications();
                    filterApplications();
                } else {
                    console.error('Error fetching applications:', result.message);
                    applicationsTableBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500">載入失敗</td></tr>';
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                applicationsTableBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500">系統錯誤</td></tr>';
            });
    }

    function populateScholarshipFilter() {
        // Fetch all active scholarships from database
        fetch('../api/get_scholarships.php')
            .then(res => res.json())
            .then(result => {
                if (result.success && result.data) {
                    scholarshipFilter.innerHTML = '<option value="all">所有獎學金項目</option>';
                    result.data.forEach(sch => {
                        const option = document.createElement('option');
                        option.value = sch.name; // Use name for filtering to match existing logic
                        option.textContent = sch.name;
                        scholarshipFilter.appendChild(option);
                    });
                    // Restore previous selection if any
                    // const saved = state.filters.scholarship; // If we want to persist
                }
            })
            .catch(err => console.error('Error fetching scholarships:', err));
    }
    
    function filterApplications() {
        const query = searchInput.value.toLowerCase().trim();
        const status = statusFilter.value;
        const scholarship = scholarshipFilter.value;

        filteredApps = allApplications.filter(app => {
            let matchTab = false;
            // Tab Logic
            if (currentTab === 'pending') {
                // Pending (3), Revision (2), Reviewing(3 merged)
                matchTab = [3, 2].includes(parseInt(app.status, 10));
            } else { // history: approved (1), rejected (0)
                matchTab = [1, 0].includes(parseInt(app.status, 10));
            }

            // Search
            const matchText = (app.student_name || '').toLowerCase().includes(query) ||
                (app.student_username || '').toLowerCase().includes(query);

            // Status dropdown
            const s = String(app.status);
            const statusMap = {
                pending: ['3'],
                approved: ['1'],
                rejected: ['0'],
                needs_action: ['2']
            };
            const matchStatus = status === 'all' || (statusMap[status] || []).includes(s);

            // Scholarship
            const matchScholarship = scholarship === 'all' || app.scholarship_name === scholarship;

            return matchTab && matchText && matchStatus && matchScholarship;
        });

        renderApplications();
        renderPagination();
    }

    // Event Listeners
    searchInput.addEventListener('input', () => { currentPage = 1; filterApplications(); });
    statusFilter.addEventListener('change', () => { currentPage = 1; filterApplications(); });
    scholarshipFilter.addEventListener('change', () => { currentPage = 1; filterApplications(); });

    function renderApplications() {
        applicationsTableBody.innerHTML = '';

        if (filteredApps.length === 0) {
            applicationsTableBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">沒有符合條件的申請案件</td></tr>';
            return;
        }

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        const pageData = filteredApps.slice(start, end);

        pageData.forEach(app => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-gray-50 dark:hover:bg-[#2a3441] transition-colors group";

            const initials = (app.student_username || '??').substring(0, 2).toUpperCase();
            const colors = ['blue', 'purple', 'teal', 'indigo', 'rose'];
            const color = colors[(app.student_username || '').length % colors.length];
            const statusHtml = getStatusBadge(app.status);

            tr.innerHTML = `
                <td class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="size-8 rounded-full bg-${color}-100 dark:bg-${color}-900 flex items-center justify-center text-${color}-700 dark:text-${color}-200 font-bold text-xs shrink-0">
                            ${initials}
                        </div>
                        <div class="min-w-0">
                            <p class="text-[#111318] dark:text-white font-medium text-sm truncate">${app.student_name || app.student_username}</p>
                            <p class="text-[#616f89] dark:text-gray-500 text-xs truncate">${app.student_username} • ${app.department || '未知系所'}</p>
                        </div>
                    </div>
                </td>
                <td class="p-4">
                    <p class="text-[#111318] dark:text-white text-sm line-clamp-1">${app.scholarship_name}</p>
                </td>
                <td class="p-4">
                    <p class="text-[#616f89] dark:text-gray-400 text-sm whitespace-nowrap">${app.application_date}</p>
                </td>
                <td class="p-4 whitespace-nowrap">
                    ${statusHtml}
                </td>
                <td class="p-4 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2 opacity-100 group-hover:opacity-100 transition-opacity">
                        <a href="application-review.html?id=${app.application_id}"
                            class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-medium hover:bg-primary-dark transition-colors">
                            ${currentTab === 'history' ? '查看詳情' : '開始審查'}
                        </a>
                    </div>
                </td>
            `;
            applicationsTableBody.appendChild(tr);
        });
    }

    function renderPagination() {
        const container = document.getElementById('footer-pagination'); // Need to ensure it exists in HTML
        if (!container) return; // If HTML structure differs, might need to insert it differently

        container.innerHTML = '';
        const total = filteredApps.length;
        const totalPages = Math.ceil(total / pageSize);

        // Info text
        const start = total === 0 ? 0 : (currentPage - 1) * pageSize + 1;
        const end = Math.min(currentPage * pageSize, total);

        const infoP = document.createElement('p');
        infoP.className = 'text-sm text-[#616f89] dark:text-gray-400';
        infoP.textContent = `顯示第 ${start} 至 ${end} 筆，共 ${total} 筆`;

        const controlsDiv = document.createElement('div');
        controlsDiv.className = 'flex items-center gap-2 ml-auto'; // Push to right if flex parent

        // Prev
        const prevBtn = createPageBtn('chevron_left', currentPage > 1, () => {
            if (currentPage > 1) { currentPage--; renderApplications(); renderPagination(); }
        }, true);
        controlsDiv.appendChild(prevBtn);

        // Pages
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const btn = createPageBtn(i, true, () => { currentPage = i; renderApplications(); renderPagination(); });
                if (i === currentPage) {
                    btn.classList.add('bg-primary', 'text-white');
                    btn.classList.remove('border', 'text-[#111318]');
                }
                controlsDiv.appendChild(btn);
            } else if (
                (i === currentPage - 2 && i > 1) ||
                (i === currentPage + 2 && i < totalPages)
            ) {
                const span = document.createElement('span');
                span.className = 'text-gray-400 px-1';
                span.textContent = '...';
                controlsDiv.appendChild(span);
            }
        }

        // Next
        const nextBtn = createPageBtn('chevron_right', currentPage < totalPages, () => {
            if (currentPage < totalPages) { currentPage++; renderApplications(); renderPagination(); }
        }, true);
        controlsDiv.appendChild(nextBtn);

        container.appendChild(infoP);
        container.appendChild(controlsDiv);
    }

    function createPageBtn(content, enabled, onClick, isIcon = false) {
        const btn = document.createElement('button');
        const baseClass = `rounded-lg border border-[#dbdfe6] dark:border-[#3f4a5a] text-[#616f89] dark:text-gray-400 font-medium transition-colors`;
        if (isIcon) {
            btn.className = `p-2 ${baseClass} ${enabled ? 'hover:bg-gray-50 dark:hover:bg-[#2a3441]' : 'opacity-50 cursor-not-allowed'}`;
            btn.innerHTML = `<span class="material-symbols-outlined text-[18px]">${content}</span>`;
        } else {
            btn.className = `size-8 text-sm ${baseClass} ${enabled ? 'hover:bg-gray-50 dark:hover:bg-[#2a3441] text-[#111318] dark:text-white' : 'opacity-50'}`;
            btn.textContent = content;
        }
        if (enabled) btn.onclick = onClick; else btn.disabled = true;
        return btn;
    }

    function getStatusBadge(status) {
        // ... (reuse existing logic or mapping)
        const s = parseInt(status, 10);
        switch (s) {
            case 3:
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300"><span class="size-1.5 rounded-full bg-orange-500"></span>待審核</span>`;
            case 1:
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300"><span class="size-1.5 rounded-full bg-green-500"></span>已核准</span>`;
            case 0:
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300"><span class="size-1.5 rounded-full bg-red-500"></span>已駁回</span>`;
            case 2:
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300"><span class="size-1.5 rounded-full bg-yellow-500"></span>需補件</span>`;
            default:
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300">狀態:${status}</span>`;
        }
    }
    
    async function loadDisbursement() {
        const tbody = document.getElementById("disbursement-table-body");
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="p-4 text-center text-gray-400">
                    載入撥款資料中...
                </td>
            </tr>
        `;

        try {
            const response = await fetch(`../api/reviewer/get_disbursements.php?provider_username=${encodeURIComponent(user.username)}&t=${Date.now()}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || '無法取得撥款資料');
            }

            renderDisbursements(result.data || []);
        } catch (err) {
            console.error('Error fetching disbursements:', err);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="p-4 text-center text-red-500">
                        ${escapeHtml(err.message || '撥款資料載入失敗')}
                    </td>
                </tr>
            `;
        }
    }

    function renderDisbursements(list) {
        const tbody = document.getElementById("disbursement-table-body");
        if (!tbody) return;

        if (list.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">
                        目前沒有已核准且待撥款的申請。
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = list.map(item => {
            const isPaid = item.status === 'paid';
            const isFailed = item.status === 'failed';
            const action = isPaid
                ? '<span class="text-xs text-gray-400">已完成</span>'
                : `<button class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-medium hover:bg-blue-700 transition-colors" data-disbursement-id="${escapeHtml(item.id)}" data-disbursement-status="paid">標記已撥款</button>`;

            return `
                <tr class="border-t border-[#e5e7eb] dark:border-[#2d3748]">
                    <td class="p-3">
                        <div class="font-medium text-sm">${escapeHtml(item.student_name || item.student_username || '-')}</div>
                        <div class="text-xs text-gray-500">${escapeHtml(item.student_username || '')}</div>
                    </td>
                    <td class="p-3 text-sm">${escapeHtml(item.scholarship_name || '-')}</td>
                    <td class="p-3 text-sm">${escapeHtml(item.amount || '-')}</td>
                    <td class="p-3">${formatDisbursementStatus(item.status, isFailed)}</td>
                    <td class="p-3 text-right">${action}</td>
                </tr>
            `;
        }).join('');

        tbody.querySelectorAll('[data-disbursement-id]').forEach(button => {
            button.addEventListener('click', () => {
                updateDisbursementStatus(button.dataset.disbursementId, button.dataset.disbursementStatus);
            });
        });
    }

    async function updateDisbursementStatus(id, status) {
        if (!id || !status) return;
        const tbody = document.getElementById("disbursement-table-body");

        try {
            const response = await fetch('../api/reviewer/update_disbursement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: Number(id),
                    status,
                    provider_username: user.username
                })
            });
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || '撥款狀態更新失敗');
            }

            loadDisbursement();
        } catch (err) {
            console.error('Error updating disbursement:', err);
            if (tbody) {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="5" class="p-4 text-center text-red-500">${escapeHtml(err.message || '撥款狀態更新失敗')}</td>`;
                tbody.prepend(row);
            }
        }
    }

    async function loadFinalAwards() {
        const tbody = document.getElementById("final-awards-table-body");
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">載入最終名單中...</td></tr>';

        try {
            const response = await fetch(`../api/reviewer/get_final_award_list.php?provider_username=${encodeURIComponent(user.username)}&t=${Date.now()}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || '無法取得最終名單');
            }

            renderFinalAwards(result.data || []);
        } catch (err) {
            console.error('Error fetching final awards:', err);
            tbody.innerHTML = `<tr><td colspan="6" class="p-4 text-center text-red-500">${escapeHtml(err.message || '最終名單載入失敗')}</td></tr>`;
        }
    }

    function renderFinalAwards(list) {
        const tbody = document.getElementById("final-awards-table-body");
        if (!tbody) return;

        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-500">目前沒有已核准且可排序的申請。</td></tr>';
            return;
        }

        tbody.innerHTML = list.map(item => {
            const resultText = item.result === 'selected' ? '錄取' : '備取';
            const resultClass = item.result === 'selected'
                ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300';
            const confirmed = item.confirmed_at
                ? `已確認 ${escapeHtml(item.confirmed_at)}`
                : '尚未確認';
            return `
                <tr class="border-t border-[#e5e7eb] dark:border-[#2d3748]">
                    <td class="p-3 text-sm font-bold">#${Number(item.rank_no || 0)}</td>
                    <td class="p-3">
                        <div class="font-medium text-sm">${escapeHtml(item.student_name || item.student_username || '-')}</div>
                        <div class="text-xs text-gray-500">${escapeHtml(item.student_username || '')}</div>
                    </td>
                    <td class="p-3 text-sm">
                        <div>${escapeHtml(item.scholarship_name || '-')}</div>
                        <div class="text-xs text-gray-500">名額 ${Number(item.quota || 0)} 人</div>
                    </td>
                    <td class="p-3 text-sm">
                        <div class="font-bold">${Number(item.final_score || 0).toFixed(2)}</div>
                        <div class="text-xs text-gray-500">${escapeHtml(item.score_missing ? '缺少審查評分' : ((item.review_stage_labels || []).join('、') || '尚無階段紀錄'))}</div>
                    </td>
                    <td class="p-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${resultClass}">${resultText}</span>
                    </td>
                    <td class="p-3 text-sm text-gray-500">${confirmed}</td>
                </tr>
            `;
        }).join('');
    }

    async function confirmFinalAwards() {
        const button = document.getElementById("confirm-final-awards-btn");
        if (!button) return;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = '確認中...';

        try {
            const response = await fetch('../api/reviewer/confirm_final_award_list.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ provider_username: user.username })
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || '最終名單確認失敗');
            }
            renderFinalAwards(result.data || []);
            alert(`最終名單已確認，共 ${Number(result.saved_count || 0)} 筆。`);
        } catch (err) {
            console.error('Error confirming final awards:', err);
            alert(err.message || '最終名單確認失敗');
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    function formatDisbursementStatus(status) {
        const labels = {
            pending: ['待撥款', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'],
            paid: ['已撥款', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
            failed: ['撥款失敗', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300']
        };
        const [label, classes] = labels[status] || ['待撥款', labels.pending[1]];
        return `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${classes}">${label}</span>`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initialize
    fetchApplications();
    populateScholarshipFilter(); // Fetch filters dynamic from DB
    const urlParams = new URLSearchParams(window.location.search);
    document.getElementById("confirm-final-awards-btn")?.addEventListener("click", confirmFinalAwards);

    const initialTab = urlParams.get('tab');
    if (initialTab && (initialTab === 'pending' || initialTab === 'history' || initialTab === 'disbursement' || initialTab === 'final-awards')) {
        switchTab(initialTab);
    }
});

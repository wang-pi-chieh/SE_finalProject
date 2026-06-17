(function () {
    if (window.__issueReportWidgetLoaded) return;
    window.__issueReportWidgetLoaded = true;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountIssueReportWidget);
    } else {
        mountIssueReportWidget();
    }

    function mountIssueReportWidget() {
        if (document.getElementById('issue-report-widget')) return;

        const wrapper = document.createElement('div');
        wrapper.id = 'issue-report-widget';
        wrapper.innerHTML = `
            <button id="issue-report-open-btn" type="button"
                class="fixed right-6 bottom-6 z-[9998] inline-flex items-center gap-2 rounded-full bg-primary px-4 py-3 text-sm font-bold text-white shadow-lg hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <span class="material-symbols-outlined text-[20px]">feedback</span>
                問題回報
                <span id="issue-report-notification-badge" class="hidden ml-1 rounded-full bg-red-500 px-2 py-0.5 text-xs text-white"></span>
            </button>

            <div id="issue-report-notification-toast" class="fixed right-6 bottom-24 z-[9998] hidden w-[320px] rounded-xl border border-blue-100 bg-white p-4 shadow-xl dark:border-blue-900 dark:bg-[#1e2634]">
                <div class="flex items-start gap-3">
                    <div class="rounded-full bg-blue-100 p-2 text-primary">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">問題回報狀態更新</p>
                        <p id="issue-report-notification-text" class="mt-1 text-sm text-gray-600 dark:text-gray-300"></p>
                        <button id="issue-report-notification-read-btn" type="button" class="mt-3 text-xs font-bold text-primary hover:underline">知道了</button>
                    </div>
                </div>
            </div>

            <div id="issue-report-modal" class="fixed inset-0 z-[9999] hidden">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" data-issue-report-close></div>
                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="w-full max-w-lg overflow-hidden rounded-xl bg-white dark:bg-[#1e2634] shadow-2xl border border-gray-100 dark:border-gray-700">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">問題回報</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">請描述你遇到的問題，管理員會在後台處理。</p>
                            </div>
                            <button type="button" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500" data-issue-report-close>
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <form id="issue-report-form" class="p-6 space-y-4">
                            <div>
                                <label for="issue-report-title" class="block text-sm font-bold text-gray-700 dark:text-gray-200">問題標題</label>
                                <input id="issue-report-title" name="title" type="text" maxlength="120" required
                                    class="mt-2 w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                    placeholder="例如：無法送出申請">
                            </div>
                            <div>
                                <label for="issue-report-description" class="block text-sm font-bold text-gray-700 dark:text-gray-200">問題描述</label>
                                <textarea id="issue-report-description" name="description" rows="5" maxlength="1000" required
                                    class="mt-2 w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                    placeholder="請描述發生在哪個頁面、你做了什麼操作、畫面出現什麼問題"></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="issue-report-email" class="block text-sm font-bold text-gray-700 dark:text-gray-200">聯絡 Email</label>
                                    <input id="issue-report-email" name="contact_email" type="email" maxlength="100"
                                        class="mt-2 w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                        placeholder="可留空">
                                </div>
                                <div>
                                    <label for="issue-report-phone" class="block text-sm font-bold text-gray-700 dark:text-gray-200">聯絡電話</label>
                                    <input id="issue-report-phone" name="contact_phone" type="tel" maxlength="30"
                                        class="mt-2 w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                        placeholder="可留空">
                                </div>
                            </div>
                            <p id="issue-report-message" class="hidden text-sm"></p>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" data-issue-report-close
                                    class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600">
                                    取消
                                </button>
                                <button id="issue-report-submit-btn" type="submit"
                                    class="px-4 py-2 rounded-md bg-primary text-white text-sm font-bold hover:bg-primary/90">
                                    送出
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);

        document.getElementById('issue-report-open-btn')?.addEventListener('click', openIssueReportModal);
        document.getElementById('issue-report-form')?.addEventListener('submit', submitIssueReport);
        wrapper.querySelectorAll('[data-issue-report-close]').forEach((button) => {
            button.addEventListener('click', closeIssueReportModal);
        });
        document.getElementById('issue-report-notification-read-btn')?.addEventListener('click', markLatestNotificationRead);
        fillContactDefaults();
        setupIssueReportAutosave();
        setTimeout(loadIssueReportNotifications, 800);
    }

    function openIssueReportModal() {
        const modal = document.getElementById('issue-report-modal');
        const message = document.getElementById('issue-report-message');
        if (message) {
            message.className = 'hidden text-sm';
            message.textContent = '';
        }
        modal?.classList.remove('hidden');
        fillContactDefaults();
        document.getElementById('issue-report-title')?.focus();
    }

    function closeIssueReportModal() {
        document.getElementById('issue-report-modal')?.classList.add('hidden');
    }

    async function submitIssueReport(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const submitBtn = document.getElementById('issue-report-submit-btn');
        const message = document.getElementById('issue-report-message');
        const user = getCurrentUser();

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = '送出中...';
        }

        setMessage('', '');

        try {
            const response = await fetch('../api/create_issue_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: form.title.value.trim(),
                    description: form.description.value.trim(),
                    reporter_username: user.username || user.real_name || '',
                    reporter_role: user.role || '',
                    contact_email: form.contact_email.value.trim(),
                    contact_phone: form.contact_phone.value.trim()
                })
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || '送出失敗');
            }

            clearIssueReportDraft();
            form.reset();
            setMessage('問題回報已送出，管理員會在後台處理。', 'success');
            setTimeout(closeIssueReportModal, 900);
        } catch (error) {
            setMessage(error.message || '送出失敗', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '送出';
            }
        }

        function setMessage(text, type) {
            if (!message) return;
            if (!text) {
                message.className = 'hidden text-sm';
                message.textContent = '';
                return;
            }
            const color = type === 'success' ? 'text-green-600' : 'text-red-600';
            message.className = `text-sm font-bold ${color}`;
            message.textContent = text;
        }
    }

    function setupIssueReportAutosave() {
        const form = document.getElementById('issue-report-form');
        if (!form) return;

        const key = getIssueReportDraftKey();
        let dirty = false;

        const existing = localStorage.getItem(key);
        if (existing) {
            try {
                const draft = JSON.parse(existing);
                if (draft && draft.data && window.confirm('偵測到上次未送出的問題回報暫存內容，是否恢復？')) {
                    if (form.title) form.title.value = draft.data.title || '';
                    if (form.description) form.description.value = draft.data.description || '';
                    if (form.contact_email) form.contact_email.value = draft.data.contact_email || '';
                    if (form.contact_phone) form.contact_phone.value = draft.data.contact_phone || '';
                }
            } catch {
                localStorage.removeItem(key);
            }
        }

        form.addEventListener('input', () => {
            dirty = true;
        });
        form.addEventListener('change', () => {
            dirty = true;
        });

        setInterval(() => {
            if (!dirty) return;
            saveIssueReportDraft(form);
            dirty = false;
        }, 30000);

        window.addEventListener('beforeunload', () => {
            if (dirty) {
                saveIssueReportDraft(form);
            }
        });
    }

    function getIssueReportDraftKey() {
        const user = getCurrentUser();
        const username = user.username || user.real_name || 'guest';
        return `autosave:${username}:issue-report`;
    }

    function saveIssueReportDraft(form) {
        localStorage.setItem(getIssueReportDraftKey(), JSON.stringify({
            savedAt: new Date().toISOString(),
            data: {
                title: form.title?.value || '',
                description: form.description?.value || '',
                contact_email: form.contact_email?.value || '',
                contact_phone: form.contact_phone?.value || ''
            }
        }));
    }

    function clearIssueReportDraft() {
        localStorage.removeItem(getIssueReportDraftKey());
    }

    function getCurrentUser() {
        const previewParams = new URLSearchParams(window.location.search);
        const previewRole = previewParams.get('preview');
        const previewUsername = previewParams.get('preview_user');

        if (previewRole) {
            const roleMap = {
                student: '學生',
                teacher: '老師',
                reviewer: '獎助單位'
            };
            const fallbackUsername = {
                student: 'student-preview',
                teacher: 'teacher-preview',
                reviewer: 'reviewer-preview'
            };
            return {
                username: previewUsername || fallbackUsername[previewRole] || '',
                real_name: `${roleMap[previewRole] || '角色'}端預覽`,
                role: roleMap[previewRole] || previewRole
            };
        }

        try {
            return JSON.parse(localStorage.getItem('user') || '{}');
        } catch {
            return {};
        }
    }

    async function fillContactDefaults() {
        const user = getCurrentUser();
        const emailInput = document.getElementById('issue-report-email');
        const phoneInput = document.getElementById('issue-report-phone');
        const username = user.username || '';

        if (username) {
            try {
                const response = await fetch(`../api/get_user_contact.php?username=${encodeURIComponent(username)}`);
                const result = await response.json();
                if (response.ok && result.success && result.data) {
                    if (emailInput) emailInput.value = result.data.email || '';
                    if (phoneInput) phoneInput.value = result.data.phone || '';
                    return;
                }
            } catch (error) {
                console.error('User contact lookup error:', error);
            }
        }

        if (emailInput && !emailInput.value && user.email) {
            emailInput.value = user.email;
        }
        if (phoneInput && !phoneInput.value && user.phone) {
            phoneInput.value = user.phone;
        }
    }

    async function loadIssueReportNotifications() {
        const user = getCurrentUser();
        const username = user.username || user.real_name || '';
        if (!username || isRolePreviewMode() || isPreviewUsername(username) || isStudentContext(user) || isTeacherContext(user)) return;

        try {
            const response = await fetch(`../api/get_issue_report_notifications.php?username=${encodeURIComponent(username)}`);
            const result = await response.json();
            if (!response.ok || !result.success || !Array.isArray(result.data) || result.data.length === 0) {
                return;
            }

            window.__latestIssueReportNotification = result.data[0];
            const badge = document.getElementById('issue-report-notification-badge');
            const toast = document.getElementById('issue-report-notification-toast');
            const text = document.getElementById('issue-report-notification-text');

            if (badge) {
                badge.textContent = String(result.data.length);
                badge.classList.remove('hidden');
            }
            if (renderIssueNotificationsInLatestArea(result.data)) {
                toast?.classList.add('hidden');
                return;
            }
            if (text) {
                text.textContent = result.data[0].message;
            }
            toast?.classList.remove('hidden');
        } catch (error) {
            console.error('Issue report notification error:', error);
        }
    }

    function isStudentContext(user) {
        return user.role === '學生' || user.role === 'student';
    }

    function isTeacherContext(user) {
        return user.role === '老師' || user.role === 'teacher';
    }

    function isRolePreviewMode() {
        return new URLSearchParams(window.location.search).has('preview');
    }

    function isPreviewUsername(username) {
        return ['student-preview', 'teacher-preview', 'reviewer-preview'].includes(username);
    }

    function renderIssueNotificationsInLatestArea(notifications) {
        const container = document.getElementById('notifications-list') || document.getElementById('notification-list');
        if (!container) return false;

        container.querySelectorAll('[data-issue-report-notification-card]').forEach((card) => card.remove());

        const emptyText = container.textContent || '';
        if (emptyText.includes('目前無新通知') || emptyText.includes('目前沒有新通知') || emptyText.includes('載入通知中')) {
            container.innerHTML = '';
        }

        notifications.forEach((notification) => {
            container.prepend(createIssueNotificationCard(notification));
        });

            constrainLatestNotifications(container);

        return true;
    }

    function createIssueNotificationCard(notification) {
        const card = document.createElement('div');
        card.dataset.issueReportNotificationCard = 'true';
        card.className = 'bg-white dark:bg-[#1e2634] p-4 rounded-xl border border-l-4 border-blue-500 border-slate-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow flex gap-3';
        card.innerHTML = `
            <div class="bg-blue-100 text-primary rounded-full p-2 h-fit shrink-0">
                <span class="material-symbols-outlined text-[20px]">support_agent</span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h4 class="font-bold text-[#111318] dark:text-white text-sm">${escapeHtml(notification.title || '問題回報狀態更新')}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${escapeHtml(notification.created_at || '')}</p>
                    </div>
                    <span data-issue-notification-state class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary whitespace-nowrap">未讀</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2 leading-relaxed">${escapeHtml(notification.message || '')}</p>
                <button type="button" class="mt-3 text-xs font-bold text-primary hover:underline" data-issue-notification-read="${Number(notification.id)}">標記已讀</button>
            </div>
        `;

        card.querySelector('[data-issue-notification-read]')?.addEventListener('click', async () => {
            await markNotificationRead(notification.id);
            card.dataset.issueReportNotificationRead = 'true';
            card.classList.add('opacity-70');
            const unreadBadge = card.querySelector('[data-issue-notification-state]');
            if (unreadBadge) {
                unreadBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300 whitespace-nowrap';
                unreadBadge.textContent = '已讀';
            }
            const readButton = card.querySelector('[data-issue-notification-read]');
            readButton?.remove();
            reorderIssueNotificationCards(card.parentElement);
            const remaining = document.querySelectorAll('[data-issue-notification-read]').length;
            const badge = document.getElementById('issue-report-notification-badge');
            if (badge) {
                if (remaining > 0) {
                    badge.textContent = String(remaining);
                } else {
                    badge.classList.add('hidden');
                }
            }
            constrainLatestNotifications(document.getElementById('notifications-list'));
            constrainLatestNotifications(document.getElementById('notification-list'));
        });

        return card;
    }

    function reorderIssueNotificationCards(container) {
        if (!container) return;

        const issueCards = Array.from(container.querySelectorAll('[data-issue-report-notification-card]'));
        if (issueCards.length <= 1) {
            constrainLatestNotifications(container);
            return;
        }

        const firstNonIssueCard = Array.from(container.children).find((child) => {
            return !child.matches('[data-issue-report-notification-card]') &&
                !child.matches('[data-student-notification-inline-more], [data-teacher-notification-inline-more]');
        });

        const sortedCards = issueCards.sort((a, b) => {
            const aRead = a.dataset.issueReportNotificationRead === 'true' ? 1 : 0;
            const bRead = b.dataset.issueReportNotificationRead === 'true' ? 1 : 0;
            return aRead - bRead;
        });

        sortedCards.reverse().forEach((card) => {
            if (firstNonIssueCard) {
                container.insertBefore(card, firstNonIssueCard);
            } else {
                container.prepend(card);
            }
        });

        constrainLatestNotifications(container);
    }

    function constrainLatestNotifications(container) {
        if (!container) return;

        const limit = container.id === 'notification-list' ? 3 : container.id === 'notifications-list' ? 3 : null;
        if (!limit) return;

        container.querySelector('[data-student-notification-inline-more]')?.remove();
        container.querySelector('[data-teacher-notification-inline-more]')?.remove();

        const cards = Array.from(container.children).filter((child) => {
            if (child.matches('[data-student-notification-inline-more], [data-teacher-notification-inline-more]')) return false;
            if (container.id === 'notifications-list') {
                return child.matches('[data-issue-report-notification-card], .student-notification-card');
            }
            return child.matches('[data-issue-report-notification-card]') || !child.classList.contains('hidden');
        });

        cards.forEach((card, index) => {
            card.classList.toggle('hidden', index >= limit);
        });

        if (cards.length <= limit) return;

        const button = document.createElement('button');
        button.type = 'button';
        if (container.id === 'notifications-list') {
            button.dataset.studentNotificationInlineMore = 'true';
        } else {
            button.dataset.teacherNotificationInlineMore = 'true';
        }
        button.className = 'w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-bold text-primary hover:bg-primary/5 dark:hover:bg-primary/10';
        button.textContent = `顯示更多 (${cards.length})`;
        button.addEventListener('click', () => {
            const isExpanded = button.dataset.expanded === 'true';
            button.dataset.expanded = isExpanded ? 'false' : 'true';
            cards.forEach((card, index) => {
                card.classList.toggle('hidden', isExpanded && index >= limit);
            });
            button.textContent = isExpanded ? `顯示更多 (${cards.length})` : `收合為 ${limit} 筆`;
        });
        container.appendChild(button);
    }

    async function markLatestNotificationRead() {
        const notification = window.__latestIssueReportNotification;
        const user = getCurrentUser();
        const username = user.username || user.real_name || '';
        if (!notification || !username) return;

        try {
            await markNotificationRead(notification.id);
        } catch (error) {
            console.error('Issue report notification read error:', error);
        }

        document.getElementById('issue-report-notification-toast')?.classList.add('hidden');
        document.getElementById('issue-report-notification-badge')?.classList.add('hidden');
        window.__latestIssueReportNotification = null;
    }

    async function markNotificationRead(notificationId) {
        const user = getCurrentUser();
        const username = user.username || user.real_name || '';
        if (!notificationId || !username) return;

        await fetch('../api/mark_issue_report_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: notificationId, username })
        });
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

// Student notification and matching module owned by Wu Ru-Ting.
// Scope: eligible scholarship recommendations, deadline reminders, result notices, and read status.

(function () {
    const API_BASE = '../api/student';
    let cachedNotifications = [];

    const TYPE_STYLE = {
        result_approved: {
            border: 'border-l-green-500',
            iconWrap: 'bg-green-100 dark:bg-green-900/30',
            icon: 'check_circle',
            iconColor: 'text-green-600 dark:text-green-400',
            sender: '獎助學金委員會',
        },
        result_rejected: {
            border: 'border-l-red-500',
            iconWrap: 'bg-red-100 dark:bg-red-900/30',
            icon: 'cancel',
            iconColor: 'text-red-600 dark:text-red-400',
            sender: '獎助學金委員會',
        },
        result_revision: {
            border: 'border-l-orange-500',
            iconWrap: 'bg-orange-100 dark:bg-orange-900/30',
            icon: 'priority_high',
            iconColor: 'text-orange-600 dark:text-orange-400',
            sender: '系統管理員',
        },
        deadline_reminder: {
            border: 'border-l-blue-500',
            iconWrap: 'bg-blue-100 dark:bg-blue-900/30',
            icon: 'schedule',
            iconColor: 'text-blue-600 dark:text-blue-400',
            sender: '系統提醒',
        },
        eligibility_recommendation: {
            border: 'border-l-purple-500',
            iconWrap: 'bg-purple-100 dark:bg-purple-900/30',
            icon: 'auto_awesome',
            iconColor: 'text-purple-600 dark:text-purple-400',
            sender: '資格推薦',
        },
    };

    const RECOMMENDATION_STYLES = [
        { img: 'assets/images/abstract_blue.png', tag: '成績優異', fallback: 'from-blue-600 to-indigo-600' },
        { img: 'assets/images/abstract_green.png', tag: '資格推薦', fallback: 'from-emerald-500 to-teal-500' },
        { img: 'assets/images/abstract_purple.png', tag: '截止提醒', fallback: 'from-purple-600 to-pink-600' },
    ];

    function formatDate(value) {
        if (!value) return '剛剛';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return `${date.getFullYear()}/${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}`;
    }

    function getStoredUsername() {
        try {
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            return user?.username || '';
        } catch (error) {
            return '';
        }
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, options);
        const result = await response.json();
        if (!response.ok || result.success === false) {
            throw new Error(result.message || `HTTP ${response.status}`);
        }
        return result;
    }

    function createNotificationCard(notification) {
        const style = TYPE_STYLE[notification.type] || TYPE_STYLE.deadline_reminder;
        const card = document.createElement('div');
        card.className = `student-notification-card bg-white dark:bg-slate-800 p-4 rounded-xl border border-l-4 border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow ${style.border}${notification.is_read ? ' opacity-80' : ''}`;
        card.dataset.notificationId = notification.id;

        let actionHtml = '';
        if (notification.type === 'result_revision' && notification.related_application_id) {
            actionHtml = `<button type="button" data-action="open-application" data-application-id="${notification.related_application_id}" class="inline-block mt-2 text-xs font-bold text-orange-600 dark:text-orange-400 hover:underline">前往補件 →</button>`;
        } else if (notification.type === 'deadline_reminder' && notification.related_scholarship_id) {
            actionHtml = `<button type="button" data-action="open-scholarship" data-scholarship-id="${notification.related_scholarship_id}" class="inline-block mt-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">立即申請 →</button>`;
        } else if (notification.type === 'eligibility_recommendation' && notification.related_scholarship_id) {
            actionHtml = `<button type="button" data-action="open-scholarship" data-scholarship-id="${notification.related_scholarship_id}" class="inline-block mt-2 text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline">查看獎學金 →</button>`;
        }

        card.innerHTML = `
            <div class="flex gap-3">
                <div class="size-10 rounded-full ${style.iconWrap} flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined ${style.iconColor}">${style.icon}</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-slate-900 dark:text-white text-sm">${style.sender}</span>
                        <span class="text-xs text-slate-500">${formatDate(notification.created_at)}</span>
                        ${notification.is_read ? '' : '<span data-role="unread-badge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary">未讀</span>'}
                    </div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">${notification.title}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mt-1">${notification.message}</p>
                    ${actionHtml}
                </div>
            </div>
        `;

        card.addEventListener('click', async (event) => {
            const actionBtn = event.target.closest('[data-action]');
            if (actionBtn) {
                event.stopPropagation();
                const action = actionBtn.dataset.action;
                if (action === 'open-application') {
                    window.location.href = `application-form.html?application_id=${actionBtn.dataset.applicationId}`;
                } else if (action === 'open-scholarship') {
                    window.location.href = `application-form.html?scholarship_id=${actionBtn.dataset.scholarshipId}`;
                }
                return;
            }

            if (!notification.is_read) {
                await markAsRead(notification.id);
            }
        });

        return card;
    }

    function renderEmptyState(container, message) {
        container.innerHTML = `
            <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                <div class="flex flex-col items-center gap-2">
                    <span class="material-symbols-outlined text-4xl text-slate-300">notifications_off</span>
                    <p class="text-slate-500 text-sm">${message}</p>
                </div>
            </div>
        `;
    }

    function renderErrorState(container, error) {
        container.innerHTML = `
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-200 dark:border-red-700 text-center">
                <p class="text-red-600 dark:text-red-400 text-sm">無法載入通知：${error.message || '未知錯誤'}</p>
            </div>
        `;
    }

    function renderNotificationList(container, notifications, limit = 3) {
        if (!container) return;

        container.innerHTML = '';
        if (!notifications.length) {
            renderEmptyState(container, '目前無新通知');
            return;
        }

        notifications.slice(0, limit).forEach((notification) => {
            container.appendChild(createNotificationCard(notification));
        });
    }

    function syncUnreadCountFromNotifications(notifications = cachedNotifications) {
        window.__studentNotificationUnreadCount = notifications.filter((item) => !item.is_read).length;
        updateUnreadBadge();
    }

    function updateUnreadBadge() {
        const badge = document.getElementById('student-notification-unread-badge');
        const count = window.__studentNotificationUnreadCount || 0;
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function refreshNotificationViews(notifications) {
        const listContainer = document.getElementById('notifications-list');
        renderNotificationList(listContainer, notifications, 3);
        syncUnreadCountFromNotifications(notifications);

        const modal = document.getElementById('notifications-modal');
        const modalList = document.getElementById('modal-notifications-list');
        if (!modal || modal.classList.contains('hidden') || !modalList) return;

        modalList.innerHTML = '';
        if (!notifications.length) {
            renderEmptyState(modalList, '無通知');
            return;
        }

        notifications.forEach((notification) => {
            modalList.appendChild(createNotificationCard(notification));
        });
    }

    async function markAsRead(notificationId, username = getStoredUsername()) {
        if (!username || !notificationId) return;

        const result = await fetchJson(`${API_BASE}/mark_notification_read.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_username: username,
                notification_id: notificationId,
            }),
        });

        const target = cachedNotifications.find((item) => String(item.id) === String(notificationId));
        if (target) target.is_read = 1;

        window.__studentNotificationUnreadCount = result.unread_count ?? 0;
        updateUnreadBadge();
        refreshNotificationViews(cachedNotifications);
    }

    async function markAllAsRead(username = getStoredUsername()) {
        if (!username) return;

        await fetchJson(`${API_BASE}/mark_notification_read.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_username: username,
                mark_all: true,
            }),
        });

        cachedNotifications.forEach((item) => {
            item.is_read = 1;
        });
        window.__studentNotificationUnreadCount = 0;
        refreshNotificationViews(cachedNotifications);
    }

    function ensureUnreadBadge() {
        const titleWrap = document.querySelector('#view-all-notifications-btn')?.parentElement;
        if (!titleWrap || document.getElementById('student-notification-unread-badge')) return;

        const badge = document.createElement('span');
        badge.id = 'student-notification-unread-badge';
        badge.className = 'hidden ml-2 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-white text-[10px] font-bold';
        titleWrap.querySelector('h3')?.appendChild(badge);
    }

    function bindNotificationModal(notifications) {
        cachedNotifications = notifications;
        const btn = document.getElementById('view-all-notifications-btn');
        const modal = document.getElementById('notifications-modal');
        const closeBtn = document.getElementById('close-notif-modal-btn');
        const backdrop = document.getElementById('notif-modal-backdrop');
        const modalContent = document.getElementById('notif-modal-content');
        const modalList = document.getElementById('modal-notifications-list');
        const markAllBtn = document.getElementById('mark-all-notifications-read-btn');

        if (!btn || !modal || !modalList) return;

        function openModal() {
            modal.classList.remove('hidden');
            void modal.offsetWidth;
            backdrop?.classList.remove('opacity-0');
            modalContent?.classList.remove('scale-95', 'opacity-0');
            modalContent?.classList.add('scale-100', 'opacity-100');
            refreshNotificationViews(cachedNotifications);
        }

        function closeModal() {
            backdrop?.classList.add('opacity-0');
            modalContent?.classList.add('scale-95', 'opacity-0');
            modalContent?.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        btn.onclick = (event) => {
            event.preventDefault();
            openModal();
        };

        if (closeBtn) closeBtn.onclick = closeModal;
        if (backdrop) backdrop.onclick = closeModal;

        if (markAllBtn) {
            markAllBtn.classList.remove('hidden');
            markAllBtn.onclick = async (event) => {
                event.preventDefault();
                await markAllAsRead();
            };
        }
    }

    function renderRecommendedScholarships(scholarships) {
        const container = document.getElementById('recommendation-grid');
        if (!container) return;

        container.innerHTML = '';

        if (!scholarships.length) {
            container.innerHTML = `
                <div class="md:col-span-2 lg:col-span-3 bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 text-center text-slate-500 text-sm">
                    目前沒有符合您系所與成績條件的推薦獎學金。
                </div>
            `;
            return;
        }

        scholarships.forEach((sch, index) => {
            const style = RECOMMENDATION_STYLES[index % RECOMMENDATION_STYLES.length];
            const amount = sch.amount ? parseInt(sch.amount, 10).toLocaleString() : '0';
            const end = sch.application_end_date ? new Date(sch.application_end_date) : null;
            const endDateStr = end
                ? `${String(end.getMonth() + 1).padStart(2, '0')}/${String(end.getDate()).padStart(2, '0')}`
                : '未定';
            const reasons = Array.isArray(sch.match_reasons) ? sch.match_reasons.slice(0, 2).join('；') : '';

            const card = document.createElement('div');
            card.className = 'animate-on-scroll group flex flex-col bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-primary/50 transition-all duration-300 overflow-hidden';
            card.innerHTML = `
                <div class="h-32 bg-slate-200 dark:bg-slate-700 rounded-t-xl relative overflow-hidden">
                    <img src="${style.img}" class="absolute inset-0 w-full h-full object-cover rounded-t-xl group-hover:scale-110 transition-transform duration-700"
                         onerror="this.style.display='none'; this.parentElement.classList.add('bg-gradient-to-r', '${style.fallback}')">
                    <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
                    <div class="absolute top-4 left-4 bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-white text-xs font-bold border border-white/30">
                        ${style.tag}
                    </div>
                </div>
                <div class="p-5 flex flex-col flex-1 gap-3">
                    <div class="flex justify-between items-start gap-2">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">${sch.name}</h4>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-primary/10 text-primary shrink-0">${sch.match_summary || '符合資格'}</span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2">${sch.description || '無描述'}</p>
                    ${reasons ? `<p class="text-xs text-primary/90 dark:text-primary/80 line-clamp-2">推薦原因：${reasons}</p>` : ''}
                    <div class="flex items-center gap-4 text-sm text-slate-600 dark:text-slate-300 mt-auto pt-2">
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">payments</span>
                            <span>NT$ ${amount}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">event</span>
                            <span>截止：${endDateStr}</span>
                        </div>
                    </div>
                    <button type="button" data-scholarship-id="${sch.id}"
                        class="mt-3 w-full py-2 rounded-lg border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        查看詳情
                    </button>
                </div>
            `;

            card.querySelector('button')?.addEventListener('click', () => {
                window.location.href = `application-form.html?scholarship_id=${sch.id}`;
            });

            container.appendChild(card);
            if (window.observer) window.observer.observe(card);
            setTimeout(() => card.classList.add('is-visible'), 300);
        });
    }

    async function loadNotifications(username, options = {}) {
        const containerId = options.containerId || 'notifications-list';
        const container = document.getElementById(containerId);
        if (!username) {
            if (container) renderEmptyState(container, '請先登入以查看通知');
            return [];
        }

        try {
            const result = await fetchJson(
                `${API_BASE}/get_student_notifications.php?student_username=${encodeURIComponent(username)}&sync=1`
            );
            const notifications = result.data || [];
            cachedNotifications = notifications;
            window.__studentNotificationUnreadCount = result.unread_count || 0;
            ensureUnreadBadge();
            updateUnreadBadge();
            renderNotificationList(container, notifications, options.limit || 3);
            bindNotificationModal(notifications);
            return notifications;
        } catch (error) {
            console.error('StudentNotifications.loadNotifications:', error);
            if (container) renderErrorState(container, error);
            return [];
        }
    }

    async function loadRecommendations(username, renderCallback) {
        if (!username) return [];

        try {
            const result = await fetchJson(
                `${API_BASE}/get_eligible_scholarships.php?student_username=${encodeURIComponent(username)}&limit=6`
            );
            const scholarships = result.data || [];
            const renderer = renderCallback || renderRecommendedScholarships;
            renderer(scholarships);
            return scholarships;
        } catch (error) {
            console.error('StudentNotifications.loadRecommendations:', error);
            const container = document.getElementById('recommendation-grid');
            if (container) {
                container.innerHTML = `
                    <div class="md:col-span-2 lg:col-span-3 p-6 rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm text-center">
                        無法載入推薦獎學金：${error.message || '未知錯誤'}
                    </div>
                `;
            }
            return [];
        }
    }

    window.StudentNotifications = {
        loadNotifications,
        loadRecommendations,
        markAsRead,
        markAllAsRead,
        renderRecommendedScholarships,
        createNotificationCard,
    };
})();

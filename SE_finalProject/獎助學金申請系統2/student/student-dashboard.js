document.addEventListener('DOMContentLoaded', () => {
    // Update Greeting
    // Update Greeting & Semester Info
    const storedUser = localStorage.getItem('user');
    const previewParams = new URLSearchParams(window.location.search);
    const isPreviewMode = previewParams.get('preview') === 'student';
    const previewUser = {
        username: previewParams.get('preview_user') || 'student-preview',
        real_name: '學生端預覽',
        role: '學生',
        email: 'student-preview@example.edu'
    };

    if (storedUser || isPreviewMode) {
        try {
            const user = isPreviewMode ? previewUser : JSON.parse(storedUser);

            // 1. Update Greeting Name immediately from localStorage (fast)
            const greetingEl = document.getElementById('dashboard-greeting');
            const name = user.real_name || user.username;
            if (greetingEl) {
                greetingEl.textContent = `歡迎回來，${name} 👋`;
            }

            // 2. Fetch full profile to get Grade/Department
            fetch(`../api/get_student_profile.php?username=${user.username}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}`);
                    }
                    return res.json();
                })
                .then(result => {
                    const infoEl = document.getElementById('semester-info');
                    if (result.success && result.data && infoEl) {
                        const data = result.data;
                        const department = data.department || '系所未知';
                        const dbName = data.real_name || user.real_name || user.username;
                        // Logic: If grade_level exists, show it; otherwise "年級未知"
                        const grade = data.grade_level ? data.grade_level : '年級未知';

                        if (greetingEl) {
                            greetingEl.textContent = `歡迎回來，${dbName} 👋`;
                        }
                        const headerName = document.getElementById('header-user-name');
                        const headerEmail = document.getElementById('header-user-email');
                        if (headerName) headerName.textContent = dbName;
                        if (headerEmail && data.email) headerEmail.textContent = data.email;

                        // 統一顯示學期（與老師端一致）
                        infoEl.textContent = `目前學期：113學年度第1學期 | ${department} ${grade}`;
                    } else if (infoEl) {
                        infoEl.textContent = `載入失敗: ${result.message || '未知錯誤'}`;
                    }
                })
                .catch(err => {
                    console.error('Error fetching dashboard info:', err);
                    const infoEl = document.getElementById('semester-info');
                    if (infoEl) infoEl.textContent = `連接錯誤: ${err.message}`;
                });

            // 3. Fetch My Applications
            fetch(`../api/get_student_applications.php?student_username=${user.username}`)
                .then(res => res.json())
                .then(result => {
                    const tbody = document.getElementById('applications-table-body');
                    if (!tbody) {
                        console.error('Applications table body not found!');
                        return;
                    }

                    tbody.innerHTML = ''; // Clear hardcoded content

                    if (result.success && result.data && result.data.length > 0) {
                        console.log('Received applications:', result.data);
                        // Render Notifications based on these applications
                        renderNotifications(result.data);
                        setupNotificationsModal(result.data);

                        // Show/Hide "Show More" button and Setup Modal
                        setupApplicationsModal(result.data);

                        // Store globally for stat card access
                        window.allApplications = result.data;

                        // Render first 2 items to main table
                        const displayData = result.data.slice(0, 2);
                        displayData.forEach(app => {
                            const tr = createApplicationRow(app);
                            tbody.appendChild(tr);
                        });
                    } else {
                        console.warn('No applications found or empty data.');
                        renderNotifications([]); // Render empty notifications
                        setupApplicationsModal([]); // Hide button


                        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">目前尚無申請紀錄</td></tr>';
                    }
                })
                .catch(err => {
                    console.error('Error fetching applications:', err);
                    const tbody = document.getElementById('applications-table-body');
                    if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-red-500">載入失敗: ${err.message}</td></tr>`;
                    renderNotifications([], err); // Also show error in notifications
                });

            // 4. Fetch Dashboard Stats
            fetch(`../api/get_dashboard_stats.php?student_username=${user.username}`)
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        const d = result.data;
                        // Card 1: Reviewing
                        document.querySelector('#stat-reviewing h3').textContent = d.reviewing;

                        // Card 2: Passed
                        document.querySelector('#stat-passed h3').textContent = d.passed;

                        // Card 3: Needs Action
                        document.querySelector('#stat-action h3').textContent = d.needs_action;

                        // Card 4: Total Amount
                        document.querySelector('#total-amount-card h3').textContent = `NT$ ${parseInt(d.total_amount).toLocaleString()}`;
                    }
                })
                .catch(err => console.error('Error fetching stats:', err));

            // 5. Fetch database-backed notifications and eligible scholarship recommendations.
            if (window.StudentNotifications) {
                window.StudentNotifications.loadNotifications(user.username);
                window.StudentNotifications.loadRecommendations(user.username, renderRecommendedScholarships);
            } else {
                console.error('Student notification module is not loaded.');
                renderNotifications([], new Error('學生通知模組未載入'));
            }

        } catch (e) {
            console.error(e);
        }

    }

    function renderRecommendedScholarships(scholarships) {
        const container = document.getElementById('recommendation-grid');
        if (!container) return;

        container.innerHTML = ''; // Clear hardcoded

        // Styles config with generated images
        const styles = [
            {
                img: 'assets/images/abstract_blue.png',
                tag: '成績優異',
                fallback: 'from-blue-600 to-indigo-600'
            },
            {
                img: 'assets/images/abstract_green.png',
                tag: '志工服務',
                fallback: 'from-emerald-500 to-teal-500'
            },
            {
                img: 'assets/images/abstract_purple.png',
                tag: '海外交流',
                fallback: 'from-purple-600 to-pink-600'
            }
        ];

        scholarships.forEach((sch, index) => {
            const style = styles[index % styles.length];
            const amount = parseInt(sch.amount).toLocaleString();

            // Format dates mm/dd
            const end = sch.application_end_date ? new Date(sch.application_end_date) : null;
            const endDateStr = end ? `${(end.getMonth() + 1).toString().padStart(2, '0')}/${end.getDate().toString().padStart(2, '0')}` : '未定';

            const card = document.createElement('div');
            card.dataset.delay = "0"; // or index * 100
            card.className = "animate-on-scroll group flex flex-col bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-primary/50 transition-all duration-300 overflow-hidden";

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
                    <div class="flex justify-between items-start">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">
                            ${sch.name}
                        </h4>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2">
                        ${sch.description || '無描述'}
                    </p>
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
                    <button onclick="window.location.href='application-form.html?scholarship_id=${sch.id}'"
                        class="mt-3 w-full py-2 rounded-lg border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        查看詳情
                    </button>
                </div>
            `;
            container.appendChild(card);

            // CRITICAL: Observe dynamic element for scroll animations
            if (window.observer) {
                window.observer.observe(card);
            } else {
                // Fallback: Show immediately if observer is missing
                card.classList.add('is-visible');
            }

            // Force visibility if already in viewport or if observer is slow
            setTimeout(() => card.classList.add('is-visible'), 500);
        });
    }
    // Logout Logic
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            window.location.href = '../index.html';
        });
    }

    // --- Celebration Feature ---
    const totalAmountCard = document.getElementById('total-amount-card');
    const celebrationModal = document.getElementById('celebration-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalContent = document.getElementById('modal-content');

    if (totalAmountCard && celebrationModal) {
        totalAmountCard.addEventListener('click', () => {
            // 1. Show Modal
            const amountText = document.querySelector('#total-amount-card h3').textContent;
            const passedCount = document.querySelector('#stat-passed h3').textContent;

            document.getElementById('modal-total-amount').textContent = amountText;
            document.getElementById('modal-passed-count').textContent = passedCount;

            celebrationModal.classList.remove('pointer-events-none');
            celebrationModal.classList.remove('opacity-0');
            celebrationModal.classList.add('modal-enter'); // Add entrance animation
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');

            // 2. Trigger Confetti
            triggerConfetti();

            // 3. Reset & Trigger Animations
            if (window.initTextAnimation) {
                // Clear previous split to allow re-animation
                const text = modalContent.querySelector('.animate-text');
                if (text) text.textContent = text.textContent;
                window.initTextAnimation();
            }

            // Reset generic entrance animations
            const animatedElements = modalContent.querySelectorAll('.animate-on-view');
            animatedElements.forEach(el => {
                el.style.animation = 'none';
                el.offsetHeight; /* trigger reflow */
                el.style.animation = null;
            });
        });

        // Close Logic
        const closeModal = () => {
            celebrationModal.classList.add('pointer-events-none', 'opacity-0');
            celebrationModal.classList.remove('modal-enter'); // FIX: Remove animation class preventing hide
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
        };

        if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
        if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);
    }

    function triggerConfetti() {
        const duration = 3000;
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 60 };

        const randomInRange = (min, max) => Math.random() * (max - min) + min;

        const interval = setInterval(function () {
            const timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            const particleCount = 50 * (timeLeft / duration);
            // since particles fall down, start a bit higher than random
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);
    }
    function createNotificationCard(app) {
        let card = document.createElement('div');
        // Common classes
        const baseClasses = "bg-white dark:bg-slate-800 p-4 rounded-xl border border-l-4 border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow";

        if (parseInt(app.status, 10) === 1) {
            card.className = baseClasses + " border-l-green-500";
            card.innerHTML = `
            <div class="flex gap-3">
                <div class="size-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-slate-900 dark:text-white text-sm">獎助學金委員會</span>
                        <span class="text-xs text-slate-500">${app.application_date || '剛剛'}</span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        恭喜您！您申請的「${app.scholarship_name}」已通過審核，預計於下個月撥款。
                    </p>
                </div>
            </div>`;
        } else if (parseInt(app.status, 10) === 2) {
            card.className = baseClasses + " border-l-orange-500";
            card.innerHTML = `
            <div class="flex gap-3">
                <div class="size-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-orange-600 dark:text-orange-400">priority_high</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-slate-900 dark:text-white text-sm">系統管理員</span>
                        <span class="text-xs text-slate-500">${app.application_date || '剛剛'}</span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        您的「${app.scholarship_name}」申請需補件：${app.review_comment || '請檢查缺漏資料'}
                    </p>
                    <a href="application-form.html?application_id=${app.id}"
                        class="inline-block mt-2 text-xs font-bold text-orange-600 dark:text-orange-400 hover:underline">前往補件
                        →</a>
                </div>
            </div>`;
        } else {
            return null; // Don't render other statuses in notifications for now
        }
        return card;
    }

    function renderNotifications(applications, error = null) {
        console.log('renderNotifications called with:', applications, error);
        const container = document.getElementById('notifications-list');
        if (!container) {
            console.error('Notification container not found!');
            return;
        }

        try {
            console.log('Clearing container and starting render...');
            container.innerHTML = ''; // Clear loading state

            if (error) {
                container.innerHTML = `
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-200 dark:border-red-700 text-center">
                        <p class="text-red-600 dark:text-red-400 text-sm">無法載入通知: ${error.message || '未知錯誤'}</p>
                    </div>`;
                return;
            }

            // Filter for actionable logic locally to check if ANY exist, but we render first 3
            // Filter for actionable logic: 1 (Approved) or 2 (Needs Action)
            const actionableApps = applications.filter(app => parseInt(app.status, 10) === 1 || parseInt(app.status, 10) === 2);

            if (!actionableApps || actionableApps.length === 0) {
                console.log('No actionable notifications found. Rendering empty state.');
                container.innerHTML = `
                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                    <div class="flex flex-col items-center gap-2">
                        <span class="material-symbols-outlined text-4xl text-slate-300">notifications_off</span>
                        <p class="text-slate-500 text-sm">目前無新通知</p>
                    </div>
                </div>`;
                return;
            }

            // Limit to top 3 for dashboard
            const displayApps = actionableApps.slice(0, 3);

            displayApps.forEach(app => {
                const card = createNotificationCard(app);
                if (card) container.appendChild(card);
            });

        } catch (e) {
            console.error('Error rendering notifications:', e);
            container.innerHTML = `<div class="p-4 text-center text-red-500 text-sm">渲染通知時發生錯誤</div>`;
        }
    }

    function createApplicationRow(app) {
        // Map status to UI style
        let statusHtml = '';
        const safeAmount = app.amount ? parseInt(app.amount).toLocaleString() : '0';
        const safeName = app.scholarship_name || '未知獎學金';

        const statusInt = parseInt(app.status, 10);
        const comment = (app.review_comment || '').replace(/"/g, '&quot;');

        switch (statusInt) {
            case 1: // Approved
                statusHtml = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300"><span class="size-1.5 rounded-full bg-green-500"></span>已通過</span>`;
                break;
            case 0: // Rejected
                statusHtml = `<span data-comment="${comment}" onclick="event.stopPropagation(); showReason(this.dataset.comment)" class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"><span class="size-1.5 rounded-full bg-red-500"></span>未通過 <span class="material-symbols-outlined text-[14px]">info</span></span>`;
                break;
            case 2: // Needs Action
                statusHtml = `<span data-comment="${comment}" onclick="event.stopPropagation(); showReason(this.dataset.comment)" class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors"><span class="size-1.5 rounded-full bg-orange-500 animate-pulse"></span>需補件 <span class="material-symbols-outlined text-[14px]">info</span></span>`;
                break;
            case 3: // Pending
            default:
                statusHtml = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"><span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>審核中</span>`;
        }

        const isNeedsAction = statusInt === 2;
        const actionText = isNeedsAction ? '前往補件' : '詳細狀態';
        const actionUrl = isNeedsAction
            ? `application-form.html?application_id=${app.id}`
            : `application-form.html?application_id=${app.id}&mode=view`;
        const actionClass = isNeedsAction
            ? "px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-bold transition-all"
            : "text-slate-500 font-bold hover:text-slate-700 dark:hover:text-slate-300";

        const tr = document.createElement('tr');
        tr.className = "hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors";
        tr.innerHTML = `
            <td class="px-6 py-4">
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">${safeName}</span>
                    <span class="text-xs text-slate-500">${app.academic_year}學年度第${app.semester}學期</span>
                </div>
            </td>
            <td class="px-6 py-4 text-slate-600 dark:text-slate-300">${app.application_date}</td>
            <td class="px-6 py-4 text-slate-600 dark:text-slate-300">NT$ ${safeAmount}</td>
            <td class="px-6 py-4">${statusHtml}</td>
            <td class="px-6 py-4 text-right">
                <button 
                    class="${actionClass}"
                    onclick="window.location.href='${actionUrl}'">
                    ${actionText}
                </button>
            </td>
        `;
        return tr;
    }

    function setupApplicationsModal(applications) {
        const btn = document.getElementById('show-more-applications-btn');
        const modal = document.getElementById('applications-modal');
        const closeBtn = document.getElementById('close-app-modal-btn');
        const backdrop = document.getElementById('app-modal-backdrop');
        const modalContent = document.getElementById('app-modal-content');
        const modalBody = document.getElementById('modal-applications-list');

        if (!btn || !modal) return;

        if (applications.length > 2) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }

        function openModal() {
            modal.classList.remove('hidden');
            // Trigger reflow
            void modal.offsetWidth;

            backdrop.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');

            // Render ALL applications
            modalBody.innerHTML = '';
            applications.forEach(app => {
                const tr = createApplicationRow(app);
                modalBody.appendChild(tr);
            });
        }

        function closeModal() {
            backdrop.classList.add('opacity-0');
            modalContent.classList.add('scale-95', 'opacity-0');
            modalContent.classList.remove('scale-100', 'opacity-100');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300); // Match transition duration
        }

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
    }

    function setupNotificationsModal(applications) {
        const btn = document.getElementById('view-all-notifications-btn');
        const modal = document.getElementById('notifications-modal');
        const closeBtn = document.getElementById('close-notif-modal-btn');
        const backdrop = document.getElementById('notif-modal-backdrop');
        const modalContent = document.getElementById('notif-modal-content');
        const modalList = document.getElementById('modal-notifications-list');

        if (!btn || !modal) return;

        function openModal() {
            modal.classList.remove('hidden');
            // Trigger reflow
            void modal.offsetWidth;

            backdrop.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');

            // Render ALL notifications
            modalList.innerHTML = '';
            const actionableApps = applications.filter(app => parseInt(app.status, 10) === 1 || parseInt(app.status, 10) === 2);

            if (actionableApps.length === 0) {
                modalList.innerHTML = `<div class="p-4 text-center text-slate-500">無通知</div>`;
            } else {
                actionableApps.forEach(app => {
                    const card = createNotificationCard(app);
                    if (card) modalList.appendChild(card);
                });
            }
        }

        function closeModal() {
            backdrop.classList.add('opacity-0');
            modalContent.classList.add('scale-95', 'opacity-0');
            modalContent.classList.remove('scale-100', 'opacity-100');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300); // Match transition duration
        }

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
    }

    function setupReasonModal() {
        const modal = document.getElementById('reason-modal');
        const backdrop = document.getElementById('reason-modal-backdrop');
        const modalContent = document.getElementById('reason-modal-content');
        const closeBtn = document.getElementById('close-reason-modal-btn');
        const reasonText = document.getElementById('reason-modal-text');

        if (!modal) return;

        function closeModal() {
            backdrop.classList.replace('opacity-100', 'opacity-0');
            modalContent.classList.replace('opacity-100', 'opacity-0');
            modalContent.classList.replace('scale-100', 'scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        window.showReason = function (comment) {
            reasonText.textContent = comment || '尚無具體審核意見。';
            modal.classList.remove('hidden');
            // Trigger reflow
            void modal.offsetWidth;

            backdrop.classList.replace('opacity-0', 'opacity-100');
            modalContent.classList.replace('opacity-0', 'opacity-100');
            modalContent.classList.replace('scale-95', 'scale-100');
        };

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);

        // Add click listener to the stat card too
        const statAction = document.getElementById('stat-action');
        if (statAction) {
            statAction.style.cursor = 'pointer';
            statAction.addEventListener('click', () => {
                const needsActionApp = (window.allApplications || []).find(a => parseInt(a.status, 10) === 2);
                if (needsActionApp) {
                    window.showReason(needsActionApp.review_comment);
                } else {
                    // Check if there are rejected ones
                    const rejectedApp = (window.allApplications || []).find(a => parseInt(a.status, 10) === 0);
                    if (rejectedApp) {
                        window.showReason(rejectedApp.review_comment);
                    } else {
                        window.showReason('目前無需要補件或未通過的申請。');
                    }
                }
            });
        }
    }

    setupReasonModal();
});

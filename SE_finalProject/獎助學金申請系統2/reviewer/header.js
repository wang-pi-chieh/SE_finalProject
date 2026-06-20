const reviewerHeaderHTML = `
<header class="h-16 border-b border-border-light dark:border-border-dark flex items-center justify-between px-4 sm:px-6 bg-card-light dark:bg-card-dark sticky top-0 z-50">
    <div class="flex items-center gap-3 min-w-0">
        <div class="size-8 flex items-center justify-center bg-primary/10 rounded-lg text-primary shrink-0">
            <span class="material-symbols-outlined text-[24px]">school</span>
        </div>
        <a href="../index.html" class="text-base sm:text-lg font-bold hover:text-primary-dark transition-colors truncate">獎助學金審查系統</a>
    </div>

    <nav class="hidden md:flex items-center gap-6">
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="reviewer-dashboard.html">儀表板</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="applications.html">待審申請</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="applications.html?tab=history">審查紀錄</a>
    </nav>

    <div class="flex items-center gap-2 sm:gap-4">
        <button id="reviewer-mobile-menu-btn" type="button" class="md:hidden p-2 rounded-lg text-text-secondary-light dark:text-text-secondary-dark hover:bg-background-light dark:hover:bg-background-dark transition-colors" aria-expanded="false" aria-controls="reviewer-mobile-menu">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <button class="relative p-2 text-text-secondary-light dark:text-text-secondary-dark hover:bg-background-light dark:hover:bg-background-dark rounded-full transition-colors">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute top-2 right-2 size-2 bg-red-500 rounded-full border-2 border-card-light dark:border-card-dark"></span>
        </button>

        <div class="relative group">
            <button id="user-menu-btn" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                data-alt="User Avatar"
                style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDqz0f9lRK605M2xMJib8mFWj3wZNdMdL-N50TGLzxSQhGEur4AYnh3xwLriuXb9LGXARJVModYxob7c8L_ZxJHxmVEu4mzOQv0T3uQcNJMLAAAPN_JrurAjTbekiUO-_vMjSWhg_oIJMF5p7slpMUuZz9y-EREAqjyXqAXHKjo6-DxoZZLe_87it1lJGvsmBCBBj5TaX7zn0mmDKg_WB6wB8qnhmHmLCcPf61IzEJrLt50bTcw-DFDFrvGK-ZZQPv3DKyp4PKV9g");'>
            </button>
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50 transform origin-top-right transition-all duration-200">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p id="header-user-name" class="text-sm font-bold text-gray-900 dark:text-white">審查單位</p>
                    <p id="header-user-email" class="text-xs text-gray-500 dark:text-gray-400 truncate">reviewer@example.edu</p>
                </div>
                <a href="#" id="logout-btn" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    登出系統
                </a>
            </div>
        </div>
    </div>
</header>
<nav id="reviewer-mobile-menu" class="hidden md:hidden sticky top-16 z-40 border-b border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark px-4 py-3">
    <div class="grid grid-cols-1 gap-2">
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="reviewer-dashboard.html">儀表板</a>
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="applications.html">待審申請</a>
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="applications.html?tab=history">審查紀錄</a>
    </div>
</nav>
`;

document.body.insertAdjacentHTML('afterbegin', reviewerHeaderHTML);

const reviewerPreviewMode = new URLSearchParams(window.location.search).get('preview') === 'reviewer';
if (reviewerPreviewMode) {
    document.body.insertAdjacentHTML('afterbegin', `
        <div id="role-preview-banner" class="bg-amber-50 border-b border-amber-200 text-amber-900 px-4 sm:px-6 py-3 text-sm font-bold flex items-center justify-between gap-3">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">visibility</span>
                目前正在以審查單位身分預覽
            </span>
            <button type="button" id="close-role-preview-btn" class="text-primary hover:underline font-bold">返回管理員介面</button>
        </div>
    `);
    document.getElementById('close-role-preview-btn')?.addEventListener('click', () => {
        window.close();
        setTimeout(() => {
            window.location.href = '../admin/admin-dashboard.html';
        }, 150);
    });
}

loadIssueReportWidget();
bindReviewerMobileMenu();
loadReviewerUserData();
bindReviewerDropdown();
bindReviewerLogout();

function loadIssueReportWidget() {
    if (document.querySelector('script[data-issue-report-widget]')) return;

    const inject = () => {
        if (document.querySelector('script[data-issue-report-widget]') || window.__issueReportWidgetLoaded) return;

        const script = document.createElement('script');
        script.src = '../issue-report.js?v=20260604_autosave';
        script.dataset.issueReportWidget = 'true';
        document.body.appendChild(script);
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(inject, { timeout: 2000 });
        return;
    }

    window.setTimeout(inject, 1200);
}

function bindReviewerMobileMenu() {
    const button = document.getElementById('reviewer-mobile-menu-btn');
    const menu = document.getElementById('reviewer-mobile-menu');
    if (!button || !menu) return;

    button.addEventListener('click', () => {
        const isOpen = !menu.classList.contains('hidden');
        menu.classList.toggle('hidden', isOpen);
        button.setAttribute('aria-expanded', String(!isOpen));
    });
}

function loadReviewerUserData() {
    if (reviewerPreviewMode) {
        setReviewerHeaderUser('審查單位端預覽', 'reviewer-preview@example.edu');
        return;
    }

    const storedUser = localStorage.getItem('user');
    if (!storedUser) return;

    try {
        const user = JSON.parse(storedUser);
        setReviewerHeaderUser(user.real_name || user.username || '審查單位', user.email || '');
    } catch (error) {
        console.error('Error parsing user data:', error);
    }
}

function setReviewerHeaderUser(name, email) {
    const nameEl = document.getElementById('header-user-name');
    const emailEl = document.getElementById('header-user-email');
    if (nameEl) nameEl.textContent = name;
    if (emailEl) emailEl.textContent = email;
}

function bindReviewerDropdown() {
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');
    if (!userMenuBtn || !userDropdown) return;

    userMenuBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (event) => {
        if (!userMenuBtn.contains(event.target) && !userDropdown.contains(event.target)) {
            userDropdown.classList.add('hidden');
        }
    });
}

function bindReviewerLogout() {
    const logoutBtn = document.getElementById('logout-btn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (confirm('確定要登出系統嗎？')) {
            window.location.href = '../index.html';
        }
    });
}

window.updateHeaderActiveState = function (overrideTab) {
    const currentPath = window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = overrideTab || urlParams.get('tab') || 'pending';
    const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');

    navLinks.forEach((link) => {
        const href = link.getAttribute('href') || '';
        let isActive = false;

        link.classList.add('text-text-secondary-light', 'dark:text-text-secondary-dark', 'font-medium');
        link.classList.remove('text-primary', 'font-bold');

        if (currentPath.endsWith('application-review.html')) {
            isActive = href === 'applications.html';
        } else if (currentPath.endsWith('applications.html')) {
            if (href.includes('tab=history')) {
                isActive = activeTab === 'history';
            } else if (href === 'applications.html') {
                isActive = activeTab !== 'history';
            }
        } else if (currentPath.endsWith(href)) {
            isActive = true;
        }

        if (isActive) {
            link.classList.remove('text-text-secondary-light', 'dark:text-text-secondary-dark', 'text-slate-700', 'dark:text-slate-200', 'font-medium');
            link.classList.add('text-primary', 'font-bold');
        }
    });
};

window.updateHeaderActiveState();

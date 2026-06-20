const teacherHeaderHTML = `
<header class="h-16 border-b border-border-light dark:border-border-dark flex items-center justify-between px-4 sm:px-6 bg-card-light dark:bg-card-dark sticky top-0 z-50">
    <div class="flex items-center gap-3 min-w-0">
        <div class="size-8 flex items-center justify-center bg-primary/10 rounded-lg text-primary shrink-0">
            <span class="material-symbols-outlined text-[24px]">school</span>
        </div>
        <a href="../index.html" class="text-base sm:text-lg font-bold hover:text-primary-dark transition-colors truncate">獎助學金系統</a>
    </div>

    <nav class="hidden md:flex items-center gap-6">
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="teacher-dashboard.html">儀表板</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="recommendations.html">推薦獎助學金</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="student-search.html">學生查詢</a>
    </nav>

    <div class="flex items-center gap-2 sm:gap-4">
        <button id="teacher-mobile-menu-btn" type="button" class="md:hidden p-2 rounded-lg text-text-secondary-light dark:text-text-secondary-dark hover:bg-background-light dark:hover:bg-background-dark transition-colors" aria-expanded="false" aria-controls="teacher-mobile-menu">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <div class="relative group">
            <button id="user-menu-btn" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                data-alt="User Avatar"
                style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuC2DT2WPY9WZdToGh6B2S_P9O5GjCeoXMKgZZMpFAOillfWSCwBmDlyLe2vz9Ba8TwIaFUpHlay0NDFpAmGfw6CaIf9iBZ49fxBSp8lB7l7HB9qhXaHtn5icNEJDpPZi0IERiAVRZeE7c_7RoZTlrCvpYMqvOdVuLfmbe3CmNYiefbQsoLlpt4gnVAZD64BqZTT85xrUN_vohH03hRNAu4rLj2-HQPfdoNVlcHwpaK_-9cjWWfzc37jcmPuPsnhg4v74P87lv-WMw");'>
            </button>
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50 transform origin-top-right transition-all duration-200">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p id="header-user-name" class="text-sm font-bold text-gray-900 dark:text-white">老師</p>
                    <p id="header-user-email" class="text-xs text-gray-500 dark:text-gray-400 truncate">teacher@university.edu</p>
                </div>
                <a href="#" id="logout-btn" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    登出系統
                </a>
            </div>
        </div>
    </div>
</header>
<nav id="teacher-mobile-menu" class="hidden md:hidden sticky top-16 z-40 border-b border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark px-4 py-3">
    <div class="grid grid-cols-1 gap-2">
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="teacher-dashboard.html">儀表板</a>
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="recommendations.html">推薦獎助學金</a>
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="student-search.html">學生查詢</a>
    </div>
</nav>
`;

document.body.insertAdjacentHTML('afterbegin', teacherHeaderHTML);

const teacherPreviewMode = new URLSearchParams(window.location.search).get('preview') === 'teacher';
if (teacherPreviewMode) {
    document.body.insertAdjacentHTML('afterbegin', `
        <div id="role-preview-banner" class="bg-amber-50 border-b border-amber-200 text-amber-900 px-4 sm:px-6 py-3 text-sm font-bold flex items-center justify-between gap-3">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">visibility</span>
                目前正在以老師身分預覽
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
bindTeacherMobileMenu();
loadTeacherUserData();
bindTeacherDropdown();
bindTeacherLogout();
setTeacherActiveState();

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

function bindTeacherMobileMenu() {
    const button = document.getElementById('teacher-mobile-menu-btn');
    const menu = document.getElementById('teacher-mobile-menu');
    if (!button || !menu) return;

    button.addEventListener('click', () => {
        const isOpen = !menu.classList.contains('hidden');
        menu.classList.toggle('hidden', isOpen);
        button.setAttribute('aria-expanded', String(!isOpen));
    });
}

function loadTeacherUserData() {
    if (teacherPreviewMode) {
        setTeacherHeaderUser('老師身分預覽', 'teacher-preview@example.edu');
        return;
    }

    const storedUser = localStorage.getItem('user');
    if (!storedUser) return;

    try {
        const user = JSON.parse(storedUser);
        setTeacherHeaderUser(user.real_name || user.username || '老師', user.email || '');
    } catch (error) {
        console.error('Error parsing user data:', error);
    }
}

function setTeacherHeaderUser(name, email) {
    const nameEl = document.getElementById('header-user-name');
    const emailEl = document.getElementById('header-user-email');
    if (nameEl) nameEl.textContent = name;
    if (emailEl) emailEl.textContent = email;
}

function bindTeacherDropdown() {
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

function bindTeacherLogout() {
    const logoutBtn = document.getElementById('logout-btn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (confirm('確定要登出系統嗎？')) {
            window.location.href = '../index.html';
        }
    });
}

function setTeacherActiveState() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');

    navLinks.forEach((link) => {
        const href = link.getAttribute('href') || '';
        if (currentPath.endsWith(href)) {
            link.classList.remove('text-text-secondary-light', 'dark:text-text-secondary-dark', 'text-slate-700', 'dark:text-slate-200', 'font-medium');
            link.classList.add('text-primary', 'font-bold');
        }
    });
}

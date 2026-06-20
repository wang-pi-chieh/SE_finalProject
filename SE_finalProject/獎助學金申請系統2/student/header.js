const studentHeaderHTML = `
<header class="h-16 border-b border-border-light dark:border-border-dark flex items-center justify-between px-4 sm:px-6 bg-card-light dark:bg-card-dark sticky top-0 z-50">
    <div class="flex items-center gap-3 min-w-0">
        <a href="../index.html" class="flex items-center gap-2 group min-w-0">
            <div class="flex items-center justify-center size-8 rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-colors shrink-0">
                <span class="material-symbols-outlined text-xl">school</span>
            </div>
            <span class="text-base sm:text-lg font-bold text-[#111318] dark:text-white tracking-tight truncate">獎助學金申請系統</span>
        </a>
    </div>

    <nav class="hidden md:flex items-center gap-6">
        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="student-dashboard.html">儀表板</a>
        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="scholarships.html">獎助學金列表</a>
        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="application-form.html">申請表單</a>
        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="profile.html">個人資料</a>
    </nav>

    <div class="flex items-center gap-2 sm:gap-4">
        <button id="student-mobile-menu-btn" type="button" class="md:hidden p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" aria-expanded="false" aria-controls="student-mobile-menu">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <div class="relative">
            <button id="user-menu-btn" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuD0Yzw4_qtxDPgDOj0lqBcUCHcaMTpTIbzyws4kDcf6_b-I8_ZVZhtkmgu5dp5qpWQes6Nw-1lkru7eldkZ92PayBxhN0ria9x71bxvG80JBJv2szLK4AAcZK8gyYx93GgP2SBt3pb_8lohDK5FJw9IzV0G5jU-F-lOLvWXwZPzWjcwRx8uFkngz618FRnkuskIEZIaVuEcy7WnDsAU_2m_x0AoI9qfIvCwKdQa7Hh-cqpv_ZcOwYsBYwkioP49XxpF5GwmLQ849w");'>
            </button>
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50 transform origin-top-right transition-all duration-200">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p id="header-user-name" class="text-sm font-bold text-gray-900 dark:text-white">學生</p>
                    <p id="header-user-email" class="text-xs text-gray-500 dark:text-gray-400 truncate">user@example.com</p>
                </div>
                <a href="profile.html" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">person</span>
                    個人資料
                </a>
                <a href="#" id="logout-btn" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    登出系統
                </a>
            </div>
        </div>
    </div>
</header>
<nav id="student-mobile-menu" class="hidden md:hidden sticky top-16 z-40 border-b border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark px-4 py-3">
    <div class="grid grid-cols-1 gap-2">
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="student-dashboard.html">儀表板</a>
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="scholarships.html">獎助學金列表</a>
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="application-form.html">申請表單</a>
        <a class="mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" href="profile.html">個人資料</a>
    </div>
</nav>
`;

document.body.insertAdjacentHTML('afterbegin', studentHeaderHTML);

const studentPreviewMode = new URLSearchParams(window.location.search).get('preview') === 'student';
if (studentPreviewMode) {
    document.body.insertAdjacentHTML('afterbegin', `
        <div id="role-preview-banner" class="bg-amber-50 border-b border-amber-200 text-amber-900 px-4 sm:px-6 py-3 text-sm font-bold flex items-center justify-between gap-3">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">visibility</span>
                目前正在以學生身分預覽
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
bindMobileMenu();
loadUserData();
bindDropdown();
bindLogout();
setActiveState();

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

function bindMobileMenu() {
    const button = document.getElementById('student-mobile-menu-btn');
    const menu = document.getElementById('student-mobile-menu');
    if (!button || !menu) return;

    button.addEventListener('click', () => {
        const isOpen = !menu.classList.contains('hidden');
        menu.classList.toggle('hidden', isOpen);
        button.setAttribute('aria-expanded', String(!isOpen));
    });
}

function loadUserData() {
    if (studentPreviewMode) {
        setHeaderUser('學生端預覽', 'student-preview@example.edu');
        return;
    }

    const storedUser = localStorage.getItem('user');
    if (!storedUser) return;

    try {
        const user = JSON.parse(storedUser);
        setHeaderUser(user.real_name || user.username || '學生', user.email || '');
    } catch (error) {
        console.error('Error parsing user data:', error);
    }
}

function setHeaderUser(name, email) {
    const nameEl = document.getElementById('header-user-name');
    const emailEl = document.getElementById('header-user-email');
    if (nameEl) nameEl.textContent = name;
    if (emailEl) emailEl.textContent = email;
}

function bindDropdown() {
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

function bindLogout() {
    const logoutBtn = document.getElementById('logout-btn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (confirm('確定要登出系統嗎？')) {
            window.location.href = '../index.html';
        }
    });
}

function setActiveState() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');

    navLinks.forEach((link) => {
        const href = link.getAttribute('href') || '';
        let isActive = currentPath.endsWith(href);

        if (currentPath.includes('application-form.html') && href === 'application-form.html') {
            isActive = true;
        }

        if (isActive) {
            link.classList.remove('text-slate-600', 'dark:text-slate-300', 'text-slate-700', 'dark:text-slate-200', 'font-medium');
            link.classList.add('text-primary', 'font-bold');
        }
    });
}

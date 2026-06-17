const teacherHeaderHTML = `
<header class="h-16 border-b border-border-light dark:border-border-dark flex items-center justify-between px-6 bg-card-light dark:bg-card-dark sticky top-0 z-50">
    <div class="flex items-center gap-4">
        <div class="size-8 flex items-center justify-center bg-primary/10 rounded-lg text-primary">
            <span class="material-symbols-outlined text-[24px]">school</span>
        </div>
        <a href="../index.html" class="text-lg font-bold hover:text-primary-dark transition-colors">獎學金助學系統</a>
    </div>

    <nav class="hidden md:flex items-center gap-6">
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="teacher-dashboard.html">儀表板</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="recommendations.html">推薦獎助學金</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="student-search.html">學生查詢 (導師)</a>
    </nav>

    <div class="flex items-center gap-4">
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
`;

document.body.insertAdjacentHTML('afterbegin', teacherHeaderHTML);

const teacherPreviewMode = new URLSearchParams(window.location.search).get('preview') === 'teacher';
if (teacherPreviewMode) {
    document.body.insertAdjacentHTML('afterbegin', `
        <div id="role-preview-banner" class="bg-amber-50 border-b border-amber-200 text-amber-900 px-6 py-3 text-sm font-bold flex items-center justify-between gap-3">
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

function loadIssueReportWidget() {
    if (document.querySelector('script[data-issue-report-widget]')) return;

    const script = document.createElement('script');
    script.src = '../issue-report.js?v=20260604_autosave';
    script.dataset.issueReportWidget = 'true';
    document.body.appendChild(script);
}

const storedUser = localStorage.getItem('user');
if (teacherPreviewMode) {
    const nameEl = document.getElementById('header-user-name');
    const emailEl = document.getElementById('header-user-email');
    if (nameEl) nameEl.textContent = '老師身分預覽';
    if (emailEl) emailEl.textContent = 'teacher-preview@example.edu';
} else if (storedUser) {
    try {
        const user = JSON.parse(storedUser);
        const nameEl = document.getElementById('header-user-name');
        const emailEl = document.getElementById('header-user-email');
        if (nameEl) nameEl.textContent = user.real_name || user.username;
        if (emailEl) emailEl.textContent = user.email || '';
    } catch (e) {
        console.error('Error parsing user data:', e);
    }
}

const currentPath = window.location.pathname;
const navLinks = document.querySelectorAll('.nav-link');

navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (currentPath.endsWith(href)) {
        link.classList.remove('text-text-secondary-light', 'dark:text-text-secondary-dark', 'font-medium');
        link.classList.add('text-primary', 'font-bold');
    }
});

const userMenuBtn = document.getElementById('user-menu-btn');
const userDropdown = document.getElementById('user-dropdown');
const logoutBtn = document.getElementById('logout-btn');

if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
}

if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm('確定要登出系統嗎？')) {
            window.location.href = '../index.html';
        }
    });
}

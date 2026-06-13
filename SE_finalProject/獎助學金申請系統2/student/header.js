const headerHTML = `
<header class="h-16 border-b border-border-light dark:border-border-dark flex items-center justify-between px-6 bg-card-light dark:bg-card-dark sticky top-0 z-50">
    <div class="flex items-center gap-4">
        <a href="../index.html" class="flex items-center gap-2 group">
            <div class="flex items-center justify-center size-8 rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                <span class="material-symbols-outlined text-xl">school</span>
            </div>
            <span class="text-lg font-bold text-[#111318] dark:text-white tracking-tight">?摮賊?蝟餌絞</span>
        </a>
    </div>

    <nav class="hidden md:flex items-center gap-6 absolute left-1/2 -translate-x-1/2">
        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="student-dashboard.html">蝮質汗</a>
        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="scholarships.html">?汗?飛??/a>
        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="student-dashboard.html">???唾?</a>

        <a class="nav-link text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="profile.html">?犖瑼?</a>
    </nav>

    <div class="flex items-center gap-4">

        <div class="relative">
            <button id="user-menu-btn" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuD0Yzw4_qtxDPgDOj0lqBcUCHcaMTpTIbzyws4kDcf6_b-I8_ZVZhtkmgu5dp5qpWQes6Nw-1lkru7eldkZ92PayBxhN0ria9x71bxvG80JBJv2szLK4AAcZK8gyYx93GgP2SBt3pb_8lohDK5FJw9IzV0G5jU-F-lOLvWXwZPzWjcwRx8uFkngz618FRnkuskIEZIaVuEcy7WnDsAU_2m_x0AoI9qfIvCwKdQa7Hh-cqpv_ZcOwYsBYwkioP49XxpF5GwmLQ849w");'>
            </button>
            <!-- Dropdown Menu -->
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50 transform origin-top-right transition-all duration-200">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p id="header-user-name" class="text-sm font-bold text-gray-900 dark:text-white">雿輻??/p>
                    <p id="header-user-email" class="text-xs text-gray-500 dark:text-gray-400 truncate">user@example.com</p>
                </div>
                <a href="profile.html" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">person</span>
                    ?犖瑼?
                </a>
                <a href="#" id="logout-btn" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    ?餃蝟餌絞
                </a>
            </div>
        </div>
    </div>
</header>
`;

// Insert header
document.body.insertAdjacentHTML('afterbegin', headerHTML);
const studentPreviewMode = new URLSearchParams(window.location.search).get('preview') === 'student';
if (studentPreviewMode) {
    document.body.insertAdjacentHTML('afterbegin', `
        <div id="role-preview-banner" class="bg-amber-50 border-b border-amber-200 text-amber-900 px-6 py-3 text-sm font-bold flex items-center justify-between gap-3">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">visibility</span>
                ?桀??箄??脤?閬踝?摮貊?蝡?            </span>
            <button type="button" id="close-role-preview-btn" class="text-primary hover:underline font-bold">餈?蝟餌絞蝞∠???/button>
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

// Load user data
const storedUser = localStorage.getItem('user');
if (studentPreviewMode) {
    const nameEl = document.getElementById('header-user-name');
    const emailEl = document.getElementById('header-user-email');
    if (nameEl) nameEl.textContent = '摮貊?蝡舫?閬?;
    if (emailEl) emailEl.textContent = 'student-preview@example.edu';
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

// Dropdown Logic
const userMenuBtn = document.getElementById('user-menu-btn');
const userDropdown = document.getElementById('user-dropdown');
const logoutBtn = document.getElementById('logout-btn');

if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
}

// Logout Logic
if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // Here you would typically handle clearing session/tokens
        if (confirm('蝣箏?閬?箇頂蝯勗?嚗?)) {
            window.location.href = '../index.html';
        }
    });
}

// Set active state
const currentPath = window.location.pathname;
const navLinks = document.querySelectorAll('.nav-link');

navLinks.forEach(link => {
    // Get the href attribute (e.g., "student-dashboard.html")
    const href = link.getAttribute('href');

    // Check if the current path includes the href (handling situations like /path/to/student-dashboard.html)
    // Also special handling for application-form.html to highlight "???唾?" (student-dashboard.html)

    let isActive = false;

    if (currentPath.endsWith(href)) {
        isActive = true;
    } else if (currentPath.endsWith('/') && href === 'index.html') {
        isActive = true; // Handle root path
    } else if (currentPath.includes('application-form.html') && href === 'student-dashboard.html') {
        isActive = true; // Highlight "???唾?" when in application form
    }

    if (isActive) {
        link.classList.remove('text-slate-600', 'dark:text-slate-300', 'font-medium');
        link.classList.add('text-primary', 'font-bold');
    }
});


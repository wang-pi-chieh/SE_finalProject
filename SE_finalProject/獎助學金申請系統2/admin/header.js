document.addEventListener('DOMContentLoaded', () => {
    const headerHTML = `
    <header class="fixed top-0 left-0 right-0 z-50 w-full transition-all duration-300 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md shadow-sm">
        <div class="layout-container max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 h-16">
            <div class="flex items-center justify-between h-full">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="../index.html" class="flex items-center gap-2 group min-w-0">
                        <div class="flex items-center justify-center size-8 rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-colors shrink-0">
                            <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                        </div>
                        <span class="text-base sm:text-lg font-bold text-[#111318] dark:text-white tracking-tight truncate">獎助學金系統</span>
                    </a>
                </div>

                <nav class="hidden md:flex items-center gap-1">
                    <a href="admin-dashboard.html" class="admin-nav-link px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">儀表板</a>
                    <a href="system-settings.html" class="admin-nav-link px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">系統設定</a>
                    <a href="home-announcements.html" class="admin-nav-link px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">首頁公告</a>
                    <a href="user-management.html" class="admin-nav-link px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">使用者管理</a>
                    <a href="../help.html" class="admin-nav-link px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Help</a>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    <button id="admin-mobile-menu-btn" type="button" class="md:hidden p-2 rounded-lg text-[#616f89] dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" aria-expanded="false" aria-controls="admin-mobile-menu">
                        <span class="material-symbols-outlined">menu</span>
                    </button>

                    <div class="flex items-center gap-3 pl-1">
                        <div class="text-right hidden sm:block">
                            <p class="header-user-name text-xs font-bold text-[#111318] dark:text-white leading-none mb-1">管理員</p>
                            <p class="header-user-email text-[10px] text-[#616f89] dark:text-gray-500 font-medium leading-none">admin@university.edu</p>
                        </div>
                        <div class="relative">
                            <button id="user-menu-btn" class="size-9 rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                <span class="material-symbols-outlined text-[20px]">person</span>
                            </button>
                            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50 transform origin-top-right transition-all duration-200">
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 block sm:hidden">
                                    <p class="header-user-name text-sm font-bold text-gray-900 dark:text-white">管理員</p>
                                    <p class="header-user-email text-xs text-gray-500 dark:text-gray-400 truncate">admin@university.edu</p>
                                </div>
                                <a href="system-settings.html" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">settings</span>
                                    系統設定
                                </a>
                                <a href="#" id="logout-btn" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">logout</span>
                                    登出系統
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <nav id="admin-mobile-menu" class="hidden md:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="grid grid-cols-1 gap-2">
                <a href="admin-dashboard.html" class="admin-mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-[#616f89] dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">儀表板</a>
                <a href="system-settings.html" class="admin-mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-[#616f89] dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">系統設定</a>
                <a href="home-announcements.html" class="admin-mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-[#616f89] dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">首頁公告</a>
                <a href="user-management.html" class="admin-mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-[#616f89] dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">使用者管理</a>
                <a href="../help.html" class="admin-mobile-nav-link rounded-lg px-3 py-2 text-sm font-bold text-[#616f89] dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">Help</a>
            </div>
        </nav>
    </header>
    <div class="h-16"></div>
    `;

    document.body.insertAdjacentHTML('afterbegin', headerHTML);
    bindAdminMobileMenu();
    setAdminActiveState();
    loadAdminUserData();
    bindAdminDropdown();
    bindAdminLogout();
});

function bindAdminMobileMenu() {
    const button = document.getElementById('admin-mobile-menu-btn');
    const menu = document.getElementById('admin-mobile-menu');
    if (!button || !menu) return;

    button.addEventListener('click', () => {
        const isOpen = !menu.classList.contains('hidden');
        menu.classList.toggle('hidden', isOpen);
        button.setAttribute('aria-expanded', String(!isOpen));
    });
}

function setAdminActiveState() {
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll('.admin-nav-link, .admin-mobile-nav-link');
    const inactiveClasses = ['text-[#616f89]', 'dark:text-gray-400', 'dark:text-gray-300', 'hover:text-[#111318]', 'dark:hover:text-white'];
    const activeClasses = ['text-primary', 'bg-primary/5'];

    links.forEach((link) => {
        const href = link.getAttribute('href') || '';
        const isActive = currentPath.endsWith(href);
        link.classList.remove(...activeClasses);
        if (isActive) {
            link.classList.remove(...inactiveClasses);
            link.classList.add(...activeClasses);
        }
    });
}

function loadAdminUserData() {
    const storedUser = localStorage.getItem('user');
    if (!storedUser) return;

    try {
        const user = JSON.parse(storedUser);
        const nameEls = document.querySelectorAll('.header-user-name');
        const emailEls = document.querySelectorAll('.header-user-email');
        nameEls.forEach((el) => { el.textContent = user.real_name || user.username || '管理員'; });
        emailEls.forEach((el) => { el.textContent = user.email || ''; });
    } catch (error) {
        console.error('Error parsing user data:', error);
    }
}

function bindAdminDropdown() {
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

function bindAdminLogout() {
    const logoutBtn = document.getElementById('logout-btn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (confirm('確定要登出系統嗎？')) {
            window.location.href = '../index.html';
        }
    });
}

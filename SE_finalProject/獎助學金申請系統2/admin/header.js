document.addEventListener('DOMContentLoaded', () => {
    const headerHTML = `
    <header class="fixed top-0 left-0 right-0 z-50 w-full transition-all duration-300 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-sm">
        <div class="layout-container max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 h-16">
            <div class="flex items-center justify-between h-full">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <a href="../index.html" class="flex items-center gap-2 group">
                        <div class="flex items-center justify-center size-8 rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                        </div>
                        <span class="text-lg font-bold text-[#111318] dark:text-white tracking-tight">獎助學金系統</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center gap-1">
                    <a href="admin-dashboard.html" class="px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        儀表板
                    </a>
                    <a href="system-settings.html" class="px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        系統設定
                    </a>
                    <a href="home-announcements.html" class="px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        首頁公告
                    </a>
                    <a href="user-management.html" class="px-4 py-2 rounded-lg text-sm font-medium text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        使用者管理
                    </a>
                </nav>

                <!-- User Profile & Actions -->
                <div class="flex items-center gap-3">


                    <div class="flex items-center gap-3 pl-1">
                        <div class="text-right hidden sm:block">
                            <p class="header-user-name text-xs font-bold text-[#111318] dark:text-white leading-none mb-1">管理員</p>
                            <p class="header-user-email text-[10px] text-[#616f89] dark:text-gray-500 font-medium leading-none">admin@university.edu</p>
                        </div>
                    <div class="relative">
                        <button id="user-menu-btn" class="size-9 rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-[#616f89] dark:text-gray-400 hover:text-[#111318] dark:hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            <span class="material-symbols-outlined text-[20px]">person</span>
                        </button>
                        <!-- Dropdown Menu -->
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

                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 rounded-lg text-[#616f89] dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors ml-1">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>
    </div>
    </header>
    <div class="h-16"></div> <!-- Spacer -->
    `;

    // Inject Header
    document.body.insertAdjacentHTML('afterbegin', headerHTML);

    // Navigation Active State Logic
    const currentPath = window.location.pathname;
    const navDashboard = document.querySelector('a[href="admin-dashboard.html"]');
    const navSettings = document.querySelector('a[href="system-settings.html"]');
    const navHomeAnnouncements = document.querySelector('a[href="home-announcements.html"]');
    const navUsers = document.querySelector('a[href="user-management.html"]');

    const activeClasses = ['text-primary', 'bg-primary/5'];
    const inactiveClasses = ['text-[#616f89]', 'dark:text-gray-400', 'hover:text-[#111318]', 'dark:hover:text-white', 'hover:bg-gray-50', 'dark:hover:bg-gray-800', 'transition-colors'];

    function setActive(element) {
        if (!element) return;
        element.classList.remove(...inactiveClasses);
        element.classList.add(...activeClasses);
    }

    function setInactive(element) {
        if (!element) return;
        element.classList.remove(...activeClasses);
        element.classList.add(...inactiveClasses);
    }

    // Default to inactive
    setInactive(navDashboard);
    setInactive(navSettings);
    setInactive(navHomeAnnouncements);
    setInactive(navUsers);

    // Apply active state - Robust check
    console.log('Current Path:', currentPath); // Debug
    if (currentPath.indexOf('system-settings.html') !== -1) {
        setActive(navSettings);
    } else if (currentPath.indexOf('home-announcements.html') !== -1) {
        setActive(navHomeAnnouncements);
    } else if (currentPath.indexOf('user-management.html') !== -1) {
        setActive(navUsers);
    } else if (currentPath.indexOf('admin-dashboard.html') !== -1) {
        setActive(navDashboard);
    } else {
        // Default fallback
        setActive(navDashboard);
    }

    // Load user data
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            const user = JSON.parse(storedUser);
            const nameEls = document.querySelectorAll('.header-user-name');
            const emailEls = document.querySelectorAll('.header-user-email');

            nameEls.forEach(el => el.textContent = user.real_name || user.username);
            emailEls.forEach(el => el.textContent = user.email || '');
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
            if (confirm('確定要登出系統嗎？')) {
                window.location.href = '../index.html';
            }
        });
    }
});

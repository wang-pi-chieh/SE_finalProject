const reviewerHeaderHTML = `
<header class="h-16 border-b border-border-light dark:border-border-dark flex items-center justify-between px-6 bg-card-light dark:bg-card-dark sticky top-0 z-50">
    <div class="flex items-center gap-4">
        <div class="size-8 flex items-center justify-center bg-primary/10 rounded-lg text-primary">
            <span class="material-symbols-outlined text-[24px]">school</span>
        </div>
        <a href="../index.html" class="text-lg font-bold hover:text-primary-dark transition-colors">?飛?恣?頂蝯?/a>
    </div>

    <nav class="hidden md:flex items-center gap-6">
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="reviewer-dashboard.html">?銵冽</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="applications.html">?唾?獢辣</a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="applications.html?tab=history">甇瑕蝝??/a>
        <a class="nav-link text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="application-review.html">?唾?撖拇</a>
    </nav>

    <div class="flex items-center gap-4">
        <button class="relative p-2 text-text-secondary-light dark:text-text-secondary-dark hover:bg-background-light dark:hover:bg-background-dark rounded-full transition-colors">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute top-2 right-2 size-2 bg-red-500 rounded-full border-2 border-card-light dark:border-card-dark"></span>
        </button>

        <div class="relative group">
             <button id="user-menu-btn" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                data-alt="User Avatar"
                style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDqz0f9lRK605M2xMJib8mFWj3wZNdMdL-N50TGLzxSQhGEur4AYnh3xwLriuXb9LGXARJVModYxob7c8L_ZxJHxmVEu4mzOQv0T3uQcNJMLAAAPN_JrurAjTbekiUO-_vMjSWhg_oIJMF5p7slpMUuZz9y-EREAqjyXqAXHKjo6-DxoZZLe_87it1lJGvsmBCBBj5TaX7zn0mmDKg_WB6wB8qnhmHmLCcPf61IzEJrLt50bTcw-DFDFrvGK-ZZQPv3DKyp4PKV9g");'>
            </button>
            <!-- Dropdown Menu -->
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50 transform origin-top-right transition-all duration-200">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p id="header-user-name" class="text-sm font-bold text-gray-900 dark:text-white">蝞∠???/p>
                    <p id="header-user-email" class="text-xs text-gray-500 dark:text-gray-400 truncate">admin@university.edu</p>
                </div>
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
document.body.insertAdjacentHTML('afterbegin', reviewerHeaderHTML);
const reviewerPreviewMode = new URLSearchParams(window.location.search).get('preview') === 'reviewer';
if (reviewerPreviewMode) {
  document.body.insertAdjacentHTML('afterbegin', `
    <div id="role-preview-banner" class="bg-amber-50 border-b border-amber-200 text-amber-900 px-6 py-3 text-sm font-bold flex items-center justify-between gap-3">
      <span class="flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">visibility</span>
        ?桀??箄??脤?閬踝?撖拇?桐?蝡?      </span>
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
if (reviewerPreviewMode) {
  const nameEl = document.getElementById('header-user-name');
  const emailEl = document.getElementById('header-user-email');
  if (nameEl) nameEl.textContent = '撖拇?桐?蝡舫?閬?;
  if (emailEl) emailEl.textContent = 'reviewer-preview@example.edu';
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

// Set active state
window.updateHeaderActiveState = function (overrideTab) {
  const currentPath = window.location.pathname;
  const urlParams = new URLSearchParams(window.location.search);
  // Use overrideTab if provided, otherwise get from URL
  const activeTab = overrideTab || urlParams.get('tab') || 'pending';

  const navLinks = document.querySelectorAll('.nav-link');

  navLinks.forEach((link) => {
    const href = link.getAttribute('href');
    let isActive = false;

    // Reset first (remove active styles)
    link.classList.add(
      'text-text-secondary-light',
      'dark:text-text-secondary-dark',
      'font-medium'
    );
    link.classList.remove('text-primary', 'font-bold');

    // Logic for Applications Page
    if (currentPath.endsWith('applications.html')) {
      // 1. History Link (href="applications.html?tab=history")
      if (href.includes('tab=history')) {
        if (activeTab === 'history') {
          isActive = true;
        }
      }
      // 2. Applications Link (href="applications.html") - Active for 'pending' or default
      else if (href === 'applications.html') {
        if (activeTab !== 'history') {
          isActive = true;
        }
      }
    }
    // Logic for Other Pages (Dashboard, etc.)
    else {
      if (currentPath.endsWith(href)) {
        isActive = true;
      }
    }

    // Apply active styles
    if (isActive) {
      link.classList.remove(
        'text-text-secondary-light',
        'dark:text-text-secondary-dark',
        'font-medium'
      );
      link.classList.add('text-primary', 'font-bold');
    }
  });
};

// Initial call
window.updateHeaderActiveState();

// Dropdown Logic (Copied and adapted from student header)
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
    if (confirm('蝣箏?閬?箇頂蝯勗?嚗?)) {
      window.location.href = '../index.html';
    }
  });
}



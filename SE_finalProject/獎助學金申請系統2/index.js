document.addEventListener('DOMContentLoaded', () => {
    // --- Scroll Animations (IntersectionObserver) ---
    const observerOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    };

    const scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                // Optional: Stop observing once visible if you don't want it to re-animate
                // scrollObserver.unobserve(entry.target); 
            }
        });
    }, observerOptions);

    const scrollElements = document.querySelectorAll('.animate-on-scroll');
    scrollElements.forEach(el => scrollObserver.observe(el));


    // --- Flashlight Effect ---
    const cards = document.querySelectorAll('.flashlight-card');
    cards.forEach(card => {
        card.onmousemove = e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        };

        // Navigation logic (retained)
        card.addEventListener('click', () => {
            // Logic handled by IDs below, but keeping this structure if needed for general card clicks
        });
    });

    // --- Portal Navigation ---
    const studentPortal = document.getElementById('student-portal-card');
    if (studentPortal) {
        studentPortal.addEventListener('click', () => {
            window.location.href = 'student/student-dashboard.html';
        });
    }

    const reviewerPortal = document.getElementById('reviewer-portal-card');
    if (reviewerPortal) {
        reviewerPortal.addEventListener('click', () => {
            window.location.href = 'reviewer/reviewer-dashboard.html';
        });
    }

    const adminPortal = document.getElementById('admin-portal-card');
    if (adminPortal) {
        adminPortal.addEventListener('click', () => {
            window.location.href = 'admin-dashboard.html';
        });
    }


    // --- Text Animation (Split Text) ---
    // Logic moved to global.js for reusability
    // window.initTextAnimation() is called automatically there

    // --- Header Scroll Effect ---
    const header = document.getElementById('main-header');
    const loginBtn = document.getElementById('login-btn');

    function updateHeader() {
        if (window.scrollY > 20) {
            // Scrolled State (White Background)
            header.classList.remove('bg-transparent', 'text-white', 'py-4');
            header.classList.add('bg-white/90', 'dark:bg-slate-900/90', 'backdrop-blur-md', 'shadow-sm', 'border-b', 'border-gray-200/50', 'dark:border-gray-800/50', 'py-3', 'text-slate-900', 'dark:text-white');

            // Button turns to White/Ghost style to match theme
            if (loginBtn) {
                loginBtn.classList.remove('bg-primary', 'text-white', 'shadow-primary/30');
                loginBtn.classList.add('bg-white', 'text-primary', 'border', 'border-primary', 'shadow-sm');
            }
        } else {
            // Transparent State (Initial)
            header.classList.add('bg-transparent', 'text-white', 'py-4');
            header.classList.remove('bg-white/90', 'dark:bg-slate-900/90', 'backdrop-blur-md', 'shadow-sm', 'border-b', 'border-gray-200/50', 'dark:border-gray-800/50', 'py-3', 'text-slate-900', 'dark:text-white');

            // Button reverts to Primary Blue
            if (loginBtn) {
                loginBtn.classList.add('bg-primary', 'text-white', 'shadow-primary/30');
                loginBtn.classList.remove('bg-white', 'text-primary', 'border', 'border-primary', 'shadow-sm');
            }
        }
    }
    window.addEventListener('scroll', updateHeader);
    updateHeader(); // Initial check

    // --- Mobile Navigation ---
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu-panel');

    function closeMobileMenu() {
        if (!mobileMenu) return;

        mobileMenu.classList.add('hidden');
        if (mobileMenuBtn) {
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }
    }

    if (mobileMenu) {
        mobileMenu.querySelectorAll('a, button').forEach(item => {
            item.addEventListener('click', closeMobileMenu);
        });

        document.addEventListener('click', (event) => {
            if (!mobileMenu.contains(event.target) && (!mobileMenuBtn || !mobileMenuBtn.contains(event.target))) {
                closeMobileMenu();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeMobileMenu();
            }
        });
    }

    // --- Vertical Clip Text Animation ---
    function initTextAnimation(container) {
        if (!container) return;

        // Get text content - prefer data-text but fallback to innerText if not empty
        let textStr = container.getAttribute('data-text');
        if (!textStr && container.innerText.trim().length > 0) {
            textStr = container.innerText;
        }
        if (!textStr) return; // Nothing to animate

        container.setAttribute('data-text', textStr);
        container.innerHTML = '';
        container.style.opacity = '1';
        container.style.visibility = 'visible'; // Restore visibility

        const hasGradient = container.classList.contains('text-gradient-gold');

        textStr.split('').forEach((char, index) => {
            const span = document.createElement('span');
            span.textContent = char;
            span.className = 'char-clip';
            if (hasGradient) {
                span.classList.add('text-gradient-gold');
            }
            span.style.animationDelay = `${index * 0.05}s`;

            if (char === ' ') {
                span.style.width = '0.3em';
                span.style.display = 'inline-block';
            }

            container.appendChild(span);
        });
    }

    // --- Hero Carousel & Animation Triggers ---
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('#pagination-dots button');

    // Initial Text Animation for First Slide
    if (slides.length > 0) {
        const firstSlideTexts = slides[0].querySelectorAll('.reveal-text');
        firstSlideTexts.forEach(el => initTextAnimation(el));
    }

    if (slides.length > 0) {
        let currentSlide = 0;
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                const content = slide.querySelector('.slide-content');
                if (i === index) {
                    // Active Slide
                    slide.classList.remove('opacity-0', 'z-0');
                    slide.classList.add('opacity-100', 'z-10');

                    // Animate Content
                    if (content) {
                        content.classList.remove('translate-y-10', 'opacity-0');
                        content.classList.add('translate-y-0', 'opacity-100');

                        // Re-trigger Text Animation
                        const textContainers = slide.querySelectorAll('.reveal-text');
                        textContainers.forEach(el => initTextAnimation(el));
                    }
                } else {
                    // Inactive Slide
                    slide.classList.remove('opacity-100', 'z-10');
                    slide.classList.add('opacity-0', 'z-0');

                    // Reset Content
                    if (content) {
                        content.classList.remove('translate-y-0', 'opacity-100');
                        content.classList.add('translate-y-10', 'opacity-0');
                    }
                }
            });

            // Update Dots
            if (dots.length > 0) {
                dots.forEach((dot, i) => {
                    if (i === index) {
                        dot.className = "w-8 h-2.5 rounded-full bg-white transition-all duration-300";
                    } else {
                        dot.className = "w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300";
                    }
                });
            }
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Auto play
        let slideInterval = setInterval(nextSlide, 6000); // Changed to 6000ms to match ref

        // Arrows
        const prevBtn = document.getElementById('prev-slide');
        const nextBtn = document.getElementById('next-slide');

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                prevSlide();
                slideInterval = setInterval(nextSlide, 6000);
            });
            nextBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                nextSlide();
                slideInterval = setInterval(nextSlide, 6000);
            });
        }

        // Dots Interaction
        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                clearInterval(slideInterval);
                currentSlide = i;
                showSlide(currentSlide);
                slideInterval = setInterval(nextSlide, 6000);
            });
        });
    }

    // --- Latest Scholarships -> 最新公告 ---
    const announcementsContainer = document.getElementById('announcements-container');
    if (announcementsContainer) {
        // 先嘗試讀取「首頁公告管理」資料表
        fetch('api/get_homepage_announcements.php')
            .then(res => res.json())
            .then(result => {
                if (result.success && Array.isArray(result.data) && result.data.length > 0) {
                    announcementsContainer.innerHTML = '';

                    result.data.slice(0, 3).forEach(a => {
                        const card = document.createElement('div');
                        // 不使用 animate-on-scroll，避免沒有被 observer 追蹤導致 opacity 為 0 看不到
                        card.className = 'announcement-item p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-background-light dark:bg-gray-800 hover:border-primary/50 transition-colors cursor-default flex flex-col sm:flex-row gap-5 items-start';

                        let statusClass = 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
                        if (a.status_type === 'notice') {
                            statusClass = 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                        } else if (a.status_type === 'warning') {
                            statusClass = 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300';
                        }

                        const label = a.status_label || '';
                        const badge = label ? `<span class="${statusClass} text-xs px-2 py-1 rounded font-bold whitespace-nowrap ml-2">${label}</span>` : '';
                        const content = a.content || '';

                        card.innerHTML = `
                            <div class="bg-primary/10 text-primary p-3 rounded-xl shrink-0">
                                <span class="material-symbols-outlined text-2xl">campaign</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-1">
                                    <h3 class="font-bold text-[#111318] dark:text-white text-lg">${a.title}</h3>
                                    ${badge}
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">${content}</p>
                                ${a.display_date ? `<span class="text-xs text-gray-400 font-medium flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">event</span> ${a.display_date}
                                </span>` : ''}
                            </div>
                        `;

                        announcementsContainer.appendChild(card);
                    });
                } else {
                    // 若沒有自訂首頁公告，退回使用獎學金列表作為最新公告
                    loadAnnouncementsFromScholarships(announcementsContainer);
                }
            })
            .catch(err => {
                console.error('Failed to load homepage announcements:', err);
                loadAnnouncementsFromScholarships(announcementsContainer);
            });
    }

    function loadAnnouncementsFromScholarships(container) {
        fetch('api/get_scholarships.php')
            .then(res => res.json())
            .then(result => {
                if (!result.success || !Array.isArray(result.data)) return;

                const list = result.data;
                if (list.length === 0) return;

                container.innerHTML = '';
                const now = new Date();

                list.slice(0, 3).forEach(s => {
                    const card = document.createElement('div');
                    // 不使用 animate-on-scroll，避免沒有被 observer 追蹤導致 opacity 為 0 看不到
                    card.className = 'announcement-item p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-background-light dark:bg-gray-800 hover:border-primary/50 transition-colors cursor-default flex flex-col sm:flex-row gap-5 items-start';

                    const endDateStr = s.application_end_date || s.deadline || '';
                    let statusText = '進行中';
                    let statusTypeClass = 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';

                    if (endDateStr) {
                        const endDate = new Date(endDateStr);
                        if (endDate < now) {
                            statusText = '已截止';
                            statusTypeClass = 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                        }
                    }

                    const desc = s.description || '';
                    const shortDesc = desc.length > 80 ? desc.substring(0, 80) + '…' : (desc || '歡迎符合資格的同學申請此獎學金。');

                    card.innerHTML = `
                        <div class="bg-primary/10 text-primary p-3 rounded-xl shrink-0">
                            <span class="material-symbols-outlined text-2xl">campaign</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-bold text-[#111318] dark:text-white text-lg">${s.name}</h3>
                                <span class="${statusTypeClass} text-xs px-2 py-1 rounded font-bold whitespace-nowrap ml-2">${statusText}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">${shortDesc}</p>
                            ${endDateStr ? `<span class="text-xs text-gray-400 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">event</span> 截止日：${endDateStr}
                            </span>` : ''}
                        </div>
                    `;

                    container.appendChild(card);
                });
            })
            .catch(err => {
                console.error('Failed to load scholarships for announcements:', err);
            });
    }
});

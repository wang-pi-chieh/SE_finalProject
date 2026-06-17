// Scroll Animation Observer with Stagger
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px'
};

window.observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
            const target = entry.target;
            // Stagger effect: add delay based on index of simultaneously intersecting elements
            // If element has data-delay, use it; otherwise calculate stagger
            const delay = target.dataset.delay ? target.dataset.delay : (index % 10) * 100;
            target.style.animationDelay = `${delay}ms`;

            target.classList.add('is-visible');
            window.observer.unobserve(target);
        }
    });
}, observerOptions);

document.addEventListener('DOMContentLoaded', () => {
    // Flashlight Effect
    const cards = document.querySelectorAll(".flashlight-card");
    cards.forEach((card) => {
        card.onmousemove = (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty("--mouse-x", `${x}px`);
            card.style.setProperty("--mouse-y", `${y}px`);
        };
    });

    // Initialize Pixel Fragmentation Effect
    window.initPixelEffect = () => {
        const targets = document.querySelectorAll('.hover-pixel');
        targets.forEach(container => {
            const originalText = container.textContent;
            container.style.position = 'relative';
            container.style.display = 'inline-block';

            // Create a wrapper for the text to hide it easily
            const textSpan = document.createElement('span');
            textSpan.textContent = originalText;
            textSpan.style.display = 'inline-block';
            textSpan.style.transition = 'opacity 0.1s';
            container.innerHTML = '';
            container.appendChild(textSpan);

            let particles = [];
            let isHovering = false;

            container.addEventListener('mouseenter', () => {
                if (isHovering) return;
                isHovering = true;
                textSpan.style.opacity = '0';

                // Create particles
                const rect = container.getBoundingClientRect();
                const particleCount = 40; // Number of shards

                for (let i = 0; i < particleCount; i++) {
                    const p = document.createElement('span');
                    p.classList.add('pixel-shard');

                    // Initial Position (Random within text area)
                    const startX = Math.random() * rect.width;
                    const startY = Math.random() * rect.height;

                    p.style.left = `${startX}px`;
                    p.style.top = `${startY}px`;
                    p.style.backgroundColor = container.classList.contains('text-gradient-gold') ? '#FFD700' : '#ffffff';

                    container.appendChild(p);
                    particles.push(p);

                    // Explode
                    setTimeout(() => {
                        const angle = Math.random() * Math.PI * 2;
                        const velocity = 20 + Math.random() * 40;
                        const tx = Math.cos(angle) * velocity;
                        const ty = Math.sin(angle) * velocity;

                        p.style.transform = `translate(${tx}px, ${ty}px) scale(0)`;
                        p.style.opacity = '0';
                    }, 10);
                }
            });

            container.addEventListener('mouseleave', () => {
                isHovering = false;
                // Fade text back in
                setTimeout(() => {
                    textSpan.style.opacity = '1';
                    // Cleanup particles
                    particles.forEach(p => p.remove());
                    particles = [];
                }, 300);
            });
        });
    };

    // Initialize Text Animations
    window.initTextAnimation = () => {
        const animatedTexts = document.querySelectorAll('.animate-text');
        animatedTexts.forEach(text => {
            const content = text.textContent.trim();
            if (!content || text.querySelector('.char-slide')) return; // Avoid re-splitting

            text.textContent = '';
            text.style.opacity = '1';

            [...content].forEach((char, index) => {
                const span = document.createElement('span');
                span.textContent = char;
                span.className = 'char-slide';
                if (char === ' ') {
                    span.style.width = '0.25em';
                    span.style.display = 'inline-block';
                }
                span.style.animationDelay = `${index * 0.03}s`;
                text.appendChild(span);
            });
        });
    };

    // Initialize Particle System
    window.initParticles = (canvasId) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        let width, height;
        let particles = [];

        function resize() {
            width = canvas.width = canvas.offsetWidth;
            height = canvas.height = canvas.offsetHeight;
        }

        function createParticles() {
            particles = [];
            const count = Math.floor(width * height / 10000); // Density
            for (let i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    size: Math.random() * 2 + 0.5,
                    alpha: Math.random() * 0.5 + 0.1
                });
            }
        }

        function animate() {
            ctx.clearRect(0, 0, width, height);
            ctx.fillStyle = 'white';

            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;

                // Wrap around
                if (p.x < 0) p.x = width;
                if (p.x > width) p.x = 0;
                if (p.y < 0) p.y = height;
                if (p.y > height) p.y = 0;

                ctx.globalAlpha = p.alpha;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();
            });
            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', () => {
            resize();
            createParticles();
        });

        resize();
        createParticles();
        animate();
    };

    // Initialize Text Animations
    window.initTextAnimation();
    window.initPixelEffect();
    // Particles will be init by page script if canvas exists


    document.querySelectorAll('.animate-on-scroll, .animate-text').forEach(el => {
        window.observer.observe(el);
    });
});

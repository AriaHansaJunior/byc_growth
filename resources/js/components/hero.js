/**
 * Hero Section Enhancements:
 * 1. Smooth scrolling for internal anchor links (e.g. #destinations).
 * 2. Auto-playing slideshow for hero group photos (every 5 seconds).
 */
export function initHeroEnhancements() {
    initSmoothScroll();
    initHeroSlideshow();
}

function initSmoothScroll() {
    document.addEventListener('click', (e) => {
        const anchor = e.target.closest('a[href^="#"]');
        if (!anchor) return;

        const targetId = anchor.getAttribute('href');
        if (targetId === '#' || !targetId) return;

        const target = document.querySelector(targetId);
        if (target) {
            e.preventDefault();
            const headerOffset = 90;
            const elementPosition = target.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });

            if (history.pushState) {
                history.pushState(null, null, targetId);
            }
        }
    });
}

function initHeroSlideshow() {
    const container = document.getElementById('hero-photo-slideshow');
    if (!container) return;

    const slides = Array.from(container.querySelectorAll('.hero-slide'));
    const dots = Array.from(container.querySelectorAll('.slide-dot'));
    const totalSlides = slides.length;
    if (totalSlides <= 1) return;

    let currentIndex = 0;
    let timer = null;
    let cleanupTimeout = null;
    const intervalMs = 5000; // 5 seconds per slide

    function goToSlide(targetIndex) {
        // Ensure clean modulo loop so last slide loops back to slide 0 (first slide)
        const nextIndex = ((targetIndex % totalSlides) + totalSlides) % totalSlides;
        if (nextIndex === currentIndex) return;

        const currentSlide = slides[currentIndex];
        const nextSlide = slides[nextIndex];

        if (cleanupTimeout) {
            clearTimeout(cleanupTimeout);
            cleanupTimeout = null;
        }

        // 1. Current slide stays solid underneath as base to prevent white background flicker
        slides.forEach((s) => {
            if (s !== currentSlide && s !== nextSlide) {
                s.classList.remove('active', 'base');
            }
        });
        currentSlide.classList.add('base');
        currentSlide.classList.remove('active');

        // 2. Prepare next slide: start opacity from 0 on top layer
        nextSlide.classList.remove('base');
        nextSlide.classList.remove('active');
        void nextSlide.offsetWidth; // Force DOM reflow

        // 3. Fade in next slide smoothly over the current slide
        nextSlide.classList.add('active');

        // 4. Update dot indicators
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === nextIndex);
        });

        const prevSlide = currentSlide;
        currentIndex = nextIndex;

        // 5. Once transition has completely finished (1200ms), remove base class from previous slide
        cleanupTimeout = setTimeout(() => {
            prevSlide.classList.remove('base');
            cleanupTimeout = null;
        }, 1300);
    }

    function advanceSlide() {
        goToSlide(currentIndex + 1);
    }

    function startTimer() {
        stopTimer();
        timer = setInterval(advanceSlide, intervalMs);
    }

    function stopTimer() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    // Dot indicators click
    dots.forEach((dot) => {
        dot.addEventListener('click', (e) => {
            e.preventDefault();
            const targetIdx = parseInt(dot.getAttribute('data-slide-to'), 10);
            if (!isNaN(targetIdx)) {
                goToSlide(targetIdx);
                startTimer(); // Reset 5s timer after manual navigation
            }
        });
    });

    // Start auto slideshow
    startTimer();

    // Pause on hover, resume on leave
    container.addEventListener('mouseenter', stopTimer);
    container.addEventListener('mouseleave', startTimer);
}

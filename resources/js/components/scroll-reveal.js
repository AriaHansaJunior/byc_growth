/**
 * Universal Scroll & Entrance Animation Component
 * Smoothly reveals header, page content, cards, and footer
 * both upon initial page load and progressively as the user scrolls.
 */
export function initScrollReveal() {
    const revealSelectors = [
        // Navigation & Layout Headers
        '.global-header',
        '.admin-topbar',
        '.back-nav-bar',
        '.page-header',

        // Homepage Hero
        '.home-copy',
        '.hero-photo-wrap',

        // Homepage Scripture & Destinations
        '.scripture-card',
        '.destinations-heading',
        '.destinations-grid .destination-card',

        // About & General Pages
        '.placeholder-card',
        '.feature-grid-3 .feature-box',

        // Activities
        '.activity-card',

        // Members Directory
        '.members-grid .member-card',

        // Game Center Hub
        '.game-center-hero',
        '.game-center-selection .destinations-heading',
        '.game-center-grid .gc-card',
        '.gc-scoreboard',

        // Birthday Wishes & Archives
        '.wishes-controls-card',
        '.wishes-grid .wish-card',

        // Admin Pages
        '.admin-welcome-card',
        '.admin-section-heading',
        '.admin-shell .feature-box',

        // Cash Management & Forms
        '#form-cash-transaction',

        // Global Footer
        '.global-footer',

        // Explicit Targets
        '[data-reveal]',
        '.scroll-reveal-target'
    ];

    const allElements = document.querySelectorAll(revealSelectors.join(', '));
    if (allElements.length === 0) return;

    // Filter out modals or hidden contract elements
    const revealElements = Array.from(allElements).filter((el) => {
        return !el.closest('.modal-backdrop') && 
               !el.closest('#modal-birthday-popup') &&
               !el.classList.contains('destination-card-disabled');
    });

    if (revealElements.length === 0) return;

    // If IntersectionObserver is not supported, reveal immediately
    if (!('IntersectionObserver' in window)) {
        revealElements.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    // Enable CSS reveal styles
    document.documentElement.classList.add('reveal-enabled');

    // Add scroll-reveal class to all targeted elements
    revealElements.forEach((el) => {
        el.classList.add('scroll-reveal');
    });

    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -40px 0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                obs.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Orchestrate entrance animation after paint
    requestAnimationFrame(() => {
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        revealElements.forEach((el) => {
            const rect = el.getBoundingClientRect();
            // Header is animated first with gentle drop
            if (el.classList.contains('global-header') || el.classList.contains('admin-topbar')) {
                setTimeout(() => el.classList.add('is-revealed'), 60);
                return;
            }

            // Check if element is currently in the initial viewport
            if (rect.top < viewportHeight - 20 && rect.bottom > 0) {
                // Progressive delay based on vertical screen position
                const verticalRatio = Math.max(0, rect.top / viewportHeight);
                const entranceDelay = Math.min(Math.round(verticalRatio * 280) + 120, 480);
                setTimeout(() => {
                    el.classList.add('is-revealed');
                }, entranceDelay);
            } else {
                // Element is below the fold: observe for scroll reveal
                observer.observe(el);
            }
        });
    });
}

// Make accessible globally
if (typeof window !== 'undefined') {
    window.initScrollReveal = initScrollReveal;
}

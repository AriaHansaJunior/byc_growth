{{-- Universal Scroll Position Preservation Engine for BYC Growth --}}
<script>
(function() {
    const SCROLL_KEY = 'byc_preserved_scroll_y';
    const PATH_KEY = 'byc_preserved_scroll_path';

    // Disable browser automatic scroll-to-top on page reloads/form submissions
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    let isNavigatingToNewPage = false;

    // Detect navigation via topbar navigation menus or external links
    document.addEventListener('click', function(e) {
        // Any link inside the top navigation bar or brand logo should start at top
        const navLink = e.target.closest('.admin-nav-item, .brand-link, .nav-item, .admin-profile-menu a, .site-header a');
        if (navLink) {
            isNavigatingToNewPage = true;
            sessionStorage.removeItem(SCROLL_KEY);
            sessionStorage.removeItem(PATH_KEY);
            return;
        }

        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

        try {
            const targetUrl = new URL(link.href, window.location.origin);
            if (targetUrl.origin === window.location.origin) {
                if (targetUrl.pathname !== window.location.pathname) {
                    // Navigating to a different page/route: reset scroll to top
                    isNavigatingToNewPage = true;
                    sessionStorage.removeItem(SCROLL_KEY);
                    sessionStorage.removeItem(PATH_KEY);
                } else {
                    // In-page navigation (e.g. filter reset, pagination links, sort links)
                    sessionStorage.setItem(SCROLL_KEY, String(Math.round(window.scrollY)));
                    sessionStorage.setItem(PATH_KEY, window.location.pathname);
                }
            }
        } catch (_) {}
    }, true);

    // Save scroll position immediately on ANY form submission across the entire application
    document.addEventListener('submit', function() {
        sessionStorage.setItem(SCROLL_KEY, String(Math.round(window.scrollY)));
        sessionStorage.setItem(PATH_KEY, window.location.pathname);
    }, true);

    // Also capture position right before page unloads (reload, redirect, form post)
    window.addEventListener('beforeunload', function() {
        if (!isNavigatingToNewPage) {
            sessionStorage.setItem(SCROLL_KEY, String(Math.round(window.scrollY)));
            sessionStorage.setItem(PATH_KEY, window.location.pathname);
        }
    });

    // Smart Scroll Restoration Engine
    let userInterrupted = false;
    window.addEventListener('wheel', function() { userInterrupted = true; }, { passive: true });
    window.addEventListener('touchmove', function() { userInterrupted = true; }, { passive: true });
    window.addEventListener('keydown', function(e) {
        if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Space'].includes(e.code)) {
            userInterrupted = true;
        }
    }, { passive: true });

    function restorePreservedScroll() {
        const savedPath = sessionStorage.getItem(PATH_KEY);
        const savedScroll = sessionStorage.getItem(SCROLL_KEY);

        if (savedPath === window.location.pathname && savedScroll !== null) {
            const targetY = parseInt(savedScroll, 10);
            if (!isNaN(targetY) && targetY >= 0) {
                function applyY() {
                    if (userInterrupted) return;
                    if (Math.abs(window.scrollY - targetY) > 2) {
                        window.scrollTo(0, targetY);
                    }
                }

                // Immediate execution
                applyY();

                // Multi-frame stages to ensure async components, tables, and images don't jump
                requestAnimationFrame(applyY);
                setTimeout(applyY, 40);
                setTimeout(applyY, 120);
                setTimeout(applyY, 250);
                setTimeout(function() {
                    applyY();
                    sessionStorage.removeItem(SCROLL_KEY);
                    sessionStorage.removeItem(PATH_KEY);
                }, 450);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restorePreservedScroll);
    } else {
        restorePreservedScroll();
    }
    window.addEventListener('load', restorePreservedScroll);
})();
</script>

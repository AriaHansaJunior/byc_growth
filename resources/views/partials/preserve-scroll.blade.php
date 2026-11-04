{{-- Universal Scroll Position Preservation Engine for BYC Growth --}}
<script>
(function() {
    const SCROLL_KEY = 'byc_preserved_scroll_y';
    const PATH_KEY = 'byc_preserved_scroll_path';

    // Detect navigation via links
    document.addEventListener('click', function(e) {
        const navLink = e.target.closest('.admin-nav-item, .brand-link, .header-brand-link, .nav-item, .mobile-nav-link, .footer-link, .admin-profile-menu a, .global-header a');
        if (navLink) {
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
                    sessionStorage.removeItem(SCROLL_KEY);
                    sessionStorage.removeItem(PATH_KEY);
                } else if (window.scrollY > 10) {
                    // Same-page filter/sort/pagination link
                    sessionStorage.setItem(SCROLL_KEY, String(Math.round(window.scrollY)));
                    sessionStorage.setItem(PATH_KEY, window.location.pathname);
                }
            }
        } catch (_) {}
    }, true);

    // Save scroll position on form submission (e.g., filter forms, modal actions)
    document.addEventListener('submit', function() {
        if (window.scrollY > 10) {
            sessionStorage.setItem(SCROLL_KEY, String(Math.round(window.scrollY)));
            sessionStorage.setItem(PATH_KEY, window.location.pathname);
        } else {
            sessionStorage.removeItem(SCROLL_KEY);
            sessionStorage.removeItem(PATH_KEY);
        }
    }, true);

    // Immediately yield to any user touch/wheel/key interaction
    let userInterrupted = false;
    const markInterrupted = function() { userInterrupted = true; };
    window.addEventListener('touchstart', markInterrupted, { passive: true });
    window.addEventListener('touchmove', markInterrupted, { passive: true });
    window.addEventListener('pointerdown', markInterrupted, { passive: true });
    window.addEventListener('wheel', markInterrupted, { passive: true });
    window.addEventListener('keydown', function(e) {
        if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Space'].includes(e.code)) {
            userInterrupted = true;
        }
    }, { passive: true });

    let restoredOnce = false;
    function restorePreservedScroll() {
        if (restoredOnce) return;
        restoredOnce = true;

        const savedPath = sessionStorage.getItem(PATH_KEY);
        const savedScroll = sessionStorage.getItem(SCROLL_KEY);
        sessionStorage.removeItem(SCROLL_KEY);
        sessionStorage.removeItem(PATH_KEY);

        if (savedPath === window.location.pathname && savedScroll !== null) {
            const targetY = parseInt(savedScroll, 10);
            if (!isNaN(targetY) && targetY > 10) {
                function applyY() {
                    if (userInterrupted) return;
                    if (Math.abs(window.scrollY - targetY) > 2) {
                        try {
                            window.scrollTo({ top: targetY, left: 0, behavior: 'instant' });
                        } catch (_) {
                            window.scrollTo(0, targetY);
                        }
                    }
                }

                applyY();
                requestAnimationFrame(applyY);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restorePreservedScroll, { once: true });
    } else {
        restorePreservedScroll();
    }
})();
</script>

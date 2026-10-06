/**
 * BYC Growth Mobile Navigation Drawer Controller
 */
export function initMobileNav() {
    const toggleBtn = document.getElementById('btn-mobile-nav');
    const closeBtn = document.getElementById('btn-mobile-nav-close');
    const backdrop = document.getElementById('mobile-nav-backdrop');
    const drawer = document.getElementById('mobile-nav-drawer');

    if (!toggleBtn || !drawer || !backdrop) return;

    function openNav() {
        drawer.classList.add('is-open');
        backdrop.classList.add('is-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mobile-nav-open');
    }

    function closeNav() {
        drawer.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mobile-nav-open');
    }

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (drawer.classList.contains('is-open')) {
            closeNav();
        } else {
            openNav();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeNav);
    }

    backdrop.addEventListener('click', closeNav);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
            closeNav();
            toggleBtn.focus();
        }
    });

    // Close mobile nav when clicking any internal nav link
    const navLinks = drawer.querySelectorAll('.mobile-nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeNav();
        });
    });
}

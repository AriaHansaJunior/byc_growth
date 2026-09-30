/**
 * BYC Growth S0 Global User Identity & Dropdown Interactions
 */
export function initUserIdentity() {
    const dropdownWrap = document.getElementById('nav-user-dropdown-wrap');
    const userBtn = document.getElementById('btn-user-dropdown');
    const dropdownMenu = document.getElementById('nav-user-dropdown-menu');

    if (userBtn && dropdownMenu) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdownMenu.style.display === 'block';
            dropdownMenu.style.display = isOpen ? 'none' : 'block';
            userBtn.setAttribute('aria-expanded', String(!isOpen));
        });

        document.addEventListener('click', (e) => {
            if (dropdownWrap && !dropdownWrap.contains(e.target)) {
                dropdownMenu.style.display = 'none';
                userBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && dropdownMenu.style.display === 'block') {
                dropdownMenu.style.display = 'none';
                userBtn.setAttribute('aria-expanded', 'false');
                userBtn.focus();
            }
        });
    }

    // Auto-dismiss welcome toast after 4.5 seconds
    const welcomeToast = document.getElementById('byc-welcome-toast');
    if (welcomeToast) {
        setTimeout(() => {
            if (welcomeToast && welcomeToast.parentNode) {
                welcomeToast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                welcomeToast.style.opacity = '0';
                welcomeToast.style.transform = 'translateX(30px)';
                setTimeout(() => {
                    if (welcomeToast.parentNode) {
                        welcomeToast.parentNode.removeChild(welcomeToast);
                    }
                }, 400);
            }
        }, 4500);
    }
}

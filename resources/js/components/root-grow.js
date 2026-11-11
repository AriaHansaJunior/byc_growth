/**
 * BYC GROWTH — Interactive Root & Sprout Animations
 * Decorates buttons, navigation pills, and interactive cards (Scoreboard, Activities, Games, Members)
 * with organic living roots and sprout buds that respond smoothly to hover and active states.
 */

export function initRootGrow() {
    // 1. ALL Buttons & Navigation Pills
    const buttonSelectors = [
        '.button',
        '.btn',
        '.nav-item',
        '.admin-nav-item',
        '.mobile-nav-link',
        '.nav-login-btn',
        '.final-return-btn',
        '[data-roots]'
    ];

    // 2. Interactive Cards, Scoreboard & Activity Containers
    const cardSelectors = [
        '.gc-scoreboard',
        '.gc-card',
        '.activity-card',
        '.destination-card',
        '.members-grid .member-card',
        '.member-card',
        '.wishes-grid .wish-card',
        '.wish-card',
        '.feature-box',
        '.final-team',
        '.podium-card',
        '.about-philosophy-card',
        '.about-hero-showcase'
    ];

    function attachButtonTendrils(btn) {
        if (btn.querySelector('.btn-root-sprout-wrap')) return;

        // Skip tiny icon-only buttons or close buttons
        if (btn.classList.contains('icon-button') ||
            btn.classList.contains('btn-close-modal') ||
            btn.classList.contains('mobile-nav-toggle') ||
            btn.classList.contains('toast-close') ||
            btn.dataset.noRoots === 'true') {
            return;
        }

        const wrap = document.createElement('span');
        wrap.className = 'btn-root-sprout-wrap';
        wrap.setAttribute('aria-hidden', 'true');
        // Snug vector paths that curve tightly along the pill borders without bleeding into neighbors
        wrap.innerHTML = `
            <svg class="btn-root-anchor btn-root-bl" viewBox="0 0 40 28" fill="none">
                <path class="root-tendril-main" d="M 32,20 C 22,21 14,19 8,14 C 4,10 5,5 3,1" />
                <path class="root-tendril-sub" d="M 16,20 C 12,23 7,24 2,24" />
                <circle class="root-leaf-bud" cx="3" cy="1" r="2" />
                <circle class="root-leaf-bud" cx="2" cy="24" r="1.6" />
            </svg>
            <svg class="btn-root-anchor btn-root-tr" viewBox="0 0 40 28" fill="none">
                <path class="root-tendril-main" d="M 8,8 C 18,7 26,9 32,14 C 36,18 35,23 37,27" />
                <path class="root-tendril-sub" d="M 24,8 C 28,4 33,3 37,2" />
                <circle class="root-leaf-bud" cx="37" cy="2" r="2" />
                <circle class="root-leaf-bud" cx="37" cy="27" r="1.6" />
            </svg>
        `;

        btn.appendChild(wrap);
    }

    function attachCardTendrils(card) {
        if (card.querySelector('.card-root-sprout-wrap')) return;
        if (card.dataset.noRoots === 'true') return;

        const wrap = document.createElement('span');
        wrap.className = 'card-root-sprout-wrap';
        wrap.setAttribute('aria-hidden', 'true');
        // Elegant corner vine that unfurls on card hover
        wrap.innerHTML = `
            <svg class="card-root-anchor" viewBox="0 0 68 56" fill="none">
                <path class="card-root-main" d="M 6,10 C 26,8 46,14 54,26 C 60,38 56,48 60,54" />
                <path class="card-root-branch" d="M 32,9 C 40,3 50,2 56,1" />
                <path class="card-root-sub" d="M 54,26 C 60,28 64,32 65,31" />
                <circle class="card-root-bud" cx="56" cy="1" r="2.6" />
                <circle class="card-root-bud" cx="60" cy="54" r="2.2" />
                <circle class="card-root-bud" cx="65" cy="31" r="1.8" />
            </svg>
        `;

        card.appendChild(wrap);
    }

    // Attach to existing DOM elements
    document.querySelectorAll(buttonSelectors.join(', ')).forEach(attachButtonTendrils);
    document.querySelectorAll(cardSelectors.join(', ')).forEach(attachCardTendrils);

    // MutationObserver for dynamically loaded content (modals, pagination, games)
    if ('MutationObserver' in window && !window._bycRootGrowObserver) {
        window._bycRootGrowObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === 1) { // ELEMENT_NODE
                        if (buttonSelectors.some((s) => node.matches && node.matches(s))) {
                            attachButtonTendrils(node);
                        }
                        if (cardSelectors.some((s) => node.matches && node.matches(s))) {
                            attachCardTendrils(node);
                        }
                        if (node.querySelectorAll) {
                            node.querySelectorAll(buttonSelectors.join(', ')).forEach(attachButtonTendrils);
                            node.querySelectorAll(cardSelectors.join(', ')).forEach(attachCardTendrils);
                        }
                    }
                }
            }
        });

        window._bycRootGrowObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// Make accessible globally
if (typeof window !== 'undefined') {
    window.initRootGrow = initRootGrow;
}

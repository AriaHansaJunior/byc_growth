/**
 * BYC GROWTH — Mobile Login Keyboard Positioning (Member & Admin)
 * Keeps the login card centered symmetrically on initial open,
 * then smoothly raises the card upward when the user taps email/password
 * on mobile so the virtual keyboard (40-50% viewport) does not obscure fields or CTA.
 */
export function initAuthKeyboardShift() {
    const shell = document.querySelector('.auth-shell');
    const card = shell ? shell.querySelector('.auth-card') : null;
    if (!shell || !card) return;

    const form = card.querySelector('.auth-form');
    const inputs = Array.from(card.querySelectorAll('input[type="email"], input[type="password"], input[type="text"]'));
    if (!form || inputs.length === 0) return;

    const mobileMq = window.matchMedia('(max-width: 767.98px)');
    const coarseMq = window.matchMedia('(pointer: coarse)');

    function isMobileContext() {
        return mobileMq.matches || (coarseMq.matches && window.innerHeight <= 900);
    }

    // Track full baseline viewport dimensions to accurately detect virtual keyboard open/close
    let baselineHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
    let baselineWidth = window.innerWidth;
    let isRaised = false;
    let collapseTimer = null;
    let holdPointerUntil = 0;
    let userGestureDetected = false;
    let activeAnim = null;

    // On mobile initial load, clear browser `autofocus` until user explicitly taps a field
    // so the card always opens symmetrically centered without an unintended focus ring.
    if (isMobileContext() && document.activeElement && inputs.includes(document.activeElement)) {
        document.activeElement.blur();
    }

    function updateVvVar() {
        const vv = window.visualViewport;
        const h = vv ? vv.height : window.innerHeight;
        document.documentElement.style.setProperty('--auth-vv-height', `${Math.round(h)}px`);
        return h;
    }

    function isInputFocused() {
        const active = document.activeElement;
        return Boolean(active && inputs.includes(active));
    }

    function animateFlip(nextState) {
        if (isRaised === nextState) return;
        clearTimeout(collapseTimer);

        if (activeAnim) {
            clearTimeout(activeAnim);
            card.style.transition = '';
            card.style.transform = '';
            activeAnim = null;
        }

        const firstTop = card.getBoundingClientRect().top;

        isRaised = nextState;
        shell.classList.toggle('is-keyboard-open', nextState);
        document.body.classList.toggle('is-auth-keyboard-open', nextState);

        if (nextState) {
            updateVvVar();
        } else {
            document.documentElement.style.removeProperty('--auth-vv-height');
        }

        const lastTop = card.getBoundingClientRect().top;
        const deltaY = firstTop - lastTop;

        if (Math.abs(deltaY) > 2) {
            card.style.transition = 'none';
            card.style.transform = `translate3d(0, ${deltaY}px, 0)`;
            void card.offsetHeight;
            card.style.transition = 'transform 0.28s cubic-bezier(0.22, 1, 0.36, 1)';
            card.style.transform = 'translate3d(0, 0, 0)';

            activeAnim = setTimeout(() => {
                card.style.transition = '';
                card.style.transform = '';
                activeAnim = null;
            }, 300);
        }

        if (nextState) {
            ensureVisibleAboveKeyboard();
        }
    }

    function ensureVisibleAboveKeyboard() {
        const vv = window.visualViewport;

        const syncScroll = () => {
            if (!isRaised) return;
            const currentAvail = vv ? vv.height : window.innerHeight;
            const cardRect = card.getBoundingClientRect();

            // If entire card fits inside the visible height above keyboard, keep top aligned cleanly
            if (cardRect.height + 20 <= currentAvail) {
                if (window.scrollY > 0 || (vv && vv.offsetTop > 0)) {
                    window.scrollTo({ top: 0, behavior: 'auto' });
                }
            } else {
                // On extra-short viewports or when validation alerts increase card height,
                // keep the focused input visible comfortably above the keyboard.
                const active = document.activeElement;
                if (active && inputs.includes(active)) {
                    const inputRect = active.getBoundingClientRect();
                    if (inputRect.bottom > currentAvail - 12 || inputRect.top < 8) {
                        active.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                }
            }
        };

        requestAnimationFrame(syncScroll);
        setTimeout(syncScroll, 220);
        setTimeout(syncScroll, 380);
    }

    function scheduleCollapse(delay = 160) {
        clearTimeout(collapseTimer);
        collapseTimer = setTimeout(() => {
            if (Date.now() < holdPointerUntil) {
                scheduleCollapse(180);
                return;
            }

            const vv = window.visualViewport;
            const currentH = vv ? vv.height : window.innerHeight;
            const keyboardStillOpen = currentH < baselineHeight * 0.80;

            if (!isInputFocused() && !keyboardStillOpen) {
                animateFlip(false);
            }
        }, delay);
    }

    // Protect taps on interactive controls inside card (Sign In, Eye Toggle, Remember Me)
    // so the card never shifts downward under the user's finger before click fires.
    card.addEventListener('pointerdown', (e) => {
        userGestureDetected = true;
        if (!isMobileContext()) return;

        const interactive = e.target.closest('button, label, input[type="checkbox"], a');
        if (interactive) {
            holdPointerUntil = Date.now() + 400;
        }

        const tappedInput = e.target.closest('input[type="email"], input[type="password"], input[type="text"]');
        if (tappedInput) {
            clearTimeout(collapseTimer);
            animateFlip(true);
        }
    }, { passive: true });

    inputs.forEach((input) => {
        input.addEventListener('focus', () => {
            if (!isMobileContext()) return;
            const vv = window.visualViewport;
            const currentH = vv ? vv.height : window.innerHeight;
            const keyboardShrunk = currentH < baselineHeight * 0.82;
            if (!userGestureDetected && !keyboardShrunk) return;

            clearTimeout(collapseTimer);
            animateFlip(true);
        });

        input.addEventListener('blur', (e) => {
            if (!isMobileContext()) return;
            const nextTarget = e.relatedTarget;
            if (nextTarget && card.contains(nextTarget)) {
                if (inputs.includes(nextTarget)) return;
                holdPointerUntil = Date.now() + 300;
            }
            scheduleCollapse(180);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Tab' || e.key === 'Enter') {
            userGestureDetected = true;
        }
    });

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', () => {
            const currentH = window.visualViewport.height;

            if (currentH > baselineHeight || Math.abs(window.innerWidth - baselineWidth) > 80) {
                baselineHeight = currentH;
                baselineWidth = window.innerWidth;
            }

            if (!isMobileContext()) {
                if (isRaised) animateFlip(false);
                return;
            }

            const keyboardShrunk = currentH < baselineHeight * 0.80;
            if (keyboardShrunk) {
                clearTimeout(collapseTimer);
                if (!isRaised) {
                    animateFlip(true);
                } else {
                    updateVvVar();
                    ensureVisibleAboveKeyboard();
                }
            } else if (isRaised && currentH >= baselineHeight * 0.88) {
                // Soft keyboard dismissed (e.g., Android system back/hide chevron)
                if (document.activeElement && inputs.includes(document.activeElement)) {
                    document.activeElement.blur();
                }
                scheduleCollapse(80);
            }
        });
    }

    window.addEventListener('orientationchange', () => {
        setTimeout(() => {
            baselineHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
            baselineWidth = window.innerWidth;
            if (!isMobileContext() && isRaised) {
                animateFlip(false);
            } else if (isRaised) {
                updateVvVar();
            }
        }, 250);
    });
}

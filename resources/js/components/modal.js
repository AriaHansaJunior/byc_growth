/**
 * Modal helper for BYC Growth dialogs
 */
export function initModal(openBtnId, modalId, closeBtnId) {
    const openBtn = document.getElementById(openBtnId);
    const modal = document.getElementById(modalId);
    const closeBtn = document.getElementById(closeBtnId);

    if (!modal) return;

    const open = () => {
        modal.style.display = 'grid';
        document.body.style.overflow = 'hidden';
    };

    const close = () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    if (openBtn) {
        openBtn.addEventListener('click', open);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', close);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            close();
        }
    });

    return { open, close };
}

export function initHowToPlayTabs() {
    const tabBtns = document.querySelectorAll('.how-tab-btn');
    const paneGuess = document.getElementById('tab-pane-guess');
    const paneGrowth = document.getElementById('tab-pane-growth');

    if (!tabBtns.length || !paneGuess || !paneGrowth) return;

    tabBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            tabBtns.forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');

            if (target === 'guess') {
                paneGuess.style.display = 'block';
                paneGrowth.style.display = 'none';
            } else if (target === 'growth') {
                paneGuess.style.display = 'none';
                paneGrowth.style.display = 'block';
            }
        });
    });
}


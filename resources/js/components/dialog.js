/**
 * Earth-tone Custom Alert / Dialog for BYC Growth
 */
export function showGameAlert({
    title = 'Admin Account Required',
    message = 'You must login as an admin account to input score.',
    eyebrow = 'BYC Growth Notice',
    icon = '🔒',
    buttonText = 'OK',
} = {}) {
    const modal = document.getElementById('byc-alert-modal');
    if (!modal) {
        window.alert(message);
        return;
    }

    const titleEl = document.getElementById('byc-alert-title');
    const messageEl = document.getElementById('byc-alert-message');
    const eyebrowEl = document.getElementById('byc-alert-eyebrow');
    const iconSymbol = document.getElementById('byc-alert-icon-symbol');
    const okBtn = modal.querySelector('.byc-alert-btn-ok');
    const closeBtn = modal.querySelector('.byc-alert-btn-close');

    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.textContent = message;
    if (eyebrowEl) eyebrowEl.textContent = eyebrow;
    if (iconSymbol) iconSymbol.textContent = icon;
    if (okBtn) okBtn.textContent = buttonText;

    modal.style.display = 'grid';
    document.body.style.overflow = 'hidden';

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleKey);
    }

    function handleKey(e) {
        if (e.key === 'Escape') closeModal();
    }

    if (okBtn) {
        okBtn.onclick = closeModal;
        okBtn.focus();
    }

    if (closeBtn) {
        closeBtn.onclick = closeModal;
    }

    modal.onclick = (e) => {
        if (e.target === modal) closeModal();
    };

    document.addEventListener('keydown', handleKey);
}

// Make globally accessible
if (typeof window !== 'undefined') {
    window.showGameAlert = showGameAlert;
}

/**
 * Admin Shell & Confirmation Foundation
 * BYC GROWTH 2.0 — Scope S1
 */
export function initAdminShell() {
    const modal = document.getElementById('admin-confirm-modal');
    if (!modal) return;

    const titleEl = document.getElementById('admin-confirm-title');
    const msgEl = document.getElementById('admin-confirm-message');
    const formEl = document.getElementById('admin-confirm-form');
    const methodEl = document.getElementById('admin-confirm-method');
    const submitBtn = document.getElementById('admin-confirm-submit-btn');
    const closeBtns = modal.querySelectorAll('.admin-confirm-close-btn, .admin-confirm-cancel-btn');

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    closeBtns.forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal();
        }
    });

    window.openAdminConfirm = function({
        title = 'Are you sure?',
        message = 'This action cannot be undone. Are you sure you want to proceed?',
        actionUrl = '',
        method = 'DELETE',
        confirmText = 'Confirm Delete',
        buttonClass = 'button-danger'
    }) {
        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;
        if (formEl) formEl.action = actionUrl;
        if (methodEl) methodEl.value = method;
        if (submitBtn) {
            submitBtn.textContent = confirmText;
            submitBtn.className = `button ${buttonClass} button-sm`;
        }

        modal.style.display = 'grid';
        document.body.style.overflow = 'hidden';
    };

    // Global listener for elements declaring data-admin-confirm
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-admin-confirm]');
        if (!trigger) return;

        e.preventDefault();
        const actionUrl = trigger.getAttribute('data-action') || (trigger.form ? trigger.form.action : '') || trigger.getAttribute('href') || '';
        const title = trigger.getAttribute('data-confirm-title') || 'Are you sure?';
        const message = trigger.getAttribute('data-admin-confirm') || 'This action cannot be undone.';
        const method = trigger.getAttribute('data-method') || 'DELETE';
        const confirmText = trigger.getAttribute('data-confirm-text') || 'Confirm Delete';

        window.openAdminConfirm({
            title,
            message,
            actionUrl,
            method,
            confirmText
        });
    });
}

/**
 * Admin Shell & Confirmation Foundation
 * BYC GROWTH 2.0 — Scope S1
 */
export function initAdminProfileDropdown() {
    const wrap = document.getElementById('admin-profile-dropdown-wrap');
    const btn = document.getElementById('btn-admin-profile-dropdown');
    if (!btn || !wrap) return;

    btn.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = wrap.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', String(isOpen));
    };

    if (!window.__adminDocClickBound) {
        window.__adminDocClickBound = true;
        document.addEventListener('click', (e) => {
            const currentWrap = document.getElementById('admin-profile-dropdown-wrap');
            const currentBtn = document.getElementById('btn-admin-profile-dropdown');
            if (currentWrap && !currentWrap.contains(e.target)) {
                currentWrap.classList.remove('is-open');
                if (currentBtn) currentBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const currentWrap = document.getElementById('admin-profile-dropdown-wrap');
                const currentBtn = document.getElementById('btn-admin-profile-dropdown');
                if (currentWrap && currentWrap.classList.contains('is-open')) {
                    currentWrap.classList.remove('is-open');
                    if (currentBtn) {
                        currentBtn.setAttribute('aria-expanded', 'false');
                        currentBtn.focus();
                    }
                }
            }
        });
    }
}

export function initAdminToasts() {
    const container = document.getElementById('admin-toast-container');
    if (!container) return;

    const toasts = container.querySelectorAll('.admin-toast-item:not([data-toast-initialized])');
    toasts.forEach(toast => {
        toast.setAttribute('data-toast-initialized', 'true');

        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => dismissToast(toast));
        }

        setTimeout(() => {
            dismissToast(toast);
        }, 5000);
    });
}

function dismissToast(toast) {
    if (!toast || toast.dataset.dismissing === 'true') return;
    toast.dataset.dismissing = 'true';
    toast.classList.add('toast-dismissed');
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 420);
}

window.showAdminToast = function(message, type = 'success') {
    const container = document.getElementById('admin-toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `admin-toast-item toast-${type} alert-box-${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.innerHTML = `
        <div class="toast-icon">${type === 'error' ? '⚠️' : '✓'}</div>
        <div class="toast-content">${message}</div>
        <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
    `;

    container.appendChild(toast);
    initAdminToasts();
};

export function initAdminShell() {
    initAdminProfileDropdown();
    initAdminToasts();

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

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal();
        }
    });

    let activeOnConfirm = null;

    if (formEl) {
        formEl.addEventListener('submit', (e) => {
            if (typeof activeOnConfirm === 'function') {
                e.preventDefault();
                const callback = activeOnConfirm;
                activeOnConfirm = null;
                closeModal();
                callback();
            }
        });
    }

    window.openAdminConfirm = function({
        title = 'Are you sure?',
        message = 'This action cannot be undone. Are you sure you want to proceed?',
        actionUrl = '',
        method = 'DELETE',
        confirmText = 'Confirm Delete',
        buttonClass = 'button-danger',
        onConfirm = null
    }) {
        activeOnConfirm = onConfirm;
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

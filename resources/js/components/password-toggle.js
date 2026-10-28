/**
 * Password Visibility Toggle Component
 * Global event-delegated handler for all password fields across regular and admin portals.
 */
export function initPasswordToggle() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-toggle-password');
        if (!btn) return;

        e.preventDefault();

        const targetId = btn.dataset.target;
        let input = null;

        if (targetId) {
            input = document.getElementById(targetId) || document.querySelector(`input[name="${targetId}"]`);
        }

        if (!input) {
            const wrapper = btn.closest('.password-field-wrapper') || btn.closest('.form-group') || btn.parentElement;
            if (wrapper) {
                input = wrapper.querySelector('input');
            }
        }

        if (!input) return;

        // Special handling for Profile Current Password non-editable masked indicator
        if (input.id === 'profile-current-password') {
            const isCurrentlyPassword = input.type === 'password';
            input.type = isCurrentlyPassword ? 'text' : 'password';
            input.value = isCurrentlyPassword ? 'Current password is set' : '••••••••••••';

            const newTitle = isCurrentlyPassword ? 'Hide password' : 'Show password';
            btn.setAttribute('aria-label', newTitle);
            btn.title = newTitle;

            const eyeIcon = btn.querySelector('.icon-eye');
            const eyeOffIcon = btn.querySelector('.icon-eye-off');
            if (eyeIcon && eyeOffIcon) {
                eyeIcon.style.display = isCurrentlyPassword ? 'none' : 'inline-flex';
                eyeOffIcon.style.display = isCurrentlyPassword ? 'inline-flex' : 'none';
            }
            return;
        }

        // Standard password input toggling
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const newTitle = isPassword ? 'Hide password' : 'Show password';
        btn.setAttribute('aria-label', newTitle);
        btn.title = newTitle;

        const eyeIcon = btn.querySelector('.icon-eye');
        const eyeOffIcon = btn.querySelector('.icon-eye-off');
        if (eyeIcon && eyeOffIcon) {
            eyeIcon.style.display = isPassword ? 'none' : 'inline-flex';
            eyeOffIcon.style.display = isPassword ? 'inline-flex' : 'none';
        }
    });
}

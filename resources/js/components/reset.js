import { postJson } from './api';
import { showGameAlert } from './dialog';

export function initResetGame() {
    const btnReset = document.getElementById('btn-reset-game');
    const modalReset = document.getElementById('modal-reset-game');
    const btnCancel = document.getElementById('btn-cancel-reset');
    const btnConfirm = document.getElementById('btn-confirm-reset');

    if (!btnReset || !modalReset) return;

    btnReset.addEventListener('click', () => {
        modalReset.style.display = 'grid';
        document.body.style.overflow = 'hidden';
    });

    const closeModal = () => {
        modalReset.style.display = 'none';
        document.body.style.overflow = '';
    };

    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    modalReset.addEventListener('click', (e) => {
        if (e.target === modalReset) closeModal();
    });

    if (btnConfirm) {
        btnConfirm.addEventListener('click', async () => {
            try {
                btnConfirm.disabled = true;
                btnConfirm.textContent = 'Mereset...';

                const res = await postJson('/game/reset', {});
                if (res.success) {
                    window.location.reload();
                }
            } catch (err) {
                showGameAlert({
                    title: 'Reset Game',
                    message: 'Gagal mereset permainan: ' + err.message,
                    icon: '⚠️',
                });
                btnConfirm.disabled = false;
                btnConfirm.textContent = 'Ya, Reset Game';
            }
        });
    }
}

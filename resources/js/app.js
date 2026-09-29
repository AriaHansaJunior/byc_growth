import './bootstrap';
import { initModal, initHowToPlayTabs } from './components/modal';
import { initGuessMe } from './components/guess-me';
import { initGrowth100 } from './components/growth-100';
import { initResetGame } from './components/reset';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Cara Bermain modal & tabs
    initModal('btn-how-to-play', 'modal-how-to-play', 'btn-close-how-to-play');
    initHowToPlayTabs();

    const startBtn = document.getElementById('btn-start-playing');
    if (startBtn) {
        startBtn.addEventListener('click', () => {
            const modal = document.getElementById('modal-how-to-play');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }

    // Initialize interactive games and reset
    initGuessMe();
    initGrowth100();
    initResetGame();
});

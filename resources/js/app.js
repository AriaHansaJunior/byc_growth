import './bootstrap';
import './components/dialog';
import { initModal, initHowToPlayTabs } from './components/modal';
import { initGuessMe } from './components/guess-me';
import { initGrowth100 } from './components/growth-100';
import { initResetGame } from './components/reset';
import { initHeroEnhancements } from './components/hero';
import { initScrollReveal } from './components/scroll-reveal';
import { initUserIdentity } from './components/user-identity';
import { initAdminShell } from './components/admin-shell';

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

    // Initialize hero smooth scroll & slideshow
    initHeroEnhancements();

    // Initialize smooth scroll reveal animations
    initScrollReveal();

    // Initialize global user identity dropdown and welcome toast
    initUserIdentity();

    // Initialize Admin Shell confirmation modal & controls
    initAdminShell();
});

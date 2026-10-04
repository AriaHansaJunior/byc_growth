import { postJson, deleteJson } from './api';
import { showGameAlert } from './dialog';
import {
    setupRoundPointAssignment,
    updateTeamCardsVisualState,
    initTeamConfigModal,
} from './universal-game';

export function initGrowth100() {
    if (!window.BYC_GAME2) return;

    let { rounds, state, teams, currentIndex } = window.BYC_GAME2;
    let currentRound = rounds[currentIndex] || rounds[0] || null;
    let roundId = currentRound ? String(currentRound.id) : '1';

    // Elements
    const roundIndicator = document.getElementById('round-indicator');
    const surveyQuestionText = document.getElementById('survey-question-text');
    const answersGridContainer = document.getElementById('answers-grid-container');
    const roundRevealedPointsEl = document.getElementById('round-revealed-points');
    const roundProgressPercent = document.getElementById('round-progress-percent');
    const roundProgressBar = document.getElementById('round-progress-bar');
    const btnPrevRound = document.getElementById('btn-prev-round');
    const btnNextRound = document.getElementById('btn-next-round');
    const roundDotsContainer = document.getElementById('round-dots-container');

    const btnRevealAll = document.getElementById('btn-reveal-all');
    const btnHideAll = document.getElementById('btn-hide-all');
    const btnResetCrosses = document.getElementById('btn-reset-crosses');
    const crossOverlay = document.getElementById('cross-overlay');
    const crossOverlayContent = document.getElementById('cross-overlay-content');
    const growthTeamsContainer = document.getElementById('growth-teams-award-container');

    let crossOverlayTimeout = null;

    // Initialize Universal Team Configuration Modal for Game 2
    initTeamConfigModal(teams || [], 'game2');

    // Initialize Universal Round Point Assignment (Exclusive Radio Selection & Transfer)
    setupRoundPointAssignment({
        gameCode: 'game2',
        getCurrentRound: () => currentRound,
        onStateChange: (res) => {
            if (currentRound) {
                currentRound.awarded_team_id = res.awarded_team_id;
                currentRound.awarded_points = res.awarded_points;
            }
        },
    });

    function getRevealedList() {
        if (!state.revealed) state.revealed = {};
        if (!state.revealed[roundId]) state.revealed[roundId] = [];
        return state.revealed[roundId];
    }

    function getCrosses() {
        if (!state.crosses) state.crosses = {};
        return state.crosses[roundId] || 0;
    }

    function calculateRoundTotal() {
        if (!currentRound || !currentRound.answers) return 0;
        const revealed = getRevealedList();
        return currentRound.answers
            .filter((_, idx) => revealed.includes(idx))
            .reduce((sum, ans) => sum + (parseInt(ans.score, 10) || 0), 0);
    }

    function updateRoundUI() {
        if (!rounds.length) return;
        currentRound = rounds[currentIndex];
        roundId = String(currentRound.id);
        const revealed = getRevealedList();
        const crosses = getCrosses();
        const total = calculateRoundTotal();

        // Round Counter
        if (roundIndicator) {
            roundIndicator.innerHTML = `${String(currentIndex + 1).padStart(2, '0')} <em>/ ${String(rounds.length).padStart(2, '0')}</em>`;
        }

        // Question
        if (surveyQuestionText) {
            surveyQuestionText.textContent = currentRound.question;
        }

        // Total Points and Progress (0/100)
        if (roundRevealedPointsEl) {
            roundRevealedPointsEl.textContent = total;
        }

        if (roundProgressPercent) {
            roundProgressPercent.textContent = `${total}%`;
        }

        if (roundProgressBar) {
            const clamped = Math.min(100, Math.max(0, total));
            roundProgressBar.style.width = `${clamped}%`;
            roundProgressBar.setAttribute('aria-valuenow', clamped);
            if (clamped === 100) {
                roundProgressBar.classList.add('is-complete');
            } else {
                roundProgressBar.classList.remove('is-complete');
            }
        }

        document.querySelectorAll('.current-total-label').forEach((el) => {
            el.textContent = total;
        });

        // Update Host Team Cards Visual Selection State
        if (growthTeamsContainer) {
            updateTeamCardsVisualState(
                growthTeamsContainer,
                currentRound.awarded_team_id ?? null,
                total
            );
        }

        // Render Answers Grid
        if (answersGridContainer && currentRound.answers) {
            answersGridContainer.innerHTML = '';
            currentRound.answers.forEach((ans, idx) => {
                const isOpen = revealed.includes(idx);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `answer-tile ${isOpen ? 'open' : ''}`;
                btn.dataset.answerIndex = idx;
                btn.innerHTML = `
                    <strong>${idx + 1}</strong>
                    <span class="answer-text-label">${isOpen ? ans.text : 'Click to reveal'}</span>
                    <em>${isOpen ? ans.score : '?'}</em>
                `;
                answersGridContainer.appendChild(btn);
            });
        }

        // Cross / Strikes Buttons
        document.querySelectorAll('.btn-cross').forEach((btn) => {
            const count = parseInt(btn.dataset.cross, 10);
            if (crosses >= count) {
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove('active');
                btn.setAttribute('aria-pressed', 'false');
            }
        });

        // Reset Strikes Button State
        if (btnResetCrosses) {
            btnResetCrosses.disabled = crosses === 0;
        }

        // Navigation buttons
        if (btnPrevRound) btnPrevRound.disabled = currentIndex === 0;
        if (btnNextRound) btnNextRound.disabled = currentIndex >= rounds.length - 1;

        // Round Dots
        if (roundDotsContainer) {
            roundDotsContainer.innerHTML = '';
            rounds.forEach((_, idx) => {
                const dot = document.createElement('span');
                if (idx === currentIndex) dot.className = 'active';
                dot.dataset.index = idx;
                dot.addEventListener('click', () => goToRound(idx));
                roundDotsContainer.appendChild(dot);
            });
        }
    }

    async function toggleAnswer(index) {
        if (!currentRound) return;
        let revealed = getRevealedList();
        if (revealed.includes(index)) {
            revealed = revealed.filter((i) => i !== index);
        } else {
            revealed.push(index);
        }
        state.revealed[roundId] = revealed;

        updateRoundUI();

        try {
            await postJson('/game/growth-100/state', {
                round_index: currentIndex,
                answer_index: index,
            });
        } catch (err) {
            console.error('Failed to update answer reveal:', err);
        }
    }

    async function revealAll(reveal) {
        if (!currentRound) return;
        state.revealed[roundId] = reveal ? currentRound.answers.map((_, i) => i) : [];
        updateRoundUI();

        try {
            await postJson('/game/growth-100/state', {
                round_index: currentIndex,
                reveal_all: reveal,
            });
        } catch (err) {
            console.error('Failed to reveal all answers:', err);
        }
    }

    async function setCrosses(count) {
        const validCount = Math.max(0, Math.min(3, count));
        if (!state.crosses) state.crosses = {};
        state.crosses[roundId] = validCount;

        if (crossOverlayTimeout) {
            clearTimeout(crossOverlayTimeout);
            crossOverlayTimeout = null;
        }

        // Visual overlay animation for wrong answers
        if (crossOverlay && crossOverlayContent) {
            if (validCount > 0) {
                crossOverlayContent.innerHTML = Array.from({ length: validCount }, () => '<b>✕</b>').join('');
                crossOverlay.style.display = 'grid';

                crossOverlayTimeout = setTimeout(() => {
                    crossOverlay.style.display = 'none';
                    crossOverlayTimeout = null;
                }, 1200);
            } else {
                crossOverlay.style.display = 'none';
            }
        }

        updateRoundUI();

        try {
            await postJson('/game/growth-100/state', {
                round_index: currentIndex,
                crosses: validCount,
            });
        } catch (err) {
            console.error('Failed to set crosses:', err);
        }
    }

    async function goToRound(index) {
        if (index < 0 || index >= rounds.length) return;
        currentIndex = index;
        updateRoundUI();

        try {
            await postJson('/game/growth-100/state', {
                round_index: currentIndex,
            });
        } catch (err) {
            console.error('Failed to update active round:', err);
        }
    }

    // Attach listeners
    if (btnRevealAll) btnRevealAll.addEventListener('click', () => revealAll(true));
    if (btnHideAll) btnHideAll.addEventListener('click', () => revealAll(false));

    document.querySelectorAll('.btn-cross').forEach((btn) => {
        btn.addEventListener('click', () => {
            const count = parseInt(btn.dataset.cross, 10);
            const current = getCrosses();
            if (current === count) {
                // Clicking active strike steps down
                setCrosses(count - 1);
            } else {
                setCrosses(count);
            }
        });
    });

    if (btnResetCrosses) {
        btnResetCrosses.addEventListener('click', () => setCrosses(0));
    }

    if (btnPrevRound) {
        btnPrevRound.addEventListener('click', () => goToRound(currentIndex - 1));
    }

    if (btnNextRound) {
        btnNextRound.addEventListener('click', () => goToRound(currentIndex + 1));
    }

    // Answer tile click delegation (immediately active for server-rendered & dynamic tiles)
    if (answersGridContainer) {
        answersGridContainer.addEventListener('click', (e) => {
            const tile = e.target.closest('.answer-tile');
            if (tile && tile.dataset.answerIndex !== undefined) {
                const idx = parseInt(tile.dataset.answerIndex, 10);
                if (!isNaN(idx)) {
                    toggleAnswer(idx);
                }
            }
        });
    }

    // Reload cleanly if page was restored from browser bfcache
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });

    // Reset revealed answers when navigating away or exiting the page
    window.addEventListener('pagehide', () => {
        if (navigator.sendBeacon) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const data = new FormData();
            if (csrfToken) {
                data.append('_token', csrfToken);
            }
            navigator.sendBeacon('/game/growth-100/reset-revealed', data);
        }
    });

    // Synchronize UI on initial page load
    updateRoundUI();

    // ==========================================
    // CRUD Editor Modal Logic with Batch Save
    // ==========================================
    const modalEditor = document.getElementById('modal-growth-editor');
    const btnOpenEditor = document.getElementById('btn-open-editor');
    const btnOpenEditorEmpty = document.getElementById('btn-open-editor-empty');
    const btnCloseEditor = document.getElementById('btn-close-growth-editor');
    const btnAddRound = document.getElementById('btn-add-growth-round');
    const btnDeleteRound = document.getElementById('btn-delete-growth-round');
    const btnSaveRound = document.getElementById('btn-save-growth-round');
    const btnSaveBatch = document.getElementById('btn-save-batch-growth');

    const inputId = document.getElementById('growth-round-id');
    const inputQuestion = document.getElementById('growth-round-question');
    const answersContainer = document.getElementById('growth-answers-container');
    const btnAddAnswerRow = document.getElementById('btn-add-answer-row');
    const scoreTotalIndicator = document.getElementById('growth-score-total-indicator');
    const roundsListEl = document.getElementById('growth-round-buttons');

    let workingRounds = [];
    let editingRoundIndex = 0;

    function openModal() {
        if (!modalEditor) return;
        workingRounds = JSON.parse(JSON.stringify(rounds));
        modalEditor.style.display = 'grid';
        document.body.style.overflow = 'hidden';

        if (workingRounds.length > 0) {
            selectEditorRoundIndex(Math.min(currentIndex, workingRounds.length - 1));
        } else {
            newEditorRound();
        }
    }

    function closeModal() {
        if (!modalEditor) return;
        modalEditor.style.display = 'none';
        document.body.style.overflow = '';
    }

    function flushActiveFormToWorkingRound() {
        if (editingRoundIndex < 0 || editingRoundIndex >= workingRounds.length) return;
        const currentWorking = workingRounds[editingRoundIndex];
        if (!currentWorking) return;

        currentWorking.question = (inputQuestion ? inputQuestion.value.trim() : currentWorking.question);

        if (answersContainer) {
            const rows = answersContainer.querySelectorAll('.answer-edit-row');
            currentWorking.answers = Array.from(rows).map((row) => ({
                text: row.querySelector('.input-ans-text').value.trim(),
                score: parseInt(row.querySelector('.input-ans-score').value, 10) || 0,
            }));
        }
    }

    function renderEditorRoundsList() {
        if (!roundsListEl) return;
        roundsListEl.innerHTML = '';
        workingRounds.forEach((r, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.id = `growth-round-btn-${idx}`;
            btn.className = idx === editingRoundIndex ? 'active is-selected' : '';
            btn.setAttribute('aria-selected', idx === editingRoundIndex ? 'true' : 'false');
            btn.innerHTML = `<strong>Round ${idx + 1}</strong><small>${r.answers ? r.answers.length : 0} answers</small>`;
            btn.addEventListener('click', () => {
                flushActiveFormToWorkingRound();
                selectEditorRoundIndex(idx);
            });
            roundsListEl.appendChild(btn);
        });
    }

    function selectEditorRoundIndex(index) {
        if (index < 0 || index >= workingRounds.length) return;
        editingRoundIndex = index;
        const target = workingRounds[index];

        inputId.value = target.id || '';
        inputQuestion.value = target.question || '';
        renderAnswerRows(target.answers || []);

        if (btnDeleteRound) {
            btnDeleteRound.style.display = workingRounds.length > 1 ? 'inline-block' : 'none';
        }

        recalculateEditorTotal();
        renderEditorRoundsList();
    }

    function newEditorRound() {
        flushActiveFormToWorkingRound();
        const newRound = {
            id: null,
            round_number: workingRounds.length + 1,
            question: '',
            answers: [
                { text: '', score: 35 },
                { text: '', score: 25 },
                { text: '', score: 20 },
                { text: '', score: 20 },
            ],
        };
        workingRounds.push(newRound);
        selectEditorRoundIndex(workingRounds.length - 1);
    }

    function renderAnswerRows(answers) {
        if (!answersContainer) return;
        answersContainer.innerHTML = '';
        answers.forEach((ans) => {
            addAnswerRow(ans.text, ans.score);
        });
        updateRowNumbers();
    }

    function addAnswerRow(text = '', score = 10) {
        const row = document.createElement('div');
        row.className = 'answer-edit-row';
        row.innerHTML = `
            <span class="row-num">1</span>
            <input type="text" class="input-ans-text" placeholder="Answer description" value="${text.replace(/"/g, '&quot;')}" required>
            <input type="number" class="input-ans-score" value="${score}" min="1" max="100" required>
            <button type="button" class="btn-remove-row" aria-label="Remove answer">×</button>
        `;

        row.querySelector('.input-ans-score').addEventListener('input', recalculateEditorTotal);
        row.querySelector('.input-ans-text').addEventListener('input', recalculateEditorTotal);
        row.querySelector('.btn-remove-row').addEventListener('click', () => {
            if (answersContainer.querySelectorAll('.answer-edit-row').length <= 1) {
                showGameAlert({
                    title: 'Validation',
                    message: 'At least 1 answer is required.',
                    icon: 'ℹ️',
                });
                return;
            }
            row.remove();
            updateRowNumbers();
            recalculateEditorTotal();
        });

        answersContainer.appendChild(row);
        updateRowNumbers();
        recalculateEditorTotal();
    }

    function updateRowNumbers() {
        if (!answersContainer) return;
        answersContainer.querySelectorAll('.answer-edit-row').forEach((row, idx) => {
            const numEl = row.querySelector('.row-num');
            if (numEl) numEl.textContent = idx + 1;
        });
    }

    function recalculateEditorTotal() {
        if (!answersContainer || !scoreTotalIndicator) return;
        let sum = 0;
        answersContainer.querySelectorAll('.input-ans-score').forEach((input) => {
            sum += parseInt(input.value, 10) || 0;
        });

        if (sum === 100) {
            scoreTotalIndicator.textContent = 'Total: 100 / 100 (Valid)';
            scoreTotalIndicator.className = 'validation-good';
            scoreTotalIndicator.style.color = 'var(--forest)';
        } else {
            scoreTotalIndicator.textContent = `Total: ${sum} / 100 (Must equal 100)`;
            scoreTotalIndicator.className = '';
            scoreTotalIndicator.style.color = 'var(--red)';
        }
    }

    function validateSingleRound(round, roundNum) {
        const question = (round.question || '').trim();
        if (!question) {
            return { valid: false, error: `Round ${roundNum}: Question cannot be empty.` };
        }

        const answers = round.answers || [];
        if (answers.length === 0) {
            return { valid: false, error: `Round ${roundNum}: At least 1 answer is required.` };
        }

        let total = 0;
        for (let i = 0; i < answers.length; i++) {
            const text = (answers[i].text || '').trim();
            const score = parseInt(answers[i].score, 10) || 0;
            if (!text) {
                return { valid: false, error: `Round ${roundNum}: Answer ${i + 1} text cannot be empty.` };
            }
            if (score < 1) {
                return { valid: false, error: `Round ${roundNum}: Answer ${i + 1} score must be at least 1.` };
            }
            total += score;
        }

        if (total !== 100) {
            return {
                valid: false,
                error: `Round ${roundNum}: Total answers score must equal exactly 100 (current: ${total}).`,
            };
        }

        return { valid: true };
    }

    if (btnAddAnswerRow) {
        btnAddAnswerRow.addEventListener('click', () => addAnswerRow('', 10));
    }

    if (btnOpenEditor) btnOpenEditor.addEventListener('click', openModal);
    if (btnOpenEditorEmpty) btnOpenEditorEmpty.addEventListener('click', openModal);
    if (btnCloseEditor) btnCloseEditor.addEventListener('click', closeModal);
    if (btnAddRound) btnAddRound.addEventListener('click', newEditorRound);

    // Close on backdrop click or ESC key
    if (modalEditor) {
        modalEditor.addEventListener('click', (e) => {
            if (e.target === modalEditor) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalEditor.style.display !== 'none') {
                closeModal();
            }
        });
    }

    // Delete single round
    if (btnDeleteRound) {
        btnDeleteRound.addEventListener('click', async () => {
            if (workingRounds.length <= 1) {
                showGameAlert({
                    title: 'Validation',
                    message: 'At least 1 round must remain.',
                    icon: 'ℹ️',
                });
                return;
            }

            if (!confirm(`Are you sure you want to delete Round ${editingRoundIndex + 1}?`)) return;

            const targetRound = workingRounds[editingRoundIndex];
            if (targetRound.id) {
                try {
                    const res = await deleteJson(`/game/growth-100/round/${targetRound.id}`);
                    if (res.success) {
                        rounds = res.rounds;
                        window.BYC_GAME2.rounds = rounds;
                        workingRounds = JSON.parse(JSON.stringify(rounds));
                        currentIndex = Math.max(0, Math.min(currentIndex, rounds.length - 1));
                        editingRoundIndex = Math.max(0, Math.min(editingRoundIndex, workingRounds.length - 1));
                        updateRoundUI();
                        selectEditorRoundIndex(editingRoundIndex);
                    }
                } catch (err) {
                    showGameAlert({
                        title: 'Error',
                        message: 'Failed to delete round: ' + err.message,
                        icon: '⚠️',
                    });
                }
            } else {
                workingRounds.splice(editingRoundIndex, 1);
                editingRoundIndex = Math.max(0, editingRoundIndex - 1);
                selectEditorRoundIndex(editingRoundIndex);
            }
        });
    }

    // Save active single round
    if (btnSaveRound) {
        btnSaveRound.addEventListener('click', async () => {
            flushActiveFormToWorkingRound();
            const validation = validateSingleRound(workingRounds[editingRoundIndex], editingRoundIndex + 1);
            if (!validation.valid) {
                showGameAlert({
                    title: 'Validation',
                    message: validation.error,
                    icon: 'ℹ️',
                });
                return;
            }

            const current = workingRounds[editingRoundIndex];

            try {
                btnSaveRound.disabled = true;
                btnSaveRound.textContent = 'Saving...';

                const payload = {
                    id: current.id || null,
                    question: current.question,
                    answers: current.answers,
                };

                const res = await postJson('/game/growth-100/round', payload);
                if (res.success) {
                    rounds = res.rounds;
                    window.BYC_GAME2.rounds = rounds;
                    currentIndex = Math.max(0, Math.min(currentIndex, rounds.length - 1));
                    updateRoundUI();
                    closeModal();
                }
            } catch (err) {
                showGameAlert({
                    title: 'Error',
                    message: 'Failed to save round: ' + err.message,
                    icon: '⚠️',
                });
            } finally {
                btnSaveRound.disabled = false;
                btnSaveRound.textContent = 'Save Round';
            }
        });
    }

    // Batch Save All Questions in One Operation
    if (btnSaveBatch) {
        btnSaveBatch.addEventListener('click', async () => {
            flushActiveFormToWorkingRound();

            if (workingRounds.length === 0) {
                showGameAlert({
                    title: 'Validation',
                    message: 'At least 1 round is required.',
                    icon: 'ℹ️',
                });
                return;
            }

            // Validate every round in the batch
            for (let i = 0; i < workingRounds.length; i++) {
                const validation = validateSingleRound(workingRounds[i], i + 1);
                if (!validation.valid) {
                    selectEditorRoundIndex(i);
                    showGameAlert({
                        title: 'Validation',
                        message: validation.error,
                        icon: 'ℹ️',
                    });
                    return;
                }
            }

            try {
                btnSaveBatch.disabled = true;
                btnSaveBatch.textContent = 'Saving Batch...';

                const payload = {
                    rounds: workingRounds.map((r) => ({
                        id: r.id || null,
                        question: r.question.trim(),
                        answers: r.answers.map((a) => ({
                            text: a.text.trim(),
                            score: parseInt(a.score, 10) || 0,
                        })),
                    })),
                };

                const res = await postJson('/game/growth-100/batch', payload);
                if (res.success) {
                    rounds = res.rounds;
                    window.BYC_GAME2.rounds = rounds;
                    currentIndex = Math.max(0, Math.min(currentIndex, rounds.length - 1));
                    updateRoundUI();
                    closeModal();
                }
            } catch (err) {
                showGameAlert({
                    title: 'Error',
                    message: 'Batch save failed: ' + err.message,
                    icon: '⚠️',
                });
            } finally {
                btnSaveBatch.disabled = false;
                btnSaveBatch.textContent = 'Save All Questions (Batch)';
            }
        });
    }
}

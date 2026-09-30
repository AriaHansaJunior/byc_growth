import { postJson, postFormData, deleteJson } from './api';
import { showGameAlert } from './dialog';
import {
    animateScoreAward,
    updateScoreboard,
    setupRoundPointAssignment,
    updateTeamCardsVisualState,
    initTeamConfigModal,
} from './universal-game';

export function initGuessMe() {
    if (!window.BYC_GAME1) return;

    let { rounds, state, teams, currentIndex } = window.BYC_GAME1;
    let currentRound = rounds[currentIndex] || rounds[0] || null;
    let roundId = currentRound ? String(currentRound.id) : '1';
    let isRevealed = state && state.revealed ? Boolean(state.revealed[roundId]) : false;

    // Elements
    const roundIndicator = document.getElementById('round-indicator');
    const guessImage = document.getElementById('guess-image');
    const answerBox = document.getElementById('answer-box');
    const answerStatusLabel = document.getElementById('answer-status-label');
    const answerText = document.getElementById('answer-text');
    const answerDescription = document.getElementById('answer-description');
    const roundScoreBadge = document.getElementById('round-score-badge');
    const btnToggleReveal = document.getElementById('btn-toggle-reveal');
    const btnPrevRound = document.getElementById('btn-prev-round');
    const btnNextRound = document.getElementById('btn-next-round');
    const roundDotsContainer = document.getElementById('round-dots-container');
    const hostTeamsContainer = document.getElementById('host-teams-container');

    // Initialize Universal Team Configuration Modal for Game 1
    initTeamConfigModal(teams || [], 'game1');

    // Initialize Universal Round Point Assignment (Exclusive Radio Selection & Transfer)
    setupRoundPointAssignment({
        gameCode: 'game1',
        getCurrentRound: () => currentRound,
        onStateChange: (res) => {
            if (currentRound) {
                currentRound.awarded_team_id = res.awarded_team_id;
                currentRound.awarded_points = res.awarded_points;
            }
        },
    });

    function updateRoundUI() {
        if (!rounds.length) return;
        currentRound = rounds[currentIndex];
        roundId = String(currentRound.id);
        isRevealed = state && state.revealed && state.revealed[roundId] ? true : false;

        // Counter
        if (roundIndicator) {
            roundIndicator.innerHTML = `${String(currentIndex + 1).padStart(2, '0')} <em>/ ${String(rounds.length).padStart(2, '0')}</em>`;
        }

        // Image
        if (guessImage) {
            guessImage.src = `/assets/images/${currentRound.image}`;
        }

        // Answer / Clue Box
        if (answerBox) {
            if (isRevealed) {
                answerBox.classList.add('revealed');
                answerStatusLabel.textContent = 'Correct Answer';
                answerText.textContent = currentRound.correct_answer;
                answerDescription.textContent = 'Correct answer revealed. Click a team card below to award the round points.';
                btnToggleReveal.textContent = 'Hide Answer';
                btnToggleReveal.className = 'button button-secondary';
            } else {
                answerBox.classList.remove('revealed');
                answerStatusLabel.textContent = 'Complete the characters';
                answerText.textContent = currentRound.clue;
                answerDescription.textContent = 'Teams discuss and submit answers. The host reveals the answer when time is up.';
                btnToggleReveal.textContent = 'Reveal Answer';
                btnToggleReveal.className = 'button button-primary';
            }
        }

        // Score Badge
        if (roundScoreBadge) {
            roundScoreBadge.textContent = currentRound.score;
        }

        // Update Host Team Cards Visual Selection State
        if (hostTeamsContainer) {
            updateTeamCardsVisualState(
                hostTeamsContainer,
                currentRound.awarded_team_id ?? null,
                currentRound.score ?? 0
            );
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

    async function toggleReveal() {
        if (!currentRound) return;
        const newRevealed = !isRevealed;
        try {
            isRevealed = newRevealed;
            if (!state.revealed) state.revealed = {};
            state.revealed[roundId] = newRevealed;

            updateRoundUI();

            await postJson('/game/guess-me/state', {
                round_index: currentIndex,
                revealed: newRevealed,
            });
        } catch (err) {
            console.error('Failed to toggle reveal state:', err);
        }
    }

    async function goToRound(index) {
        if (index < 0 || index >= rounds.length) return;
        currentIndex = index;
        updateRoundUI();

        try {
            await postJson('/game/guess-me/state', {
                round_index: currentIndex,
            });
        } catch (err) {
            console.error('Failed to update active round:', err);
        }
    }

    async function changeScore(teamIdentifier, amount) {
        if (!window.BYC_GAME1?.isAdmin) {
            showGameAlert({
                title: 'Admin Account Required',
                message: 'You must login as an admin account to input score.',
                eyebrow: 'Notice',
                icon: '🔒',
            });
            return;
        }

        try {
            const res = await postJson('/game/update-score', {
                game: 'game1',
                team: teamIdentifier,
                amount,
            });

            if (res.teams) {
                updateScoreboard(res.teams, 'game1');
            }

            if (amount > 0 && res.team_id) {
                animateScoreAward(res.team_id, amount);
            }
        } catch (err) {
            showGameAlert({
                title: 'Notice',
                message: err.message || 'You must login as an admin account to input score.',
                icon: '⚠️',
            });
        }
    }

    // Attach listeners
    if (btnToggleReveal) {
        btnToggleReveal.addEventListener('click', toggleReveal);
    }

    if (btnPrevRound) {
        btnPrevRound.addEventListener('click', () => goToRound(currentIndex - 1));
    }

    if (btnNextRound) {
        btnNextRound.addEventListener('click', () => goToRound(currentIndex + 1));
    }

    // Direct score adjustment buttons (+5, -5)
    document.querySelectorAll('.btn-score-action').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const team = btn.dataset.team;
            const amount = parseInt(btn.dataset.amount, 10);
            changeScore(team, amount);
        });
    });

    // ==========================================
    // CRUD Editor Modal Logic with Batch Save
    // ==========================================
    const modalEditor = document.getElementById('modal-guess-editor');
    const btnOpenEditor = document.getElementById('btn-open-editor');
    const btnOpenEditorEmpty = document.getElementById('btn-open-editor-empty');
    const btnCloseEditor = document.getElementById('btn-close-guess-editor');
    const btnCancelEditor = document.getElementById('btn-cancel-guess-editor');
    const btnAddRound = document.getElementById('btn-add-guess-round');
    const btnDeleteRound = document.getElementById('btn-delete-guess-round');
    const btnSaveRound = document.getElementById('btn-save-guess-round');
    const btnSaveBatch = document.getElementById('btn-save-batch-guess');

    const form = document.getElementById('form-guess-round');
    const inputId = document.getElementById('guess-round-id');
    const inputAnswer = document.getElementById('guess-round-answer');
    const inputClue = document.getElementById('guess-round-clue');
    const inputScore = document.getElementById('guess-round-score');
    const inputFile = document.getElementById('guess-round-image');
    const uploadBox = document.getElementById('guess-upload-box');
    const uploadLabel = document.getElementById('guess-upload-label');
    const clueHint = document.getElementById('guess-clue-hint');
    const roundsListEl = document.getElementById('guess-round-buttons');

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

        currentWorking.correct_answer = (inputAnswer ? inputAnswer.value.trim().toUpperCase() : (currentWorking.correct_answer || '').toUpperCase());
        currentWorking.clue = (inputClue ? inputClue.value.trim().toUpperCase() : (currentWorking.clue || '').toUpperCase());
        currentWorking.score = (inputScore ? parseInt(inputScore.value, 10) || 20 : currentWorking.score);
    }

    function renderEditorRoundsList() {
        if (!roundsListEl) return;
        roundsListEl.innerHTML = '';
        workingRounds.forEach((r, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.id = `guess-round-btn-${idx}`;
            btn.className = idx === editingRoundIndex ? 'active is-selected' : '';
            btn.setAttribute('aria-selected', idx === editingRoundIndex ? 'true' : 'false');
            btn.setAttribute('role', 'tab');
            btn.innerHTML = `<strong>Round ${idx + 1}</strong><small>${r.score || 20} pts</small>`;
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
        inputAnswer.value = (target.correct_answer || '').toUpperCase();
        inputClue.value = (target.clue || '').toUpperCase();
        inputScore.value = target.score || 20;
        uploadLabel.textContent = target.image ? `Image: ${target.image}` : 'Click to select round image';

        if (btnDeleteRound) {
            btnDeleteRound.style.display = workingRounds.length > 1 ? 'inline-block' : 'none';
        }

        validateClueInput();
        renderEditorRoundsList();
    }

    function newEditorRound() {
        flushActiveFormToWorkingRound();
        const newRound = {
            id: null,
            round_number: workingRounds.length + 1,
            correct_answer: '',
            clue: '',
            score: 20,
            image: 'BYC_Growth.jpg',
        };
        workingRounds.push(newRound);
        selectEditorRoundIndex(workingRounds.length - 1);
    }

    function validateSingleRound(round, roundNum) {
        const answer = (round.correct_answer || '').trim().toUpperCase();
        const clue = (round.clue || '').trim().toUpperCase();
        const score = parseInt(round.score, 10);

        if (!answer) {
            return { valid: false, error: `Round ${roundNum}: Correct answer cannot be empty.` };
        }
        if (!clue) {
            return { valid: false, error: `Round ${roundNum}: Clue cannot be empty.` };
        }
        if (isNaN(score) || score < 1) {
            return { valid: false, error: `Round ${roundNum}: Round score must be at least 1.` };
        }

        const answerChars = answer.split('');
        const answerLen = answerChars.length;
        const clueNoSpaces = clue.replace(/\s+/g, '').split('');

        let clueTokens = [];
        if (clue.split('').length === answerLen) {
            clueTokens = clue.split('');
        } else if (clueNoSpaces.length === answerLen) {
            clueTokens = clueNoSpaces;
        } else {
            return {
                valid: false,
                error: `Round ${roundNum}: Clue length (${clueNoSpaces.length}) must match answer length (${answerLen}).`,
            };
        }

        for (let i = 0; i < answerLen; i++) {
            const c = clueTokens[i].toUpperCase();
            const a = answerChars[i];
            if (c === '_' || c === '-' || c === '.') continue;
            if (c !== a) {
                return {
                    valid: false,
                    error: `Round ${roundNum}: Character at position ${i + 1} ('${c}') differs from answer ('${a}').`,
                };
            }
        }

        return { valid: true };
    }

    function validateClueInput() {
        const answer = inputAnswer.value.trim().toUpperCase();
        const clue = inputClue.value.trim().toUpperCase();

        if (!answer || !clue) {
            clueHint.innerHTML = 'Use underscore _ for hidden characters.';
            clueHint.className = 'field-hint';
            return false;
        }

        const res = validateSingleRound({ correct_answer: answer, clue: clue, score: inputScore.value }, editingRoundIndex + 1);
        if (!res.valid) {
            clueHint.innerHTML = `<span style="color: var(--red);">✗ ${res.error}</span>`;
            clueHint.className = 'field-hint';
            return false;
        }

        clueHint.innerHTML = `<span class="validation-good">✓ Valid clue · ${answer.length} characters aligned</span>`;
        clueHint.className = 'field-hint good';
        return true;
    }

    // Auto-uppercase on user input with cursor preservation
    if (inputAnswer) {
        inputAnswer.addEventListener('input', () => {
            const start = inputAnswer.selectionStart;
            const end = inputAnswer.selectionEnd;
            const upper = inputAnswer.value.toUpperCase();
            if (inputAnswer.value !== upper) {
                inputAnswer.value = upper;
                if (start !== null && end !== null) {
                    inputAnswer.setSelectionRange(start, end);
                }
            }
            flushActiveFormToWorkingRound();
            validateClueInput();
        });
    }

    if (inputClue) {
        inputClue.addEventListener('input', () => {
            const start = inputClue.selectionStart;
            const end = inputClue.selectionEnd;
            const upper = inputClue.value.toUpperCase();
            if (inputClue.value !== upper) {
                inputClue.value = upper;
                if (start !== null && end !== null) {
                    inputClue.setSelectionRange(start, end);
                }
            }
            flushActiveFormToWorkingRound();
            validateClueInput();
        });
    }

    if (uploadBox && inputFile) {
        uploadBox.addEventListener('click', () => inputFile.click());
        inputFile.addEventListener('change', () => {
            if (inputFile.files && inputFile.files[0]) {
                uploadLabel.textContent = `File: ${inputFile.files[0].name}`;
            }
        });
    }

    if (btnOpenEditor) btnOpenEditor.addEventListener('click', openModal);
    if (btnOpenEditorEmpty) btnOpenEditorEmpty.addEventListener('click', openModal);
    if (btnCloseEditor) btnCloseEditor.addEventListener('click', closeModal);
    if (btnCancelEditor) btnCancelEditor.addEventListener('click', closeModal);
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
                    title: 'Round Management',
                    message: 'At least 1 round must remain.',
                    eyebrow: 'Validation',
                    icon: 'ℹ️',
                });
                return;
            }

            if (!confirm(`Are you sure you want to delete Round ${editingRoundIndex + 1}?`)) return;

            const targetRound = workingRounds[editingRoundIndex];
            if (targetRound.id) {
                try {
                    const res = await deleteJson(`/game/guess-me/round/${targetRound.id}`);
                    if (res.success) {
                        rounds = res.rounds;
                        window.BYC_GAME1.rounds = rounds;
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

    // Save active single round (with file upload support)
    if (btnSaveRound) {
        btnSaveRound.addEventListener('click', async () => {
            if (!validateClueInput()) {
                showGameAlert({
                    title: 'Clue Validation',
                    message: 'Please resolve clue validation errors before saving.',
                    eyebrow: 'Validation',
                    icon: 'ℹ️',
                });
                return;
            }

            // Ensure field values are uppercase before submit
            if (inputAnswer) inputAnswer.value = inputAnswer.value.trim().toUpperCase();
            if (inputClue) inputClue.value = inputClue.value.trim().toUpperCase();

            const formData = new FormData(form);

            try {
                btnSaveRound.disabled = true;
                btnSaveRound.textContent = 'Saving...';

                const res = await postFormData('/game/guess-me/round', formData);
                if (res.success) {
                    rounds = res.rounds;
                    window.BYC_GAME1.rounds = rounds;
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

    // Batch Save All Rounds in One Operation
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
                        title: 'Validation Error',
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
                    rounds: workingRounds.map((r, idx) => ({
                        id: r.id || null,
                        correct_answer: (r.correct_answer || '').trim().toUpperCase(),
                        clue: (r.clue || '').trim().toUpperCase(),
                        score: parseInt(r.score, 10) || 20,
                        image: r.image || 'BYC_Growth.jpg',
                    })),
                };

                const res = await postJson('/game/guess-me/batch', payload);
                if (res.success) {
                    rounds = res.rounds;
                    window.BYC_GAME1.rounds = rounds;
                    currentIndex = Math.max(0, Math.min(currentIndex, rounds.length - 1));
                    updateRoundUI();
                    closeModal();
                }
            } catch (err) {
                showGameAlert({
                    title: 'Batch Save Error',
                    message: 'Batch save failed: ' + err.message,
                    icon: '⚠️',
                });
            } finally {
                btnSaveBatch.disabled = false;
                btnSaveBatch.textContent = 'Save All Rounds (Batch)';
            }
        });
    }
}

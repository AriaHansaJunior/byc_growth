import { postJson, deleteJson } from './api';

export function initGrowth100() {
    if (!window.BYC_GAME2) return;

    let { rounds, state, currentIndex } = window.BYC_GAME2;
    let currentRound = rounds[currentIndex] || rounds[0] || null;
    let roundId = currentRound ? String(currentRound.id) : '1';

    // Elements
    const roundIndicator = document.getElementById('round-indicator');
    const surveyQuestionText = document.getElementById('survey-question-text');
    const answersGridContainer = document.getElementById('answers-grid-container');
    const roundRevealedPointsEl = document.getElementById('round-revealed-points');
    const btnAddTotalRed = document.getElementById('btn-add-total-red');
    const btnAddTotalBlue = document.getElementById('btn-add-total-blue');
    const btnPrevRound = document.getElementById('btn-prev-round');
    const btnNextRound = document.getElementById('btn-next-round');
    const roundDotsContainer = document.getElementById('round-dots-container');

    const btnRevealAll = document.getElementById('btn-reveal-all');
    const btnHideAll = document.getElementById('btn-hide-all');
    const btnResetCrosses = document.getElementById('btn-reset-crosses');
    const crossOverlay = document.getElementById('cross-overlay');
    const crossOverlayContent = document.getElementById('cross-overlay-content');

    // Score elements in topbar
    const scoreRedEl = document.querySelector('.team-red strong');
    const scoreBlueEl = document.querySelector('.team-blue strong');

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

        // Total Points
        if (roundRevealedPointsEl) {
            roundRevealedPointsEl.textContent = total;
        }

        document.querySelectorAll('.current-total-label').forEach((el) => {
            el.textContent = total;
        });

        if (btnAddTotalRed) btnAddTotalRed.disabled = total === 0;
        if (btnAddTotalBlue) btnAddTotalBlue.disabled = total === 0;

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
                    <span class="answer-text-label">${isOpen ? ans.text : 'Klik untuk buka'}</span>
                    <em>${isOpen ? ans.score : '?'}</em>
                `;
                btn.addEventListener('click', () => toggleAnswer(idx));
                answersGridContainer.appendChild(btn);
            });
        }

        // Cross Buttons
        document.querySelectorAll('.btn-cross').forEach((btn) => {
            const count = parseInt(btn.dataset.cross, 10);
            if (crosses >= count) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

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
        if (!state.crosses) state.crosses = {};
        state.crosses[roundId] = count;

        // Show visual overlay with animation
        if (crossOverlay && crossOverlayContent) {
            crossOverlayContent.innerHTML = Array.from({ length: count }, () => '<b>×</b>').join('');
            crossOverlay.style.display = 'grid';

            setTimeout(() => {
                crossOverlay.style.display = 'none';
            }, 1200);
        }

        updateRoundUI();

        try {
            await postJson('/game/growth-100/state', {
                round_index: currentIndex,
                crosses: count,
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

    async function changeScore(team, amount) {
        try {
            const res = await postJson('/game/update-score', {
                game: 'game2',
                team,
                amount,
            });

            if (res.scores) {
                if (scoreRedEl) scoreRedEl.textContent = res.scores.red;
                if (scoreBlueEl) scoreBlueEl.textContent = res.scores.blue;
            }
        } catch (err) {
            alert('Gagal mengupdate skor: ' + err.message);
        }
    }

    // Attach listeners
    if (btnRevealAll) btnRevealAll.addEventListener('click', () => revealAll(true));
    if (btnHideAll) btnHideAll.addEventListener('click', () => revealAll(false));

    document.querySelectorAll('.btn-cross').forEach((btn) => {
        btn.addEventListener('click', () => {
            const count = parseInt(btn.dataset.cross, 10);
            setCrosses(count);
        });
    });

    if (btnResetCrosses) {
        btnResetCrosses.addEventListener('click', () => setCrosses(0));
    }

    if (btnAddTotalRed) {
        btnAddTotalRed.addEventListener('click', () => {
            const total = calculateRoundTotal();
            if (total > 0) changeScore('red', total);
        });
    }

    if (btnAddTotalBlue) {
        btnAddTotalBlue.addEventListener('click', () => {
            const total = calculateRoundTotal();
            if (total > 0) changeScore('blue', total);
        });
    }

    document.querySelectorAll('.btn-score-action').forEach((btn) => {
        btn.addEventListener('click', () => {
            const team = btn.dataset.team;
            const amount = parseInt(btn.dataset.amount, 10);
            changeScore(team, amount);
        });
    });

    if (btnPrevRound) {
        btnPrevRound.addEventListener('click', () => goToRound(currentIndex - 1));
    }

    if (btnNextRound) {
        btnNextRound.addEventListener('click', () => goToRound(currentIndex + 1));
    }

    // ==========================================
    // CRUD Editor Modal Logic
    // ==========================================
    const modalEditor = document.getElementById('modal-growth-editor');
    const btnOpenEditor = document.getElementById('btn-open-editor');
    const btnOpenEditorEmpty = document.getElementById('btn-open-editor-empty');
    const btnCloseEditor = document.getElementById('btn-close-growth-editor');
    const btnCancelEditor = document.getElementById('btn-cancel-growth-editor');
    const btnAddRound = document.getElementById('btn-add-growth-round');
    const btnDeleteRound = document.getElementById('btn-delete-growth-round');
    const btnSaveRound = document.getElementById('btn-save-growth-round');

    const form = document.getElementById('form-growth-round');
    const inputId = document.getElementById('growth-round-id');
    const inputQuestion = document.getElementById('growth-round-question');
    const answersContainer = document.getElementById('growth-answers-container');
    const btnAddAnswerRow = document.getElementById('btn-add-answer-row');
    const scoreTotalIndicator = document.getElementById('growth-score-total-indicator');
    const roundsListEl = document.getElementById('growth-round-buttons');

    let editingRoundId = null;

    function openModal() {
        if (!modalEditor) return;
        modalEditor.style.display = 'grid';
        document.body.style.overflow = 'hidden';
        renderEditorRoundsList();
        if (currentRound) {
            selectEditorRound(currentRound.id);
        } else {
            newEditorRound();
        }
    }

    function closeModal() {
        if (!modalEditor) return;
        modalEditor.style.display = 'none';
        document.body.style.overflow = '';
    }

    function renderEditorRoundsList() {
        if (!roundsListEl) return;
        roundsListEl.innerHTML = '';
        rounds.forEach((r, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = r.id === editingRoundId ? 'active' : '';
            btn.innerHTML = `<strong>Ronde ${idx + 1}</strong><small>${r.answers ? r.answers.length : 0} jawaban</small>`;
            btn.addEventListener('click', () => selectEditorRound(r.id));
            roundsListEl.appendChild(btn);
        });
    }

    function selectEditorRound(id) {
        editingRoundId = id;
        const target = rounds.find((r) => r.id === id);
        if (!target) return;

        inputId.value = target.id;
        inputQuestion.value = target.question;
        renderAnswerRows(target.answers || []);
        if (btnDeleteRound) btnDeleteRound.style.display = 'inline-block';

        recalculateEditorTotal();
        renderEditorRoundsList();
    }

    function newEditorRound() {
        editingRoundId = null;
        form.reset();
        inputId.value = '';
        inputQuestion.value = '';
        renderAnswerRows([
            { text: '', score: 35 },
            { text: '', score: 25 },
            { text: '', score: 20 },
            { text: '', score: 20 },
        ]);
        if (btnDeleteRound) btnDeleteRound.style.display = 'none';

        recalculateEditorTotal();
        renderEditorRoundsList();
    }

    function renderAnswerRows(answers) {
        if (!answersContainer) return;
        answersContainer.innerHTML = '';
        answers.forEach((ans, idx) => {
            addAnswerRow(ans.text, ans.score);
        });
        updateRowNumbers();
    }

    function addAnswerRow(text = '', score = 10) {
        const row = document.createElement('div');
        row.className = 'answer-edit-row';
        row.innerHTML = `
            <span class="row-num">1</span>
            <input type="text" class="input-ans-text" placeholder="Teks jawaban" value="${text.replace(/"/g, '&quot;')}" required>
            <input type="number" class="input-ans-score" value="${score}" min="1" max="100" required>
            <button type="button" class="btn-remove-row" aria-label="Hapus jawaban">×</button>
        `;

        row.querySelector('.input-ans-score').addEventListener('input', recalculateEditorTotal);
        row.querySelector('.input-ans-text').addEventListener('input', recalculateEditorTotal);
        row.querySelector('.btn-remove-row').addEventListener('click', () => {
            if (answersContainer.querySelectorAll('.answer-edit-row').length <= 1) {
                alert('Minimal harus ada 1 jawaban.');
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
            scoreTotalIndicator.textContent = `Total: ${sum} / 100 (Harus tepat 100)`;
            scoreTotalIndicator.className = '';
            scoreTotalIndicator.style.color = 'var(--red)';
        }
    }

    if (btnAddAnswerRow) {
        btnAddAnswerRow.addEventListener('click', () => addAnswerRow('', 10));
    }

    if (btnOpenEditor) btnOpenEditor.addEventListener('click', openModal);
    if (btnOpenEditorEmpty) btnOpenEditorEmpty.addEventListener('click', openModal);
    if (btnCloseEditor) btnCloseEditor.addEventListener('click', closeModal);
    if (btnCancelEditor) btnCancelEditor.addEventListener('click', closeModal);
    if (btnAddRound) btnAddRound.addEventListener('click', newEditorRound);

    if (btnDeleteRound) {
        btnDeleteRound.addEventListener('click', async () => {
            if (!editingRoundId) return;
            if (!confirm('Apakah Anda yakin ingin menghapus soal survei ini?')) return;

            try {
                const res = await deleteJson(`/game/growth-100/round/${editingRoundId}`);
                if (res.success) {
                    rounds = res.rounds;
                    window.BYC_GAME2.rounds = rounds;
                    currentIndex = Math.max(0, Math.min(currentIndex, rounds.length - 1));
                    updateRoundUI();
                    closeModal();
                }
            } catch (err) {
                alert('Gagal menghapus soal survei: ' + err.message);
            }
        });
    }

    if (btnSaveRound) {
        btnSaveRound.addEventListener('click', async () => {
            const question = inputQuestion.value.trim();
            if (!question) {
                alert('Pertanyaan survei tidak boleh kosong.');
                return;
            }

            const answers = [];
            let totalScore = 0;

            answersContainer.querySelectorAll('.answer-edit-row').forEach((row) => {
                const text = row.querySelector('.input-ans-text').value.trim();
                const score = parseInt(row.querySelector('.input-ans-score').value, 10) || 0;
                answers.push({ text, score });
                totalScore += score;
            });

            if (totalScore !== 100) {
                alert(`Total seluruh poin jawaban harus tepat 100. Saat ini berjumlah ${totalScore}.`);
                return;
            }

            try {
                btnSaveRound.disabled = true;
                btnSaveRound.textContent = 'Menyimpan...';

                const payload = {
                    id: inputId.value ? parseInt(inputId.value, 10) : null,
                    question,
                    answers,
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
                alert('Gagal menyimpan soal survei: ' + err.message);
            } finally {
                btnSaveRound.disabled = false;
                btnSaveRound.textContent = 'Simpan perubahan';
            }
        });
    }
}

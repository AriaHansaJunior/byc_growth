import { postJson, postFormData, deleteJson } from './api';

export function initGuessMe() {
    if (!window.BYC_GAME1) return;

    let { rounds, state, currentIndex } = window.BYC_GAME1;
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

    // Score elements in topbar
    const scoreRedEl = document.querySelector('.team-red strong');
    const scoreBlueEl = document.querySelector('.team-blue strong');

    function updateRoundUI() {
        if (!rounds.length) return;
        currentRound = rounds[currentIndex];
        roundId = String(currentRound.id);
        isRevealed = state.revealed && state.revealed[roundId] ? true : false;

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
                answerStatusLabel.textContent = 'Jawaban benar';
                answerText.textContent = currentRound.correct_answer;
                answerDescription.textContent = 'Jawaban telah ditampilkan. Berikan poin kepada tim yang menjawab benar.';
                btnToggleReveal.textContent = 'Sembunyikan jawaban';
                btnToggleReveal.className = 'button button-secondary';
            } else {
                answerBox.classList.remove('revealed');
                answerStatusLabel.textContent = 'Lengkapi karakter berikut';
                answerText.textContent = currentRound.clue;
                answerDescription.textContent = 'Diskusikan bersama tim. Host dapat menampilkan jawaban saat waktunya habis.';
                btnToggleReveal.textContent = 'Tampilkan jawaban';
                btnToggleReveal.className = 'button button-primary';
            }
        }

        // Score Badge
        if (roundScoreBadge) {
            roundScoreBadge.textContent = currentRound.score;
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

    async function changeScore(team, amount) {
        try {
            const res = await postJson('/game/update-score', {
                game: 'game1',
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
    if (btnToggleReveal) {
        btnToggleReveal.addEventListener('click', toggleReveal);
    }

    if (btnPrevRound) {
        btnPrevRound.addEventListener('click', () => goToRound(currentIndex - 1));
    }

    if (btnNextRound) {
        btnNextRound.addEventListener('click', () => goToRound(currentIndex + 1));
    }

    document.querySelectorAll('.btn-score-action').forEach((btn) => {
        btn.addEventListener('click', () => {
            const team = btn.dataset.team;
            const amount = parseInt(btn.dataset.amount, 10);
            changeScore(team, amount);
        });
    });

    document.querySelectorAll('.btn-score-round').forEach((btn) => {
        btn.addEventListener('click', () => {
            const team = btn.dataset.team;
            if (currentRound) {
                changeScore(team, currentRound.score);
            }
        });
    });

    // ==========================================
    // CRUD Editor Modal Logic
    // ==========================================
    const modalEditor = document.getElementById('modal-guess-editor');
    const btnOpenEditor = document.getElementById('btn-open-editor');
    const btnOpenEditorEmpty = document.getElementById('btn-open-editor-empty');
    const btnCloseEditor = document.getElementById('btn-close-guess-editor');
    const btnCancelEditor = document.getElementById('btn-cancel-guess-editor');
    const btnAddRound = document.getElementById('btn-add-guess-round');
    const btnDeleteRound = document.getElementById('btn-delete-guess-round');
    const btnSaveRound = document.getElementById('btn-save-guess-round');

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
            btn.innerHTML = `<strong>Ronde ${idx + 1}</strong><small>${r.score} poin</small>`;
            btn.addEventListener('click', () => selectEditorRound(r.id));
            roundsListEl.appendChild(btn);
        });
    }

    function selectEditorRound(id) {
        editingRoundId = id;
        const target = rounds.find((r) => r.id === id);
        if (!target) return;

        inputId.value = target.id;
        inputAnswer.value = target.correct_answer;
        inputClue.value = target.clue;
        inputScore.value = target.score;
        uploadLabel.textContent = `Gambar: ${target.image}`;
        if (btnDeleteRound) btnDeleteRound.style.display = 'inline-block';

        validateClueInput();
        renderEditorRoundsList();
    }

    function newEditorRound() {
        editingRoundId = null;
        form.reset();
        inputId.value = '';
        inputScore.value = '20';
        uploadLabel.textContent = 'Klik untuk pilih gambar ronde';
        if (btnDeleteRound) btnDeleteRound.style.display = 'none';

        validateClueInput();
        renderEditorRoundsList();
    }

    function validateClueInput() {
        const answer = inputAnswer.value.trim().toUpperCase();
        const clue = inputClue.value.trim();

        if (!answer || !clue) {
            clueHint.innerHTML = 'Gunakan tanda _ untuk huruf tersembunyi.';
            clueHint.className = 'field-hint';
            return false;
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
            clueHint.innerHTML = `<span style="color: var(--red);">✗ Jumlah karakter clue (${clueNoSpaces.length}) harus sama dengan jawaban (${answerLen}).</span>`;
            clueHint.className = 'field-hint';
            return false;
        }

        for (let i = 0; i < answerLen; i++) {
            const c = clueTokens[i].toUpperCase();
            const a = answerChars[i];

            if (c === '_' || c === '-' || c === '.') continue;

            if (c !== a) {
                clueHint.innerHTML = `<span style="color: var(--red);">✗ Karakter posisi ke-${i + 1} ('${c}') berbeda dengan huruf jawaban ('${a}').</span>`;
                clueHint.className = 'field-hint';
                return false;
            }
        }

        clueHint.innerHTML = `<span class="validation-good">✓ Clue valid · ${answerLen} posisi karakter sesuai</span>`;
        clueHint.className = 'field-hint good';
        return true;
    }

    if (inputAnswer) inputAnswer.addEventListener('input', validateClueInput);
    if (inputClue) inputClue.addEventListener('input', validateClueInput);

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

    if (btnDeleteRound) {
        btnDeleteRound.addEventListener('click', async () => {
            if (!editingRoundId) return;
            if (!confirm('Apakah Anda yakin ingin menghapus ronde ini?')) return;

            try {
                const res = await deleteJson(`/game/guess-me/round/${editingRoundId}`);
                if (res.success) {
                    rounds = res.rounds;
                    window.BYC_GAME1.rounds = rounds;
                    currentIndex = Math.max(0, Math.min(currentIndex, rounds.length - 1));
                    updateRoundUI();
                    closeModal();
                }
            } catch (err) {
                alert('Gagal menghapus ronde: ' + err.message);
            }
        });
    }

    if (btnSaveRound) {
        btnSaveRound.addEventListener('click', async () => {
            if (!validateClueInput()) {
                alert('Mohon perbaiki clue agar sesuai dengan jawaban sebelum menyimpan.');
                return;
            }

            const formData = new FormData(form);

            try {
                btnSaveRound.disabled = true;
                btnSaveRound.textContent = 'Menyimpan...';

                const res = await postFormData('/game/guess-me/round', formData);
                if (res.success) {
                    rounds = res.rounds;
                    window.BYC_GAME1.rounds = rounds;
                    currentIndex = Math.max(0, Math.min(currentIndex, rounds.length - 1));
                    updateRoundUI();
                    closeModal();
                }
            } catch (err) {
                alert('Gagal menyimpan ronde: ' + err.message);
            } finally {
                btnSaveRound.disabled = false;
                btnSaveRound.textContent = 'Simpan perubahan';
            }
        });
    }
}

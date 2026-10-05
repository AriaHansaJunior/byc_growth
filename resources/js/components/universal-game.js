import { postJson } from './api';
import { showGameAlert } from './dialog';

/**
 * Universal Game System Helper
 * Shared logic for dynamic teams, scoreboard updates, animations, and point assignments.
 */

export const TEAM_COLOR_PALETTE = [
    { code: 'red', name: 'Red', hex: '#bd4c42', soft: '#f3e0da', text: '#87352f' },
    { code: 'blue', name: 'Blue', hex: '#315e89', soft: '#dfe9f2', text: '#244d73' },
    { code: 'forest', name: 'Forest Green', hex: '#284e3b', soft: '#e3eddf', text: '#1b3b2b' },
    { code: 'gold', name: 'Warm Gold', hex: '#c9962a', soft: '#f8edd2', text: '#7a5710' },
    { code: 'purple', name: 'Royal Purple', hex: '#6b4c82', soft: '#ede6f2', text: '#4a2f5e' },
    { code: 'teal', name: 'Deep Teal', hex: '#296b6b', soft: '#dbefef', text: '#174747' },
];

/**
 * Animate score change with floating indicator and pulse
 */
export function animateScoreAward(teamId, points) {
    if (!points || points <= 0) return;

    const targets = [
        document.getElementById(`score-team-${teamId}`),
        document.getElementById(`host-team-score-${teamId}`),
        document.getElementById(`growth-team-score-${teamId}`),
    ].filter(Boolean);

    targets.forEach((scoreNumEl) => {
        const card = scoreNumEl.closest('.team-score') ||
                     scoreNumEl.closest('.host-team-card') ||
                     scoreNumEl.closest('.growth-team-card') ||
                     scoreNumEl.parentElement;

        if (!card) return;

        // Ensure relative positioning
        const originalPos = window.getComputedStyle(card).position;
        if (originalPos === 'static') {
            card.style.position = 'relative';
        }

        // Create floating score element
        const floater = document.createElement('span');
        floater.className = 'floating-score-indicator';
        floater.textContent = `+${points}`;
        card.appendChild(floater);

        // Pulse the card
        card.classList.remove('score-pulse');
        void card.offsetWidth; // Force reflow
        card.classList.add('score-pulse');

        setTimeout(() => {
            card.classList.remove('score-pulse');
        }, 600);

        setTimeout(() => {
            if (floater.parentNode) {
                floater.parentNode.removeChild(floater);
            }
        }, 950);
    });
}

/**
 * Update scoreboard and host cards with latest scores
 */
export function updateScoreboard(teams, gameCode = null) {
    if (!teams || !Array.isArray(teams)) return;

    teams.forEach((team) => {
        const gameScoreVal = (gameCode && team.scores && team.scores[gameCode] !== undefined)
            ? team.scores[gameCode]
            : (team.score ?? 0);
        const totalScoreVal = team.total_score ?? team.score ?? 0;

        // Scoreboard in topbar
        const scoreEl = document.getElementById(`score-team-${team.id}`);
        if (scoreEl) {
            scoreEl.textContent = gameScoreVal;
        }

        const scoreValEl = document.getElementById(`team-score-val-${team.id}`);
        if (scoreValEl) {
            scoreValEl.textContent = gameScoreVal;
        }

        // Legacy / code based fallback
        const codeScoreEl = document.getElementById(`score-team-code-${team.code}`);
        if (codeScoreEl) {
            codeScoreEl.textContent = gameScoreVal;
        }

        const codeScoreValEl = document.getElementById(`team-score-val-${team.code}`);
        if (codeScoreValEl) {
            codeScoreValEl.textContent = gameScoreVal;
        }

        // Host card specific game score
        const hostGameScoreEl = document.getElementById(`host-team-score-${team.id}`);
        if (hostGameScoreEl) {
            hostGameScoreEl.textContent = gameScoreVal;
        }

        const growthScoreEl = document.getElementById(`growth-team-score-${team.id}`);
        if (growthScoreEl) {
            growthScoreEl.textContent = gameScoreVal;
        }

        // Host card total score
        const hostTotalScoreEl = document.getElementById(`host-team-total-${team.id}`);
        if (hostTotalScoreEl) {
            hostTotalScoreEl.textContent = totalScoreVal;
        }
    });
}

/**
 * Universal Round Point Assignment Handler
 */
export function setupRoundPointAssignment({ gameCode, getCurrentRound, onStateChange }) {
    const container = document.getElementById('host-teams-container') ||
                      document.getElementById('growth-teams-container') ||
                      document.querySelector('.growth-teams-grid');

    if (!container) return;

    container.addEventListener('click', async (e) => {
        const teamCard = e.target.closest('.host-team-card, .growth-team-card');
        if (!teamCard) return;

        // If clicked on +/- adjustment buttons, do not trigger round award
        if (e.target.closest('.btn-score-action')) return;

        const isAdmin = (window.BYC_GAME1 && window.BYC_GAME1.isAdmin) ||
                        (window.BYC_GAME2 && window.BYC_GAME2.isAdmin);

        if (!isAdmin) {
            showGameAlert({
                title: 'Admin Account Required',
                message: 'You must login as an admin account to input score.',
                eyebrow: 'Notice',
                icon: '🔒',
            });
            return;
        }

        const teamId = parseInt(teamCard.dataset.teamId, 10);
        if (!teamId) return;

        const currentRound = getCurrentRound();
        if (!currentRound) return;

        const currentRoundId = currentRound.id;

        try {
            // Disable container temporarily to avoid double clicks
            container.style.pointerEvents = 'none';

            const res = await postJson('/game/assign-round-points', {
                game: gameCode,
                round_id: currentRoundId,
                team_id: teamId,
            });

            if (res.success) {
                // Update local round data
                currentRound.awarded_team_id = res.awarded_team_id;
                currentRound.awarded_points = res.awarded_points;

                // Visual Radio UI: Active recipient vs Dimmed other teams
                updateTeamCardsVisualState(container, res.awarded_team_id, res.awarded_points);

                // Update scores across scoreboard
                if (res.teams) {
                    updateScoreboard(res.teams, gameCode);
                }

                // Animate score update if points were awarded or transferred
                if (res.awarded_team_id && res.awarded_points > 0) {
                    animateScoreAward(res.awarded_team_id, res.awarded_points);
                }

                if (typeof onStateChange === 'function') {
                    onStateChange(res);
                }
            }
        } catch (err) {
            showGameAlert({
                title: 'Notice',
                message: err.message || 'You must login as an admin account to input score.',
                eyebrow: 'Notice',
                icon: '⚠️',
            });
        } finally {
            container.style.pointerEvents = '';
        }
    });
}

/**
 * Update visual selection state across all team cards in the host area
 */
export function updateTeamCardsVisualState(container, awardedTeamId, points = 0) {
    if (!container) return;

    const cards = container.querySelectorAll('.host-team-card, .growth-team-card');

    cards.forEach((card) => {
        const id = parseInt(card.dataset.teamId, 10);
        const pill = card.querySelector('.award-status-pill');
        const awardBtn = card.querySelector('.btn-award-round');

        if (awardedTeamId !== null && id === awardedTeamId) {
            // Selected team
            card.classList.add('is-selected');
            card.classList.remove('is-dimmed');

            if (pill) {
                pill.style.display = 'inline-flex';
                pill.textContent = `Recipient (+${points})`;
            }

            if (awardBtn) {
                awardBtn.className = 'button button-primary btn-award-round';
                awardBtn.textContent = 'Points Awarded';
            }
        } else if (awardedTeamId !== null) {
            // Other teams: dimmed but clickable
            card.classList.remove('is-selected');
            card.classList.add('is-dimmed');

            if (pill) {
                pill.style.display = 'none';
            }

            if (awardBtn) {
                awardBtn.className = 'button button-secondary btn-award-round';
                awardBtn.textContent = 'Award Round';
            }
        } else {
            // Unassigned: all normal
            card.classList.remove('is-selected');
            card.classList.remove('is-dimmed');

            if (pill) {
                pill.style.display = 'none';
            }

            if (awardBtn) {
                awardBtn.className = 'button button-secondary btn-award-round';
                awardBtn.textContent = 'Award Round';
            }
        }
    });
}

/**
 * Initialize Team Configuration Modal
 */
export function initTeamConfigModal(initialTeams = [], gameCode = 'game1') {
    const modal = document.getElementById('modal-team-config');
    const openBtn = document.getElementById('btn-open-teams-modal');
    const closeBtn = document.getElementById('btn-close-team-config');
    const cancelBtn = document.getElementById('btn-cancel-team-config');
    const form = document.getElementById('form-team-config');
    const rowsContainer = document.getElementById('team-rows-container');
    const addRowBtn = document.getElementById('btn-add-team-row');
    const presetBtns = document.querySelectorAll('.btn-preset-teams');
    const statusEl = document.getElementById('team-config-status');

    if (!modal) return;

    let workingTeams = Array.isArray(initialTeams) && initialTeams.length >= 2
        ? JSON.parse(JSON.stringify(initialTeams))
        : [
            { name: 'Red', color: 'red' },
            { name: 'Blue', color: 'blue' },
        ];

    function openModal() {
        renderRows();
        modal.style.display = 'grid';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function renderRows() {
        if (!rowsContainer) return;
        rowsContainer.innerHTML = '';

        workingTeams.forEach((team, idx) => {
            const row = document.createElement('div');
            row.className = 'team-config-row';
            row.style.cssText = 'display: grid; grid-template-columns: 32px 1fr 150px 36px; gap: 8px; align-items: center; background: var(--white); padding: 8px 12px; border-radius: 12px; border: 1px solid var(--line);';

            const colorOptions = TEAM_COLOR_PALETTE.map((c) => `
                <option value="${c.code}" ${team.color === c.code ? 'selected' : ''}>
                    ${c.name}
                </option>
            `).join('');

            row.innerHTML = `
                <span style="font-weight: 800; color: var(--forest); font-size: 14px; text-align: center;">${idx + 1}</span>
                <input type="text" class="team-name-input" value="${(team.name || '').replace(/"/g, '&quot;')}" placeholder="Team Name" required style="width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 6px 10px; font-size: 14px; font-weight: 600;">
                <select class="team-color-select" style="border: 1px solid var(--line); border-radius: 8px; padding: 6px 8px; font-size: 13px; font-weight: 600; background: var(--paper); cursor: pointer;">
                    ${colorOptions}
                </select>
                <button type="button" class="btn-remove-team" aria-label="Remove team" style="border: 0; background: transparent; color: var(--red); font-size: 20px; font-weight: 700; cursor: pointer; display: grid; place-items: center;" ${workingTeams.length <= 2 ? 'disabled' : ''}>
                    ×
                </button>
            `;

            row.querySelector('.team-name-input').addEventListener('input', (e) => {
                workingTeams[idx].name = e.target.value;
            });

            row.querySelector('.team-color-select').addEventListener('change', (e) => {
                workingTeams[idx].color = e.target.value;
            });

            const removeBtn = row.querySelector('.btn-remove-team');
            removeBtn.addEventListener('click', () => {
                if (workingTeams.length <= 2) {
                    showGameAlert({
                        title: 'Team Configuration',
                        message: 'At least 2 teams are required.',
                        eyebrow: 'Validation',
                        icon: 'ℹ️',
                    });
                    return;
                }
                workingTeams.splice(idx, 1);
                renderRows();
            });

            rowsContainer.appendChild(row);
        });

        if (statusEl) {
            statusEl.textContent = `${workingTeams.length} teams configured (Min. 2)`;
        }
    }

    function applyPreset(count) {
        const presets = {
            2: [
                { name: 'Alpha', color: 'red' },
                { name: 'Beta', color: 'blue' },
            ],
            3: [
                { name: 'Alpha', color: 'red' },
                { name: 'Beta', color: 'blue' },
                { name: 'Gamma', color: 'forest' },
            ],
            4: [
                { name: 'Alpha', color: 'red' },
                { name: 'Beta', color: 'blue' },
                { name: 'Gamma', color: 'forest' },
                { name: 'Delta', color: 'gold' },
            ],
        };

        if (presets[count]) {
            workingTeams = JSON.parse(JSON.stringify(presets[count]));
            renderRows();
        }
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    presetBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const count = parseInt(btn.dataset.count, 10);
            applyPreset(count);
        });
    });

    if (addRowBtn) {
        addRowBtn.addEventListener('click', () => {
            const nextIdx = workingTeams.length;
            const defaultColor = TEAM_COLOR_PALETTE[nextIdx % TEAM_COLOR_PALETTE.length].code;
            const letters = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta'];
            const defaultName = letters[nextIdx] || `Team ${nextIdx + 1}`;

            workingTeams.push({
                name: defaultName,
                color: defaultColor,
            });
            renderRows();
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate
            if (workingTeams.length < 2) {
                showGameAlert({
                    title: 'Team Configuration',
                    message: 'At least 2 teams are required.',
                    eyebrow: 'Validation',
                    icon: 'ℹ️',
                });
                return;
            }

            for (let i = 0; i < workingTeams.length; i++) {
                if (!workingTeams[i].name || !workingTeams[i].name.trim()) {
                    showGameAlert({
                        title: 'Team Configuration',
                        message: `Team ${i + 1} name cannot be empty.`,
                        eyebrow: 'Validation',
                        icon: 'ℹ️',
                    });
                    return;
                }
            }

            const saveBtn = document.getElementById('btn-save-teams');
            try {
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Saving...';
                }

                const res = await postJson('/game/teams/configure', {
                    game: gameCode,
                    teams: workingTeams.map((t) => ({
                        id: t.id || null,
                        name: t.name.trim(),
                        color: t.color,
                    })),
                });

                if (res.success) {
                    // Reload to reflect all updated Blade components & server state
                    window.location.reload();
                }
            } catch (err) {
                showGameAlert({
                    title: 'Team Configuration',
                    message: 'Failed to configure teams: ' + err.message,
                    eyebrow: 'Error',
                    icon: '⚠️',
                });
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Changes';
                }
            }
        });
    }
}

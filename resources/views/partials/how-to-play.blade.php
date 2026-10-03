<div class="modal-backdrop" id="modal-how-to-play" style="display: none;">
    <section aria-label="How to play guide" class="info-modal how-to-play-modal">
        <div class="modal-heading">
            <div>
                <span class="eyebrow">Official Game Guide</span>
                <h2>How to Play</h2>
            </div>
            <button type="button" aria-label="Close guide" class="icon-button" id="btn-close-how-to-play">
                <x-icon name="x" />
            </button>
        </div>

        {{-- Tab Switcher --}}
        <div class="how-tabs-nav">
            <button type="button" class="how-tab-btn active" id="tab-btn-guess" data-tab="guess">
                <span class="tab-badge">01</span> How to Play — Guess Me!
            </button>
            <button type="button" class="how-tab-btn" id="tab-btn-growth" data-tab="growth">
                <span class="tab-badge">02</span> How to Play — BYC Growth 100
            </button>
        </div>

        {{-- Scrollable Content Area --}}
        <div class="how-modal-scrollable">
            {{-- Tab 1: Guess Me! --}}
            <div class="how-tab-pane" id="tab-pane-guess" style="display: block;">
                <article class="how-card">
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                        <span style="width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; background: var(--lime); color: var(--forest-dark); font: 800 18px 'Manrope', sans-serif;">01</span>
                        <div>
                            <h3 style="margin: 0; font: 800 22px 'Manrope', sans-serif; color: var(--forest-dark);">Guess Me! — Game Rules</h3>
                            <small style="color: var(--muted); font-size: 13px;">Test precision and speed in guessing secret keywords</small>
                        </div>
                    </div>
                    <ol style="padding-left: 20px; margin: 0; color: var(--ink); line-height: 1.85; font-size: 15px;">
                        <li>Participants are divided into <strong>Red Team</strong> and <strong>Blue Team</strong>.</li>
                        <li>The host displays the clue image and letter slots to both teams simultaneously.</li>
                        <li>Both teams discuss and write down their answers on paper slips.</li>
                        <li>Once answers are collected, the host reveals the correct secret word.</li>
                        <li>Teams that answer correctly are awarded the round's designated points.</li>
                        <li>If both teams answer correctly, both receive the full round points.</li>
                        <li>The host proceeds to the next round using the round navigation buttons.</li>
                    </ol>
                </article>
            </div>

            {{-- Tab 2: BYC Growth 100 --}}
            <div class="how-tab-pane" id="tab-pane-growth" style="display: none;">
                <article class="how-card">
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                        <span style="width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; background: var(--gold); color: var(--forest-dark); font: 800 18px 'Manrope', sans-serif;">02</span>
                        <div>
                            <h3 style="margin: 0; font: 800 22px 'Manrope', sans-serif; color: var(--forest-dark);">BYC GROWTH 100 — Game Rules</h3>
                            <small style="color: var(--muted); font-size: 13px;">Find top survey answers and collect up to 100 points</small>
                        </div>
                    </div>
                    <ol style="padding-left: 20px; margin: 0; color: var(--ink); line-height: 1.8; font-size: 14px;">
                        <li>Participants are divided into <strong>Red Team</strong> and <strong>Blue Team</strong>.</li>
                        <li>Representatives from both teams come forward for a face-off to determine who plays first.</li>
                        <li>The host reads the survey question to both representatives.</li>
                        <li>The representative who raises their hand first gets the first opportunity to answer.</li>
                        <li>The answer is checked against the survey results on the board.</li>
                        <li>The team with the higher-ranked answer gains control of the round.</li>
                        <li>The same question is posed to team members in turn.</li>
                        <li>Answers present on the survey board are revealed by the host by clicking the card tile.</li>
                        <li>Answers not on the board result in a strike (<span style="color: var(--red); font-weight: 800;">×</span>).</li>
                        <li>Play continues until all team members have had their turn or all answers are found.</li>
                        <li>If unrevealed answers remain, the question returns to the team captain.</li>
                        <li>If all answers are successfully uncovered, all accumulated round points go to that team.</li>
                        <li>If a team accumulates <strong>three strikes (3× cross)</strong>, the opposing team gets a chance to steal.</li>
                        <li>The opposing team gets only <strong>one single guess</strong> to steal.</li>
                        <li>If their answer is on the board, all accumulated points are stolen by the opposing team.</li>
                        <li>If their answer is not on the board, points remain with the original team.</li>
                        <li>After the round concludes, any remaining unrevealed answers can be uncovered by the host.</li>
                        <li>The host awards points to the winning team and advances to the next round.</li>
                    </ol>
                </article>
            </div>

            <div class="tip">
                <strong>Tips for the Host</strong>
                <p>Use full-screen mode (F11) so all participants can clearly view the game. Ensure points are awarded accurately according to each game's rules.</p>
            </div>

            <button type="button" class="button button-primary how-play-btn" id="btn-start-playing">
                Ready, Let's Play
            </button>
        </div>
    </section>
</div>

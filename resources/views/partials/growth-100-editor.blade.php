<div class="modal-backdrop" id="modal-growth-editor" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; overflow-y: auto;">
    <section aria-label="Editor BYC Growth 100" class="editor-panel" style="width: min(850px, 94vw); max-height: min(650px, 88vh); display: flex; flex-direction: column; background: var(--white); border-radius: 22px; box-shadow: var(--shadow); border: 1px solid var(--line); overflow: hidden; margin: auto;">
        <div class="modal-heading" style="padding: 20px 24px 16px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: var(--white);">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px;">Host Tools</span>
                <h2 style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 0;">BYC Growth 100 Question Editor</h2>
            </div>
            <button type="button" aria-label="Close editor" class="icon-button" id="btn-close-growth-editor">
                <x-icon name="x" />
            </button>
        </div>

        <div class="editor-body" style="display: grid; grid-template-columns: 210px 1fr; flex: 1; min-height: 0; overflow: hidden;">
            <nav class="round-list" id="growth-rounds-list" style="border-right: 1px solid var(--line); padding: 18px 14px; overflow-y: auto;">
                <span style="display: block; margin-bottom: 6px; font-size: 11px; text-transform: uppercase; font-weight: 800; color: var(--muted); letter-spacing: 0.05em;">Rounds List</span>
                <div id="growth-round-buttons" style="display: flex; flex-direction: column; gap: 6px;">
                    {{-- Populated via JS --}}
                </div>
                <button type="button" class="button button-primary" id="btn-add-growth-round" style="margin-top: 10px; width: 100%;">
                    Add Round
                </button>
            </nav>

            <form class="form-area" id="form-growth-round" style="padding: 20px 24px; overflow-y: auto; flex: 1; min-height: 0;">
                <input type="hidden" name="id" id="growth-round-id">

                <label style="display: block; margin-bottom: 16px; font-weight: 700; font-size: 13.5px; color: var(--ink);">
                    Survey Question
                    <textarea name="question" id="growth-round-question" rows="3" placeholder="Enter survey question..." required style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; font-weight: 600; font-size: 14px; margin-top: 6px; font-family: inherit; resize: vertical;"></textarea>
                </label>

                <div class="answer-editor-heading" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--line);">
                    <strong style="font-size: 13.5px; color: var(--ink);">Survey Answers List</strong>
                    <span class="validation-good" id="growth-score-total-indicator" style="font-size: 12px; font-weight: 800; padding: 3px 8px; border-radius: 6px; background: #eaf3dc; color: var(--forest-dark); border: 1px solid var(--lime);">Total: 100 / 100</span>
                </div>

                <div id="growth-answers-container" style="display: flex; flex-direction: column; gap: 8px;">
                    {{-- Dynamic answer rows: index, text input, score input, delete button --}}
                </div>

                <button type="button" class="button button-secondary button-sm" id="btn-add-answer-row" style="margin-top: 12px;">
                    Add Answer Choice
                </button>
                <small style="display: block; color: var(--muted); margin-top: 6px; font-size: 12px;">
                    * Answers are automatically sorted by highest score upon saving. Sum of answers must equal exactly 100.
                </small>
            </form>
        </div>

        <div class="modal-footer" style="padding: 14px 24px 18px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: var(--white);">
            <button type="button" class="button button-danger button-sm" id="btn-delete-growth-round">Delete Round</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="button button-secondary" id="btn-save-growth-round">Save Round</button>
                <button type="button" class="button button-primary" id="btn-save-batch-growth">Save All Questions (Batch)</button>
            </div>
        </div>
    </section>
</div>

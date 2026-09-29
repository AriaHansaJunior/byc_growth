<div class="modal-backdrop" id="modal-growth-editor" style="display: none;">
    <section aria-label="Editor BYC Growth 100" class="editor-panel">
        <div class="modal-heading">
            <div>
                <span class="eyebrow">Host Tools</span>
                <h2>BYC Growth 100 Question Editor</h2>
            </div>
            <button type="button" aria-label="Close editor" class="icon-button" id="btn-close-growth-editor">
                <x-icon name="x" />
            </button>
        </div>

        <div class="editor-body">
            <nav class="round-list" id="growth-rounds-list">
                <span>Rounds List</span>
                <div id="growth-round-buttons">
                    {{-- Populated via JS --}}
                </div>
                <button type="button" class="button button-primary" id="btn-add-growth-round" style="margin-top: 10px; width: 100%;">
                    + Add Round
                </button>
            </nav>

            <form class="form-area" id="form-growth-round">
                <input type="hidden" name="id" id="growth-round-id">

                <label>
                    Survey Question
                    <textarea name="question" id="growth-round-question" rows="3" placeholder="Enter survey question..." required></textarea>
                </label>

                <div class="answer-editor-heading">
                    <strong>Survey Answers List</strong>
                    <span class="validation-good" id="growth-score-total-indicator">Total: 100 / 100</span>
                </div>

                <div id="growth-answers-container">
                    {{-- Dynamic answer rows: index, text input, score input, delete button --}}
                </div>

                <button type="button" class="button button-secondary" id="btn-add-answer-row" style="margin-top: 12px;">
                    + Add Answer
                </button>
                <small style="display: block; color: var(--muted); margin-top: 6px;">
                    * Answers are automatically sorted by highest score upon saving. Sum of answers must equal exactly 100.
                </small>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button button-danger" id="btn-delete-growth-round">Delete Round</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="button button-secondary" id="btn-save-growth-round">Save Round</button>
                <button type="button" class="button button-primary" id="btn-save-batch-growth">Save All Questions (Batch)</button>
            </div>
        </div>
    </section>
</div>

<div class="modal-backdrop" id="modal-guess-editor" style="display: none;">
    <section aria-label="Guess Me Editor" class="editor-panel">
        <div class="modal-heading">
            <div>
                <span class="eyebrow">Host Tools</span>
                <h2>Guess Me! Question Editor</h2>
            </div>
            <button type="button" aria-label="Close editor" class="icon-button" id="btn-close-guess-editor">
                <x-icon name="x" />
            </button>
        </div>

        <div class="editor-body">
            <nav class="round-list" id="guess-rounds-list">
                <span>Rounds List</span>
                <div id="guess-round-buttons">
                    {{-- Populated via JS --}}
                </div>
                <button type="button" class="button button-primary" id="btn-add-guess-round" style="margin-top: 10px; width: 100%;">
                    Add Round
                </button>
            </nav>

            <form class="form-area" id="form-guess-round" enctype="multipart/form-data">
                <input type="hidden" name="id" id="guess-round-id">
                
                <label>
                    Upload / Replace Image
                    <input type="file" name="image" id="guess-round-image" accept="image/*" style="display: none;">
                    <div class="upload-box" id="guess-upload-box" role="button" tabindex="0" style="cursor: pointer;">
                        <span id="guess-upload-label">Click to select round image</span>
                        <small>JPG or PNG, maximum 5 MB</small>
                    </div>
                </label>

                <div class="form-row">
                    <label>
                        Correct Answer
                        <input type="text" name="correct_answer" id="guess-round-answer" placeholder="Example: GROOT" required>
                    </label>
                    <label>
                        Round Score
                        <input type="number" name="score" id="guess-round-score" value="20" min="1" required>
                    </label>
                </div>

                <label>
                    Clue
                    <input type="text" name="clue" id="guess-round-clue" placeholder="Example: G _ O _ T" required>
                    <small class="field-hint" id="guess-clue-hint" style="min-height: 20px;">
                        Use underscore _ for hidden characters. Clue length must match answer length.
                    </small>
                </label>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button button-danger" id="btn-delete-guess-round">Delete Round</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="button button-secondary" id="btn-save-guess-round">Save Round</button>
                <button type="button" class="button button-primary" id="btn-save-batch-guess">Save All Rounds (Batch)</button>
            </div>
        </div>
    </section>
</div>

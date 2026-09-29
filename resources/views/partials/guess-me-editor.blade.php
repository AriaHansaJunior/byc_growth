<div class="modal-backdrop" id="modal-guess-editor" style="display: none;">
    <section aria-label="Editor Guess Me!" class="editor-panel">
        <div class="modal-heading">
            <div>
                <span class="eyebrow">Host tools</span>
                <h2>Editor Guess Me!</h2>
            </div>
            <button type="button" aria-label="Tutup editor" class="icon-button" id="btn-close-guess-editor">
                <x-icon name="x" />
            </button>
        </div>

        <div class="editor-body">
            <nav class="round-list" id="guess-rounds-list">
                <span>Daftar ronde</span>
                <div id="guess-round-buttons">
                    {{-- Populated via JS --}}
                </div>
                <button type="button" class="button button-primary" id="btn-add-guess-round" style="margin-top: 10px; width: 100%;">
                    + Tambah ronde
                </button>
            </nav>

            <form class="form-area" id="form-guess-round" enctype="multipart/form-data">
                <input type="hidden" name="id" id="guess-round-id">
                
                <label>
                    Upload / Ganti Gambar
                    <input type="file" name="image" id="guess-round-image" accept="image/*" style="display: none;">
                    <div class="upload-box" id="guess-upload-box" role="button" tabindex="0" style="cursor: pointer;">
                        <span id="guess-upload-label">Klik untuk pilih gambar ronde</span>
                        <small>JPG atau PNG, maksimum 5 MB</small>
                    </div>
                </label>

                <div class="form-row">
                    <label>
                        Jawaban benar
                        <input type="text" name="correct_answer" id="guess-round-answer" placeholder="Contoh: GROOT" required>
                    </label>
                    <label>
                        Score ronde
                        <input type="number" name="score" id="guess-round-score" value="20" min="1" required>
                    </label>
                </div>

                <label>
                    Clue
                    <input type="text" name="clue" id="guess-round-clue" placeholder="Contoh: G _ O _ T" required>
                    <small class="field-hint" id="guess-clue-hint" style="min-height: 20px;">
                        Gunakan tanda _ untuk huruf tersembunyi.
                    </small>
                </label>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button button-danger" id="btn-delete-guess-round">Hapus ronde</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="button button-secondary" id="btn-cancel-guess-editor">Batal</button>
                <button type="button" class="button button-primary" id="btn-save-guess-round">Simpan perubahan</button>
            </div>
        </div>
    </section>
</div>

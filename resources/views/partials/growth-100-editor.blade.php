<div class="modal-backdrop" id="modal-growth-editor" style="display: none;">
    <section aria-label="Editor BYC Growth 100" class="editor-panel">
        <div class="modal-heading">
            <div>
                <span class="eyebrow">Host tools</span>
                <h2>Editor BYC Growth 100</h2>
            </div>
            <button type="button" aria-label="Tutup editor" class="icon-button" id="btn-close-growth-editor">
                <x-icon name="x" />
            </button>
        </div>

        <div class="editor-body">
            <nav class="round-list" id="growth-rounds-list">
                <span>Daftar ronde</span>
                <div id="growth-round-buttons">
                    {{-- Populated via JS --}}
                </div>
                <button type="button" class="button button-primary" id="btn-add-growth-round" style="margin-top: 10px; width: 100%;">
                    + Tambah ronde
                </button>
            </nav>

            <form class="form-area" id="form-growth-round">
                <input type="hidden" name="id" id="growth-round-id">

                <label>
                    Pertanyaan Survei
                    <textarea name="question" id="growth-round-question" rows="3" placeholder="Masukkan pertanyaan survei..." required></textarea>
                </label>

                <div class="answer-editor-heading">
                    <strong>Daftar Jawaban Survei</strong>
                    <span class="validation-good" id="growth-score-total-indicator">Total: 100 / 100</span>
                </div>

                <div id="growth-answers-container">
                    {{-- Dynamic answer rows: index, text input, score input, delete button --}}
                </div>

                <button type="button" class="button button-secondary" id="btn-add-answer-row" style="margin-top: 12px;">
                    + Tambah Jawaban
                </button>
                <small style="display: block; color: var(--muted); margin-top: 6px;">
                    * Urutan ranking 1 sampai terakhir akan diurutkan otomatis berdasarkan skor tertinggi saat disimpan.
                </small>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button button-danger" id="btn-delete-growth-round">Hapus ronde</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="button button-secondary" id="btn-cancel-growth-editor">Batal</button>
                <button type="button" class="button button-primary" id="btn-save-growth-round">Simpan perubahan</button>
            </div>
        </div>
    </section>
</div>

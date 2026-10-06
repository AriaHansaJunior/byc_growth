{{-- MODAL: GUESS ME ROUND (ADD / EDIT) --}}
<div class="modal-backdrop" id="modal-admin-guess-round" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; overflow-y: auto;" role="dialog" aria-modal="true" aria-labelledby="guess-modal-title">
    <section class="editor-panel" style="width: min(580px, 92vw); max-height: min(560px, 75vh); display: flex; flex-direction: column; background: var(--white); border-radius: 22px; box-shadow: var(--shadow); border: 1px solid var(--line); overflow: hidden; margin: auto;">
        {{-- Fixed Header --}}
        <div class="modal-heading" style="padding: 24px 28px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: var(--white);">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px;">Guess Me! Management</span>
                <h2 id="guess-modal-title" style="font: 800 22px 'Manrope', sans-serif; color: var(--ink); margin: 0;">Add Guess Me Round</h2>
            </div>
            <button type="button" aria-label="Close dialog" class="icon-button" id="btn-close-guess-modal">
                <x-icon name="x" />
            </button>
        </div>

        <form action="{{ route('admin.games.guess-me.save-round') }}" method="POST" enctype="multipart/form-data" id="form-admin-guess-round" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; margin: 0;">
            @csrf
            <input type="hidden" name="id" id="guess-form-id">

            {{-- Scrollable Form Body --}}
            <div class="modal-scroll-body" style="padding: 24px 28px; overflow-y: auto; flex: 1; min-height: 0;">
                {{-- Visual Clue Image with 4:3 Crop Studio --}}
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 8px;">
                        Visual Clue Image (4:3)
                    </label>

                    {{-- Existing Image Preview (For edit mode before replacing) --}}
                    <div id="guess-existing-preview-wrap" style="display: none; margin-bottom: 12px; text-align: center;">
                        <div style="width: 280px; height: 210px; aspect-ratio: 4 / 3; margin: 0 auto; border-radius: 14px; overflow: hidden; border: 1px solid var(--line); background: var(--paper); box-shadow: 0 4px 14px rgba(0,0,0,0.06); position: relative;">
                            <img id="guess-existing-img" src="" alt="Current clue image" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.65); color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 6px; font-weight: 600;">Current Clue Image</span>
                        </div>
                    </div>

                    {{-- File Dropzone --}}
                    <div id="guess-dropzone" class="crop-dropzone" style="padding: 20px 16px; border: 2px dashed var(--line); border-radius: 14px; text-align: center; background: var(--paper); cursor: pointer;">
                        <div class="crop-dropzone-icon" style="font-size: 28px; margin-bottom: 6px;">📷</div>
                        <div style="font-weight: 700; color: var(--forest); font-size: 13.5px; margin-bottom: 4px;" id="guess-dropzone-title">
                            Click or drag image to upload or replace
                        </div>
                        <small style="color: var(--muted); font-size: 11.5px; display: block;">
                            Supported formats: JPG, PNG, WEBP, GIF (Max: 5 MB)
                        </small>
                    </div>
                    <input type="file" name="image" id="guess-image-input" accept="image/jpeg,image/png,image/webp,image/jpg,image/gif" style="display: none;">

                    {{-- Selected File Info Bar (Hidden until selected) --}}
                    <div id="guess-crop-file-info" style="display: none; align-items: center; justify-content: space-between; background: var(--paper); border: 1px solid var(--line); border-radius: 10px; padding: 8px 12px; margin: 10px 0;">
                        <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                            <span>📷</span>
                            <span id="guess-crop-file-name" style="font-weight: 700; font-size: 12.5px; color: var(--ink); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">image.jpg</span>
                            <span id="guess-crop-file-size" style="font-size: 11.5px; color: var(--muted); white-space: nowrap;">(1.2 MB)</span>
                        </div>
                        <button type="button" id="btn-guess-change-photo" class="button button-ghost button-sm" style="padding: 3px 8px; font-size: 11.5px; height: 26px;">
                            Change
                        </button>
                    </div>

                    {{-- 4:3 Crop Studio --}}
                    <div id="guess-crop-studio" class="crop-studio-wrap" style="display: none; padding: 14px; border-radius: 16px; margin-top: 10px;">
                        <div id="guess-crop-viewport" class="crop-viewport-container">
                            <img id="guess-crop-image-element" class="crop-viewport-image" alt="Crop preview" src="">
                            <div class="crop-grid-overlay">
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                            </div>
                            <span class="crop-badge-overlay">4:3 Aspect Ratio</span>
                        </div>

                        <div class="crop-controls-bar">
                            <div class="crop-zoom-bar">
                                <span id="guess-crop-zoom-label" style="white-space: nowrap; font-size: 12px;">Zoom: 1.0x</span>
                                <input type="range" id="guess-crop-zoom-range" min="1" max="2.5" step="0.05" value="1">
                                <button type="button" id="btn-guess-reset-crop" class="crop-preset-btn" style="padding: 3px 8px; font-size: 11px;">Reset</button>
                            </div>
                            <div class="crop-preset-group">
                                <button type="button" class="crop-preset-btn guess-preset-btn" data-align="center">Center</button>
                                <button type="button" class="crop-preset-btn guess-preset-btn" data-align="top">Top</button>
                                <button type="button" class="crop-preset-btn guess-preset-btn" data-align="bottom">Bottom</button>
                                <button type="button" class="crop-preset-btn guess-preset-btn" data-align="left">Left</button>
                                <button type="button" class="crop-preset-btn guess-preset-btn" data-align="right">Right</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Correct Answer --}}
                <div style="margin-bottom: 16px;">
                    <label for="guess-input-answer" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                        Correct Answer <span style="color: var(--red);">*</span>
                    </label>
                    <input type="text" name="correct_answer" id="guess-input-answer" placeholder="Example: GROOT" required style="width: 100%; height: 44px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px; font-weight: 700; text-transform: uppercase;">
                    <small style="color: var(--muted); font-size: 12px; display: block; margin-top: 4px;">Automatically normalized to uppercase.</small>
                </div>

                {{-- Clue (Letter Slots) & Score --}}
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label for="guess-input-clue" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                            Clue (Letter Slots) <span style="color: var(--red);">*</span>
                        </label>
                        <input type="text" name="clue" id="guess-input-clue" placeholder="Example: G _ O _ T" required style="width: 100%; height: 44px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: monospace; font-weight: 700; text-transform: uppercase;">
                    </div>
                    <div>
                        <label for="guess-input-score" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                            Points <span style="color: var(--red);">*</span>
                        </label>
                        <input type="number" name="score" id="guess-input-score" value="20" min="1" required style="width: 100%; height: 44px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px; font-weight: 700;">
                    </div>
                </div>

                <div id="guess-clue-validation-feedback" style="min-height: 22px; font-size: 12px; color: var(--muted); margin-bottom: 8px;">
                    Use underscore `_` for hidden characters. Clue character length must match the answer length.
                </div>
            </div>

            {{-- Fixed Footer --}}
            <div class="modal-footer" style="padding: 16px 28px 20px; border-top: 1px solid var(--line); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; background: var(--white);">
                <button type="button" class="button button-ghost" id="btn-cancel-guess-modal">
                    Cancel
                </button>
                <button type="submit" class="button button-primary" id="btn-submit-guess-modal">
                    Save Round
                </button>
            </div>
        </form>
    </section>
</div>

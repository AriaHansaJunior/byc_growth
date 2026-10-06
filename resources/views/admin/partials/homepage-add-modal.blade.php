{{-- Add Slideshow Photo Modal --}}
<div id="modal-add-slide" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-add-slide-title">
    <div class="info-modal" style="width: min(480px, 94vw); max-height: calc(100vh - 40px); overflow: hidden; padding: 20px 24px; background: var(--white); border-radius: 20px; position: relative; box-shadow: var(--shadow);">
        <button type="button" class="icon-button btn-close-modal" id="btn-close-add-slide" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;">
            <x-icon name="x" />
        </button>
        <div style="margin-bottom: 12px; padding-right: 32px;">
            <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Homepage Slideshow</span>
            <h3 id="modal-add-slide-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Add Slideshow Photo</h3>
        </div>

        <form method="POST" action="{{ route('admin.homepage.slides.store') }}" enctype="multipart/form-data" id="form-add-slide">
            @csrf

            {{-- Photo Picker Area --}}
            <div class="form-group" style="margin-bottom: 10px;">
                <label class="form-label" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 6px;">
                    Photo Image <span style="color: var(--red);">*</span>
                </label>

                {{-- Dropzone / File Picker --}}
                <div id="crop-dropzone" class="crop-dropzone" style="padding: 24px 16px;">
                    <div class="crop-dropzone-icon">📷</div>
                    <div style="font-weight: 700; color: var(--ink); font-size: 14px; margin-bottom: 4px;">
                        Click to select photo or drag here
                    </div>
                    <small style="color: var(--muted); font-size: 12px; display: block;">
                        Supports JPEG, PNG, WEBP, GIF up to 5MB.
                    </small>
                </div>

                <input type="file" id="add-slide-image" name="image" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" required style="display: none;">
            </div>

            {{-- Selected File Info Bar (Hidden until selected) --}}
            <div id="crop-file-info" style="display: none; align-items: center; justify-content: space-between; background: var(--paper); border: 1px solid var(--line); border-radius: 10px; padding: 8px 12px; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                    <span>📷</span>
                    <span id="crop-file-name" style="font-weight: 700; font-size: 12.5px; color: var(--ink); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">photo.jpg</span>
                    <span id="crop-file-size" style="font-size: 11.5px; color: var(--muted); white-space: nowrap;">(1.2 MB)</span>
                </div>
                <button type="button" id="btn-change-photo" class="button button-ghost button-sm" style="padding: 3px 8px; font-size: 11.5px; height: 26px;">
                    Change
                </button>
            </div>

            {{-- 1:1 Live Preview & Crop Adjustment Studio (Hidden until selected) --}}
            <div id="crop-studio" class="crop-studio-wrap" style="display: none; padding: 14px; border-radius: 16px;">
                {{-- 1:1 Viewport Frame --}}
                <div id="crop-viewport" class="crop-viewport-container">
                    <img id="crop-image-element" class="crop-viewport-image" alt="Crop preview" src="">
                    <div class="crop-grid-overlay">
                        <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                        <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                        <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                    </div>
                </div>

                {{-- Zoom Controls --}}
                <div class="crop-controls-bar">
                    <div class="crop-zoom-bar">
                        <span id="crop-zoom-label" style="white-space: nowrap; font-size: 12px;">Zoom: 1.0x</span>
                        <input type="range" id="crop-zoom-range" min="1" max="2.5" step="0.05" value="1">
                        <button type="button" id="btn-reset-crop" class="crop-preset-btn" style="padding: 3px 8px; font-size: 11px;">Reset</button>
                    </div>
                </div>
            </div>

            {{-- Modal Action Buttons --}}
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px;">
                <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                <button type="submit" class="button button-primary button-sm" id="btn-submit-add-slide">
                    Upload Photo
                </button>
            </div>
        </form>
    </div>
</div>

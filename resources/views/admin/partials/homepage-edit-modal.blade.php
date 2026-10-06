{{-- Edit Slideshow Photo Position Modal --}}
<div id="modal-edit-slide" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-edit-slide-title">
    <div class="info-modal" style="width: min(480px, 94vw); max-height: calc(100vh - 40px); overflow-y: auto; padding: 20px 24px; background: var(--white); border-radius: 20px; position: relative; box-shadow: var(--shadow);">
        <button type="button" class="icon-button btn-close-modal" id="btn-close-edit-slide" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;">
            <x-icon name="x" />
        </button>
        <div style="margin-bottom: 14px; padding-right: 32px;">
            <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Homepage Slideshow</span>
            <h3 id="modal-edit-slide-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Edit Photo</h3>
            <p id="edit-slide-filename-display" style="color: var(--muted); font-size: 12.5px; margin: 3px 0 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                Adjust position and zoom
            </p>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="form-edit-slide">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-slide-title" name="title" value="">
            <input type="file" id="edit-slide-image-input" name="image" style="display: none;">

            {{-- Photo Viewport & Reposition Studio --}}
            <div class="crop-studio-wrap" style="padding: 14px; border-radius: 16px; margin-bottom: 12px; background: var(--paper); border: 1px solid var(--line);">
                <div id="edit-crop-viewport" class="crop-viewport-container" style="position: relative; width: 320px; height: 240px; margin: 0 auto 10px; border-radius: 16px; overflow: hidden; border: 2px solid var(--forest); background: #141f17; cursor: grab; user-select: none; touch-action: none;">
                    <img id="edit-crop-image-element" class="crop-viewport-image" alt="Edit Photo Preview" src="" style="position: absolute; top: 0; left: 0; pointer-events: none; will-change: transform, left, top, width, height;">
                    <div class="crop-grid-overlay" style="position: absolute; inset: 0; pointer-events: none; display: grid; grid-template-columns: 1fr 1fr 1fr; grid-template-rows: 1fr 1fr 1fr; opacity: 0.25;">
                        <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                        <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                        <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                    </div>
                    <div id="edit-loading-overlay" style="display: none; position: absolute; inset: 0; background: rgba(20, 31, 23, 0.75); color: #fff; font-size: 13px; font-weight: 700; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
                        <span>Loading photo...</span>
                    </div>
                </div>

                <p style="margin: 0 0 10px; font-size: 11.5px; color: var(--muted); text-align: center;">
                    Drag photo to adjust position • Scroll wheel or slider to zoom
                </p>

                {{-- Simple Zoom Slider --}}
                <div class="crop-controls-bar" style="max-width: 320px; margin: 0 auto;">
                    <div class="crop-zoom-bar" style="display: flex; align-items: center; gap: 10px; background: var(--white); border: 1px solid var(--line); border-radius: 10px; padding: 6px 14px;">
                        <span id="edit-crop-zoom-label" style="white-space: nowrap; font-size: 12px; font-weight: 700; color: var(--ink);">Zoom: 1.0x</span>
                        <input type="range" id="edit-crop-zoom-range" min="1" max="3" step="0.05" value="1" style="flex: 1; accent-color: var(--forest); cursor: pointer;">
                    </div>
                </div>
            </div>

            {{-- Modal Action Buttons --}}
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px;">
                <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                <button type="submit" class="button button-primary button-sm" id="btn-submit-edit-slide">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

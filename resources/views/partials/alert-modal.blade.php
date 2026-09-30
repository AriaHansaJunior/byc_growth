<div id="byc-alert-modal" class="modal-backdrop byc-alert-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="byc-alert-title">
    <div class="info-modal byc-alert-card">
        {{-- Close Icon in Top Corner --}}
        <button type="button" aria-label="Close dialog" class="icon-button byc-alert-btn-close" style="position: absolute; top: 16px; right: 16px; width: 34px; height: 34px;">
            <x-icon name="x" />
        </button>

        {{-- Earth Tone Badge Icon --}}
        <div class="byc-alert-icon-wrap">
            <span id="byc-alert-icon-symbol">🔒</span>
        </div>

        {{-- Eyebrow --}}
        <span class="eyebrow" id="byc-alert-eyebrow" style="color: var(--forest); font-size: 11px; margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: 0.12em;">BYC Growth Notice</span>

        {{-- Title --}}
        <h3 id="byc-alert-title" style="font: 800 22px 'Manrope', sans-serif; letter-spacing: -.03em; color: var(--ink); margin: 0 0 10px;">Admin Account Required</h3>

        {{-- Message --}}
        <p id="byc-alert-message" style="color: var(--muted); font-size: 14.5px; line-height: 1.6; margin: 0 0 24px;">
            You must login as an admin account to input score.
        </p>

        {{-- Actions --}}
        <div style="display: flex; justify-content: center; gap: 12px;">
            <button type="button" class="button button-primary byc-alert-btn-ok" style="min-width: 140px; padding: 11px 28px; border-radius: 99px; font-weight: 700; font-size: 14px;">
                OK
            </button>
        </div>
    </div>
</div>

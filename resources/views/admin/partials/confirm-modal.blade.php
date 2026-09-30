<div id="admin-confirm-modal" class="modal-backdrop admin-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title">
    <div class="info-modal admin-confirm-card" style="width: min(460px, 92vw); padding: 32px 28px; text-align: center; border-radius: 24px; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow); position: relative;">
        {{-- Close Icon --}}
        <button type="button" aria-label="Close dialog" class="icon-button admin-confirm-close-btn" style="position: absolute; top: 16px; right: 16px; width: 34px; height: 34px;">
            <x-icon name="x" />
        </button>

        {{-- Warning Icon Badge --}}
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #fdf0ee; color: var(--red); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 16px;">
            ⚠️
        </div>

        {{-- Eyebrow --}}
        <span class="eyebrow" id="admin-confirm-eyebrow" style="color: var(--red); font-size: 11px; margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: 0.12em;">Confirmation Required</span>

        {{-- Title --}}
        <h3 id="admin-confirm-title" style="font: 800 22px 'Manrope', sans-serif; letter-spacing: -.03em; color: var(--ink); margin: 0 0 10px;">Are you sure?</h3>

        {{-- Message --}}
        <p id="admin-confirm-message" style="color: var(--muted); font-size: 14.5px; line-height: 1.6; margin: 0 0 24px;">
            This action cannot be undone. Are you sure you want to proceed?
        </p>

        {{-- Actions --}}
        <div style="display: flex; justify-content: center; gap: 12px;">
            <button type="button" class="button button-ghost button-sm admin-confirm-cancel-btn" style="min-width: 100px;">
                Cancel
            </button>
            <form id="admin-confirm-form" method="POST" action="" style="margin: 0; display: inline-block;">
                @csrf
                <input type="hidden" name="_method" id="admin-confirm-method" value="DELETE">
                <button type="submit" class="button button-danger button-sm" id="admin-confirm-submit-btn" style="min-width: 120px;">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>

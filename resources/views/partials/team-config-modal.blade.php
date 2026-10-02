<div class="modal-backdrop" id="modal-team-config" style="display: none;">
    <section aria-label="Team Configuration" class="info-modal" style="max-width: 580px; padding: 32px;">
        <div class="modal-heading" style="margin-bottom: 24px;">
            <div>
                <span class="eyebrow">Universal Game System</span>
                <h2>Configure Teams</h2>
            </div>
            <button type="button" aria-label="Close configuration" class="icon-button" id="btn-close-team-config">
                <x-icon name="x" />
            </button>
        </div>

        <p style="color: var(--muted); font-size: 14px; line-height: 1.5; margin-bottom: 20px;">
            Configure competing fellowship teams. You can set 2, 3, 4, or more teams, customize names, and assign distinct colors.
        </p>

        {{-- Preset Buttons --}}
        <div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center;">
            <span style="font-size: 13px; font-weight: 700; color: var(--muted); margin-right: 4px;">Quick Presets:</span>
            <button type="button" class="button button-ghost btn-preset-teams" data-count="2" style="padding: 6px 14px; font-size: 13px;">2 Teams</button>
            <button type="button" class="button button-ghost btn-preset-teams" data-count="3" style="padding: 6px 14px; font-size: 13px;">3 Teams</button>
            <button type="button" class="button button-ghost btn-preset-teams" data-count="4" style="padding: 6px 14px; font-size: 13px;">4 Teams</button>
        </div>

        {{-- Dynamic Team Rows Form --}}
        <form id="form-team-config">
            <div id="team-rows-container" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; max-height: 48vh; overflow-y: auto; padding-right: 4px;">
                {{-- Populated dynamically via JS --}}
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <button type="button" class="button button-secondary" id="btn-add-team-row">
                    Add Team
                </button>
                <small id="team-config-status" style="color: var(--muted); font-size: 13px;">Min. 2 teams required</small>
            </div>

            <div class="modal-footer" style="padding: 0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="button button-secondary" id="btn-cancel-team-config">Cancel</button>
                <button type="submit" class="button button-primary" id="btn-save-teams">Save Changes</button>
            </div>
        </form>
    </section>
</div>

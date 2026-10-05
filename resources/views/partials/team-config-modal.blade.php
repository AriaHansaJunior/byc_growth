<div class="modal-backdrop" id="modal-team-config" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px;" role="dialog" aria-modal="true" aria-labelledby="team-config-modal-title">
    <section class="editor-panel" style="width: min(620px, 94vw); max-height: calc(100vh - 40px); display: flex; flex-direction: column; background: var(--white); border-radius: 24px; box-shadow: var(--shadow); border: 1px solid var(--line); overflow: hidden; margin: auto;">
        {{-- Fixed Header --}}
        <div class="modal-heading" style="padding: 22px 28px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: var(--white);">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px;">Universal Game System</span>
                <h2 id="team-config-modal-title" style="font: 800 22px 'Manrope', sans-serif; color: var(--ink); margin: 0;">Configure Teams</h2>
            </div>
            <button type="button" aria-label="Close configuration" class="icon-button" id="btn-close-team-config">
                <x-icon name="x" />
            </button>
        </div>

        {{-- Form wrapper (Flex container with scrollable body and pinned footer) --}}
        <form id="form-team-config" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; margin: 0;">
            {{-- Scrollable Form Body --}}
            <div class="modal-scroll-body" style="padding: 22px 28px; overflow-y: auto; flex: 1; min-height: 0;">
                <p style="color: var(--muted); font-size: 13.5px; line-height: 1.5; margin: 0 0 16px 0;">
                    Configure competing fellowship teams. You can set 2, 3, 4, or more teams, customize names, and assign distinct colors.
                </p>

                {{-- Preset Buttons --}}
                <div style="display: flex; gap: 8px; margin-bottom: 18px; align-items: center; flex-wrap: wrap;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--muted); margin-right: 4px;">Quick Presets:</span>
                    <button type="button" class="button button-ghost btn-preset-teams" data-count="2" style="padding: 6px 14px; font-size: 13px;">2 Teams</button>
                    <button type="button" class="button button-ghost btn-preset-teams" data-count="3" style="padding: 6px 14px; font-size: 13px;">3 Teams</button>
                    <button type="button" class="button button-ghost btn-preset-teams" data-count="4" style="padding: 6px 14px; font-size: 13px;">4 Teams</button>
                </div>

                {{-- Dynamic Team Rows --}}
                <div id="team-rows-container" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                    {{-- Populated dynamically via JS --}}
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; margin-bottom: 6px;">
                    <button type="button" class="button button-secondary button-sm" id="btn-add-team-row">
                        Add Team
                    </button>
                    <small id="team-config-status" style="color: var(--muted); font-size: 12.5px;">Min. 2 teams required</small>
                </div>
            </div>

            {{-- Fixed Footer Pinned at Bottom --}}
            <div class="modal-footer" style="padding: 16px 28px 20px; border-top: 1px solid var(--line); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; background: var(--white);">
                <button type="button" class="button button-ghost" id="btn-cancel-team-config">Cancel</button>
                <button type="submit" class="button button-primary" id="btn-save-teams">Save Changes</button>
            </div>
        </form>
    </section>
</div>

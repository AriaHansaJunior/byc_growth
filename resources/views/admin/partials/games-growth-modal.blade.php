{{-- MODAL: BYC GROWTH 100 QUESTION (ADD / EDIT) --}}
<div class="modal-backdrop" id="modal-admin-growth-round" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; overflow-y: auto;" role="dialog" aria-modal="true" aria-labelledby="growth-modal-title">
    <section class="editor-panel" style="width: min(580px, 92vw); max-height: min(560px, 75vh); display: flex; flex-direction: column; background: var(--white); border-radius: 22px; box-shadow: var(--shadow); border: 1px solid var(--line); overflow: hidden; margin: auto;">
        {{-- Fixed Header --}}
        <div class="modal-heading" style="padding: 24px 28px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: var(--white);">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px;">BYC GROWTH 100 Management</span>
                <h2 id="growth-modal-title" style="font: 800 22px 'Manrope', sans-serif; color: var(--ink); margin: 0;">Add Survey Question</h2>
            </div>
            <button type="button" aria-label="Close dialog" class="icon-button" id="btn-close-growth-modal">
                <x-icon name="x" />
            </button>
        </div>

        <form action="{{ route('admin.games.growth-100.save-round') }}" method="POST" id="form-admin-growth-round" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; margin: 0;">
            @csrf
            <input type="hidden" name="id" id="growth-form-id">

            {{-- Scrollable Form Body --}}
            <div class="modal-scroll-body" style="padding: 24px 28px; overflow-y: auto; overflow-x: hidden; flex: 1; min-height: 0;">
                {{-- Question Text --}}
                <div style="margin-bottom: 20px;">
                    <label for="growth-input-question" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                        Survey Question <span style="color: var(--red);">*</span>
                    </label>
                    <textarea name="question" id="growth-input-question" rows="3" placeholder="Enter survey question..." required style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; font-weight: 600; font-size: 14px; line-height: 1.5; font-family: inherit; resize: vertical;"></textarea>
                </div>

                {{-- Answers Header & Live Sum Indicator --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--line);">
                    <strong style="font-size: 14px; color: var(--ink);">Survey Answers</strong>
                    <span id="growth-modal-total-indicator" style="font-size: 12.5px; font-weight: 800; padding: 4px 10px; border-radius: 6px; background: #eaf3dc; color: var(--forest-dark); border: 1px solid var(--lime);">
                        Total: 100 / 100 PTS
                    </span>
                </div>

                {{-- Answers Dynamic Container --}}
                <div id="growth-answers-dynamic-list" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; width: 100%; overflow-x: hidden;">
                    {{-- Populated via JS --}}
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                    <button type="button" class="button button-secondary button-sm" id="btn-growth-add-answer-row">
                        Add Answer Choice
                    </button>
                    <small style="color: var(--muted); font-size: 12px;">
                        * Total sum of answer points must equal <strong>exactly 100</strong>.
                    </small>
                </div>
            </div>

            {{-- Fixed Footer --}}
            <div class="modal-footer" style="padding: 16px 28px 20px; border-top: 1px solid var(--line); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; background: var(--white);">
                <button type="button" class="button button-ghost" id="btn-cancel-growth-modal">
                    Cancel
                </button>
                <button type="submit" class="button button-primary" id="btn-submit-growth-modal">
                    Save Question
                </button>
            </div>
        </form>
    </section>
</div>

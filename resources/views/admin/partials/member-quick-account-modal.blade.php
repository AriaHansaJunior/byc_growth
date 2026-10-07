{{-- Quick Create User Account Modal (Launched from Member Add/Edit) --}}
<div id="modal-quick-create-user" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 10050; align-items: center; justify-content: center; padding: 16px; overscroll-behavior: contain;" role="dialog" aria-modal="true" aria-labelledby="modal-quick-create-user-title">
    <div class="info-modal" style="width: min(480px, 92vw); max-height: calc(100vh - 48px); display: flex; flex-direction: column; background: var(--white); border-radius: 20px; overflow: hidden; box-shadow: var(--shadow); position: relative; border: 1px solid var(--line);">
        {{-- Header --}}
        <div style="padding: 16px 20px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px;">👤</span>
                <div>
                    <h3 id="modal-quick-create-user-title" style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 800; color: var(--ink);">
                        Create New User Account
                    </h3>
                    <small style="color: var(--muted); font-size: 11px;">Create account and immediately link to this member</small>
                </div>
            </div>
            <button type="button" class="btn-close-quick-user" aria-label="Close dialog" style="width: 30px; height: 30px; border-radius: 50%; background: var(--white); border: 1px solid var(--line); display: grid; place-items: center; font-size: 16px; font-weight: 700; color: var(--muted); cursor: pointer; flex-shrink: 0; line-height: 1;">&times;</button>
        </div>

        {{-- Form Body --}}
        <form id="form-quick-create-user" method="POST" action="{{ route('admin.roles.store') }}" novalidate style="display: flex; flex-direction: column; flex: 1; min-height: 0; margin: 0;">
            @csrf
            <div style="padding: 18px 20px; overflow-y: auto; flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 12px;">
                <div id="quick-user-alert-error" class="alert-box-error" style="display: none; font-size: 12px; padding: 8px 12px; border-radius: 8px; margin-bottom: 4px; color: var(--red); background: #fdf0ee; border: 1px solid var(--red);"></div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="quick-user-email" style="display: block; font-weight: 700; font-size: 12px; color: var(--ink); margin-bottom: 4px;">Email Address *</label>
                    <input
                        type="email"
                        name="email"
                        id="quick-user-email"
                        class="form-input"
                        placeholder="e.g. member@bycgrowth.org"
                        required
                        style="height: 40px; min-height: 40px; padding: 8px 12px; font-size: 13.5px; border-radius: 8px; width: 100%; box-sizing: border-box;"
                    >
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="quick-user-username" style="display: block; font-weight: 700; font-size: 12px; color: var(--ink); margin-bottom: 4px;">Username *</label>
                    <input
                        type="text"
                        name="username"
                        id="quick-user-username"
                        class="form-input"
                        placeholder="e.g. john_doe"
                        maxlength="60"
                        required
                        style="height: 40px; min-height: 40px; padding: 8px 12px; font-size: 13.5px; border-radius: 8px; width: 100%; box-sizing: border-box;"
                    >
                    <small style="color: var(--muted); font-size: 10.5px; display: block; margin-top: 2px;">Letters, numbers, and underscores only.</small>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="quick-user-name" style="display: block; font-weight: 700; font-size: 12px; color: var(--ink); margin-bottom: 4px;">Full Name</label>
                    <input
                        type="text"
                        name="name"
                        id="quick-user-name"
                        class="form-input"
                        placeholder="e.g. Jonathan Wijaya"
                        style="height: 40px; min-height: 40px; padding: 8px 12px; font-size: 13.5px; border-radius: 8px; width: 100%; box-sizing: border-box;"
                    >
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="quick-user-password" style="display: block; font-weight: 700; font-size: 12px; color: var(--ink); margin-bottom: 4px;">Password *</label>
                    <input
                        type="password"
                        name="password"
                        id="quick-user-password"
                        class="form-input"
                        placeholder="Minimum 6 characters (default: password123)"
                        value="password123"
                        required
                        style="height: 40px; min-height: 40px; padding: 8px 12px; font-size: 13.5px; border-radius: 8px; width: 100%; box-sizing: border-box;"
                    >
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="quick-user-role" style="display: block; font-weight: 700; font-size: 12px; color: var(--ink); margin-bottom: 4px;">Access Role *</label>
                    <select name="role" id="quick-user-role" class="form-input" style="height: 40px; min-height: 40px; padding: 8px 12px; font-size: 13.5px; border-radius: 8px; width: 100%; box-sizing: border-box;">
                        <option value="user" selected>User (Standard Fellowship Member)</option>
                        <option value="admin">Admin (Administrator)</option>
                    </select>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding: 12px 20px; background: var(--paper); border-top: 1px solid var(--line); display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-shrink: 0;">
                <button type="button" class="button button-ghost button-sm btn-close-quick-user" style="padding: 6px 14px; font-size: 12.5px;">
                    Cancel
                </button>
                <button type="submit" class="button button-primary button-sm" id="btn-submit-quick-user" style="min-width: 130px; padding: 6px 16px; font-size: 12.5px;">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

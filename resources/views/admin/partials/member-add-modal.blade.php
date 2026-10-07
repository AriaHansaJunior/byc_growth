{{-- Add Member Modal --}}
<div id="modal-add-member" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 10000; align-items: center; justify-content: center; padding: 16px; overscroll-behavior: contain;" role="dialog" aria-modal="true" aria-labelledby="modal-add-member-title">
    <div class="info-modal member-modal-card" style="width: min(600px, 94vw); max-height: calc(100vh - 40px); display: flex; flex-direction: column; background: var(--white); border-radius: 22px; overflow: hidden; position: relative; box-shadow: var(--shadow-lg); border: 1px solid var(--line);">
        {{-- Header --}}
        <div style="padding: 18px 24px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700;">Community Directory</span>
                <h3 id="modal-add-member-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Add New Member</h3>
            </div>
            <button type="button" class="btn-close-modal" id="btn-close-add-member" aria-label="Close dialog" style="width: 32px; height: 32px; border-radius: 50%; background: var(--white); border: 1px solid var(--line); display: grid; place-items: center; font-size: 18px; font-weight: 700; color: var(--muted); cursor: pointer; flex-shrink: 0; line-height: 1;">&times;</button>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data" id="form-add-member" novalidate style="display: flex; flex-direction: column; flex: 1; min-height: 0; margin: 0; overflow: hidden;">
            @csrf

            <div style="padding: 20px 24px; overflow-y: auto; flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 16px;">
                {{-- Profile Photo Section --}}
                <div style="text-align: center;">
                    <label class="form-label" style="font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink); display: block; margin-bottom: 10px; text-align: center;">
                        Profile Photo
                    </label>

                    {{-- Empty Dropzone --}}
                    <div id="add-member-dropzone" class="crop-dropzone" style="height: 200px; width: 200px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px; border-radius: 50%; cursor: pointer;">
                        <div class="crop-dropzone-icon" style="font-size: 34px; margin-bottom: 4px;">👤</div>
                        <div style="font-weight: 700; color: var(--ink); font-size: 13.5px; margin-bottom: 2px;">Choose Photo</div>
                        <small style="color: var(--muted); font-size: 10.5px; display: block; line-height: 1.3;">Click or drag photo<br>Any size supported</small>
                    </div>

                    <input type="file" id="add-photo" name="photo" accept="image/*" style="display: none;">

                    {{-- Live Large Preview Studio --}}
                    <div id="add-member-studio" style="display: none;">
                        <div class="crop-viewport-container member-crop-viewport" id="add-member-viewport" style="width: 200px; height: 200px; margin: 0 auto 10px; border-radius: 50%;">
                            <img id="add-member-preview-img" class="crop-viewport-image" alt="Member Photo Preview" src="">
                            <div class="crop-grid-overlay" style="border-radius: 50%;">
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; max-width: 220px; margin: 0 auto;">
                            <div class="crop-zoom-bar" style="flex: 1; padding: 4px 8px; font-size: 11px; border-radius: 8px;">
                                <span id="add-member-zoom-label" style="white-space: nowrap; font-size: 11px;">1.0x</span>
                                <input type="range" id="add-member-zoom-range" min="1" max="2.5" step="0.05" value="1" style="height: 4px;">
                                <button type="button" id="add-member-btn-reset" class="crop-preset-btn" style="padding: 2px 6px; font-size: 10px;">Reset</button>
                            </div>
                            <button type="button" id="add-member-btn-change" class="button button-ghost button-sm" style="padding: 3px 8px; font-size: 11px; height: 28px;">
                                Change
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Full Name --}}
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="add-full-name" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                        Full Name <span style="color: var(--red);">*</span>
                    </label>
                    <input type="text" id="add-full-name" name="full_name" class="form-input" required placeholder="e.g. Jonathan Wijaya" style="height: 44px; min-height: 44px; padding: 10px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; width: 100%; box-sizing: border-box;">
                </div>

                {{-- Date of Birth --}}
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="add-dob" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                        Date of Birth
                    </label>
                    <input type="date" id="add-dob" name="date_of_birth" max="{{ date('Y-m-d') }}" class="form-input" style="height: 44px; min-height: 44px; padding: 10px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; width: 100%; box-sizing: border-box;">
                    <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Used for community birthday celebrations.</small>
                </div>

                {{-- User Account --}}
                <div class="form-group" style="margin: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 4px;">
                        <label class="form-label" for="add-user-id" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); margin: 0;">
                            User Account
                        </label>
                        <button type="button" class="btn-trigger-quick-account" data-target="add" style="background: none; border: none; padding: 0; color: var(--forest); font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: underline; font-family: inherit;">
                            Create New Account
                        </button>
                    </div>
                    <select id="add-user-id" name="user_id" class="form-input" style="height: 44px; min-height: 44px; padding: 10px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; width: 100%; box-sizing: border-box;">
                        <option value="">None (Unlinked)</option>
                        <option value="__new__">Create New Account</option>
                        @if(isset($users) && $users->isNotEmpty())
                            <optgroup label="Existing Accounts">
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->email }} ({{ $u->username }}){{ $u->member_id ? ' [Linked]' : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Link this member to a user account so they can log in and view their birthday wishes.</small>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding: 14px 24px; background: var(--paper); border-top: 1px solid var(--line); display: flex; gap: 10px; justify-content: flex-end; align-items: center; flex-shrink: 0;">
                <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                <button type="submit" class="button button-primary button-sm" id="btn-submit-add-member" style="min-width: 120px;">
                    Save Member
                </button>
            </div>
        </form>
    </div>
</div>

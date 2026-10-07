{{-- Edit Member Modal --}}
<div id="modal-edit-member" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 10000; align-items: center; justify-content: center; padding: 16px; overscroll-behavior: contain;" role="dialog" aria-modal="true" aria-labelledby="modal-edit-member-title">
    <div class="info-modal member-modal-card" style="width: min(840px, 95vw); min-height: 590px; max-height: calc(100vh - 36px); display: flex; flex-direction: column; background: var(--white); border-radius: 22px; overflow: hidden; position: relative; box-shadow: var(--shadow-lg); border: 1px solid var(--line);">
        {{-- Header --}}
        <div style="padding: 18px 26px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700;">Community Directory</span>
                <h3 id="modal-edit-member-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Edit Member</h3>
            </div>
            <button type="button" class="btn-close-modal" id="btn-close-edit-member" aria-label="Close dialog" style="width: 32px; height: 32px; border-radius: 50%; background: var(--white); border: 1px solid var(--line); display: grid; place-items: center; font-size: 18px; font-weight: 700; color: var(--muted); cursor: pointer; flex-shrink: 0; line-height: 1;">&times;</button>
        </div>

        {{-- Form --}}
        <form id="form-edit-member" method="POST" action="" enctype="multipart/form-data" novalidate style="display: flex; flex-direction: column; flex: 1; min-height: 0; margin: 0;">
            @csrf
            @method('PUT')

            <div class="member-modal-body" style="padding: 22px 30px 48px; flex: 1; min-height: 440px; display: flex; flex-direction: column; gap: 18px;">
                {{-- Profile Photo Section --}}
                <div style="text-align: center; flex-shrink: 0;">
                    <label class="form-label" style="font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); display: block; margin-bottom: 6px; text-align: center;">
                        Profile Photo
                    </label>

                    {{-- Empty Dropzone when no photo --}}
                    <div id="edit-member-dropzone" class="crop-dropzone" style="height: 124px; width: 124px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; border-radius: 50%; cursor: pointer;">
                        <div class="crop-dropzone-icon" style="font-size: 26px; margin-bottom: 2px;">👤</div>
                        <div style="font-weight: 700; color: var(--ink); font-size: 12.5px; margin-bottom: 1px;">Choose Photo</div>
                        <small style="color: var(--muted); font-size: 9.5px; display: block; line-height: 1.2;">Click or drag photo</small>
                    </div>

                    <input type="file" id="edit-photo" name="photo" accept="image/*" style="display: none;">

                    {{-- Photo Preview & Crop Studio --}}
                    <div id="edit-member-studio" style="display: none;">
                        <div class="crop-viewport-container member-crop-viewport" id="edit-member-viewport" style="width: 124px; height: 124px; margin: 0 auto 8px; border-radius: 50%;">
                            <img id="edit-member-preview-img" class="crop-viewport-image" alt="Edit Photo Preview" src="">
                            <div class="crop-grid-overlay" style="border-radius: 50%;">
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; max-width: 220px; margin: 0 auto 6px;">
                            <div class="crop-zoom-bar" style="flex: 1; padding: 4px 8px; font-size: 11px; border-radius: 8px;">
                                <span id="edit-member-zoom-label" style="white-space: nowrap; font-size: 11px;">1.0x</span>
                                <input type="range" id="edit-member-zoom-range" min="1" max="2.5" step="0.05" value="1" style="height: 4px;">
                                <button type="button" id="edit-member-btn-reset" class="crop-preset-btn" style="padding: 2px 6px; font-size: 10px;">Reset</button>
                            </div>
                            <button type="button" id="edit-member-btn-change" class="button button-ghost button-sm" style="padding: 3px 8px; font-size: 11px; height: 28px;">
                                Change
                            </button>
                        </div>
                    </div>

                    {{-- Remove Photo Option --}}
                    <div id="edit-member-remove-photo-wrap" style="display: none; margin-top: 6px;">
                        <label style="font-size: 11.5px; color: var(--red); display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" id="edit-remove-photo" name="remove_photo" value="1">
                            Remove current photo
                        </label>
                    </div>
                </div>

                {{-- Horizontal Layout Grid for Form Inputs --}}
                <div class="member-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; align-items: start; position: relative;">
                    {{-- Left Column, Row 1: Full Name --}}
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="edit-full-name" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                            Full Name <span style="color: var(--red);">*</span>
                        </label>
                        <input type="text" id="edit-full-name" name="full_name" class="form-input" required style="height: 44px; min-height: 44px; padding: 10px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; width: 100%; box-sizing: border-box;">
                    </div>

                    {{-- Right Column, Row 1: User Account (Searchable Dropdown) --}}
                    <div class="form-group" style="margin: 0; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 8px;">
                            <label class="form-label" for="edit-user-id" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); margin: 0; white-space: nowrap;">
                                User Account
                            </label>
                            <button type="button" class="btn-trigger-quick-account" data-target="edit" style="background: none; border: none; padding: 0; color: var(--forest); font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: underline; font-family: inherit; white-space: nowrap;">
                                Create New Account
                            </button>
                        </div>

                        {{-- Searchable Dropdown Container --}}
                        <div class="byc-searchable-select" id="searchable-edit-user" style="position: relative; width: 100%;">
                            {{-- Standard underlying select for form data & validations --}}
                            <select id="edit-user-id" name="user_id" tabindex="-1" aria-hidden="true" style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; margin: -1px; clip: rect(0,0,0,0); overflow: hidden;">
                                <option value="">None (Unlinked)</option>
                                @if(isset($users) && $users->isNotEmpty())
                                    <optgroup label="Existing Accounts">
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}" data-email="{{ $u->email }}" data-username="{{ $u->username }}" data-linked="{{ $u->member_id ? '1' : '0' }}">{{ $u->email }} ({{ $u->username }}){{ $u->member_id ? ' [Linked]' : '' }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>

                            {{-- Trigger Button --}}
                            <button type="button" class="byc-select-trigger" id="btn-trigger-edit-user" aria-haspopup="listbox" aria-expanded="false" style="width: 100%; height: 44px; min-height: 44px; padding: 10px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; border: 1px solid var(--line); background: var(--white); color: var(--ink); display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; text-align: left; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box;">
                                <span class="byc-select-trigger-text" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;">
                                    None (Unlinked)
                                </span>
                                <svg class="byc-select-chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted); flex-shrink: 0; transition: transform 0.2s ease;">
                                    <path d="M6 8l4 4 4-4"/>
                                </svg>
                            </button>

                            {{-- Dropdown Menu with Search --}}
                            <div class="byc-select-dropdown" id="dropdown-menu-edit-user" role="listbox" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: var(--white); border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 16px 36px rgba(18, 30, 23, 0.16); z-index: 1060; padding: 8px; box-sizing: border-box; flex-direction: column;">
                                <div style="position: relative; margin-bottom: 6px;">
                                    <input type="text" class="byc-select-search-input" placeholder="Search account..." autocomplete="off" style="width: 100%; height: 36px; padding: 6px 10px 6px 32px; font-size: 13px; border: 1px solid var(--line); border-radius: 8px; background: var(--cream); color: var(--ink); box-sizing: border-box; outline: none; font-family: inherit;">
                                    <svg style="position: absolute; left: 10px; top: 11px; color: var(--muted); pointer-events: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </div>
                                <div class="byc-select-options-list" style="max-height: 225px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px;">
                                    {{-- Dynamically populated --}}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Left Column, Row 2: Date of Birth --}}
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="edit-dob" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                            Date of Birth
                        </label>
                        <input type="date" id="edit-dob" name="date_of_birth" max="{{ date('Y-m-d') }}" class="form-input" style="height: 44px; min-height: 44px; padding: 10px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; width: 100%; box-sizing: border-box;">
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Used for community birthday celebrations.</small>
                    </div>

                    {{-- Right Column, Row 2: User Account Note / Space --}}
                    <div class="form-group" style="margin: 0; padding-top: 4px;">
                        <small style="color: var(--muted); font-size: 11.5px; display: block; line-height: 1.45;">
                            Link this member to an existing user account so they can log in and view their birthday wishes.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding: 14px 28px; background: var(--paper); border-top: 1px solid var(--line); display: flex; justify-content: flex-end; align-items: center; flex-shrink: 0;">
                <button type="submit" class="button button-primary button-sm" id="btn-submit-edit-member" style="min-width: 130px; height: 38px; font-weight: 700;">
                    Update Member
                </button>
            </div>
        </form>
    </div>
</div>

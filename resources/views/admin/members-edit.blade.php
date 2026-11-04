@extends('layouts.admin')

@section('title', 'Edit Member: ' . $member->full_name . ' — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow" style="color: var(--forest); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; font-size: 11.5px; display: block; margin-bottom: 4px;">
            Community Directory
        </span>
        <h1 style="font: 800 28px 'Manrope', sans-serif; color: var(--ink); margin: 0 0 6px;">Edit Member</h1>
        <p style="color: var(--muted); font-size: 14.5px; margin: 0; max-width: 680px; line-height: 1.5;">
            Update community identity, member birthday, profile photo, and user login credentials for <strong>{{ $member->full_name }}</strong>.
        </p>
    </div>
</div>
@endsection

@section('content')
<div class="member-create-wrapper" style="max-width: 1200px; margin: 0 auto; padding-bottom: 40px;">
    <form method="POST" action="{{ route('admin.members.update', $member->id) }}" enctype="multipart/form-data" id="form-edit-member-page" novalidate>
        @csrf
        @method('PUT')

        {{-- 2-Column Responsive Grid matching Add New Member --}}
        <div class="member-create-grid" style="display: grid; grid-template-columns: 380px 1fr; gap: 32px; align-items: stretch;">

            {{-- Left Column: Member Photo & Studio Card --}}
            <div class="member-photo-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 36px 30px; box-shadow: var(--shadow); text-align: center; display: flex; flex-direction: column; align-items: center; height: 100%; box-sizing: border-box;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 22px; display: block;">
                    Profile Photo
                </span>

                {{-- Empty Dropzone (Displayed if member has no photo) --}}
                <div id="edit-photo-dropzone" class="crop-dropzone" style="height: 190px; width: 190px; margin: 0 auto 18px; display: {{ $member->photo_url ? 'none' : 'flex' }}; flex-direction: column; align-items: center; justify-content: center; padding: 18px; border-radius: 50%; cursor: pointer; border: 2px dashed var(--line); background: var(--cream); transition: border-color 0.2s, background-color 0.2s; box-sizing: border-box;">
                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted); margin-bottom: 6px;">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <div style="font-weight: 700; color: var(--ink); font-size: 14px; margin-bottom: 3px;">Choose Photo</div>
                    <small style="color: var(--muted); font-size: 11px; display: block; line-height: 1.2;">Click or drag photo</small>
                </div>

                {{-- Interactive Crop Studio (Active immediately if member has photo, or after new file is selected) --}}
                <div id="edit-photo-studio" style="display: {{ $member->photo_url ? 'block' : 'none' }}; width: 100%; margin-bottom: 18px;">
                    <div class="crop-viewport-wrap" style="position: relative; width: 190px; height: 190px; margin: 0 auto 12px; border-radius: 50%; overflow: hidden; border: 3px solid var(--forest); box-shadow: 0 8px 26px rgba(44, 76, 56, 0.2); background: #141f17; cursor: grab; user-select: none; touch-action: none; transition: opacity 0.2s ease;" id="edit-crop-viewport">
                        <img id="edit-crop-img" alt="Crop Preview" src="{{ $member->photo_url ?? '' }}" crossorigin="anonymous" style="position: absolute; top: 0; left: 0; pointer-events: none; will-change: transform, left, top, width, height;">
                        <div class="crop-grid-overlay" style="position: absolute; inset: 0; pointer-events: none; border-radius: 50%; display: grid; grid-template-columns: 1fr 1fr 1fr; grid-template-rows: 1fr 1fr 1fr; opacity: 0.35;">
                            <div style="border: 1px dashed rgba(255,255,255,0.4);"></div><div style="border: 1px dashed rgba(255,255,255,0.4);"></div><div style="border: 1px dashed rgba(255,255,255,0.4);"></div>
                            <div style="border: 1px dashed rgba(255,255,255,0.4);"></div><div style="border: 1px dashed rgba(255,255,255,0.4);"></div><div style="border: 1px dashed rgba(255,255,255,0.4);"></div>
                            <div style="border: 1px dashed rgba(255,255,255,0.4);"></div><div style="border: 1px dashed rgba(255,255,255,0.4);"></div><div style="border: 1px dashed rgba(255,255,255,0.4);"></div>
                        </div>
                        <div style="position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); background: rgba(18, 30, 23, 0.85); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px; pointer-events: none; white-space: nowrap;">
                            Drag to Reposition
                        </div>
                    </div>

                    {{-- Zoom Slider & Controls --}}
                    <div class="crop-zoom-control-bar" id="edit-crop-zoom-bar" style="display: flex; align-items: center; justify-content: center; gap: 8px; max-width: 300px; margin: 0 auto 12px; background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 6px 12px;">
                        <span id="edit-crop-zoom-label" style="font-size: 11.5px; font-weight: 700; color: var(--ink); min-width: 32px;">1.0x</span>
                        <input type="range" id="edit-crop-zoom-range" min="1" max="3" step="0.05" value="1" style="flex: 1; accent-color: var(--forest); cursor: pointer; height: 5px;" title="Zoom in / out">
                        <button type="button" id="btn-edit-crop-reset" class="button button-ghost button-sm" style="padding: 2px 8px; height: 24px; font-size: 11px;">Reset</button>
                    </div>

                    {{-- Actions: Change Photo, Remove Photo, Cancel New Photo --}}
                    <div style="display: flex; gap: 10px; align-items: center; justify-content: center; flex-wrap: wrap;">
                        <button type="button" id="btn-edit-trigger-change-photo" class="button button-secondary button-sm" style="font-size: 12px; height: 32px; padding: 0 14px;">
                            Change Photo
                        </button>
                        @if($member->photo_url)
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--red); cursor: pointer; user-select: none;">
                                <input type="checkbox" name="remove_photo" id="edit-remove-photo-cb" value="1" style="accent-color: var(--red);">
                                <span>Remove Photo</span>
                            </label>
                        @endif
                        <button type="button" id="btn-edit-cancel-new-file" class="button button-ghost button-sm" style="display: none; padding: 2px 10px; height: 30px; font-size: 11.5px; color: var(--muted);">
                            Cancel New Photo
                        </button>
                    </div>
                </div>

                {{-- Hidden Inputs for Upload & Cropped Base64 --}}
                <input
                    type="file"
                    name="photo"
                    id="edit-photo-input"
                    accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/heic,image/heif,.heic,.heif"
                    style="display: none;"
                >
                <input
                    type="hidden"
                    name="photo_base64"
                    id="edit-photo-base64"
                    value=""
                >
                <input
                    type="hidden"
                    name="raw_photo_base64"
                    id="edit-raw-photo-base64"
                    value=""
                >
                <input
                    type="hidden"
                    name="crop_zoom"
                    id="edit-crop-zoom"
                    value="{{ (float) ($member->memberPhoto?->crop_zoom ?? 1.0) }}"
                >
                <input
                    type="hidden"
                    name="crop_offset_x"
                    id="edit-crop-offset-x"
                    value="{{ (float) ($member->memberPhoto?->crop_offset_x ?? 0) }}"
                >
                <input
                    type="hidden"
                    name="crop_offset_y"
                    id="edit-crop-offset-y"
                    value="{{ (float) ($member->memberPhoto?->crop_offset_y ?? 0) }}"
                >

                {{-- Helper Note --}}
                <div style="width: 100%; border-top: 1px solid var(--line); padding-top: 18px; margin-top: auto; text-align: center; font-size: 11.5px; color: var(--muted); line-height: 1.5;">
                    Supports JPG, PNG, WEBP, GIF, or HEIC (Max 50MB)
                </div>
            </div>

            {{-- Right Column: Member Details & Credentials Card --}}
            <div class="member-details-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 38px 44px; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 26px;">

                {{-- Section 1: Personal Details --}}
                <div>
                    <div style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 20px;">
                        <span class="eyebrow" style="color: var(--forest); font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 3px;">
                            Step 1
                        </span>
                        <h3 style="font-family: 'Manrope', sans-serif; font-size: 19px; font-weight: 800; color: var(--ink); margin: 0 0 4px;">
                            Personal Information
                        </h3>
                        <p style="margin: 0; color: var(--muted); font-size: 13.5px;">
                            Community identity and member details.
                        </p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 22px;" class="member-form-row">
                        {{-- Full Name --}}
                        <div class="form-group" style="margin: 0;">
                            <label for="edit-full-name" class="form-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                                Full Name <span style="color: var(--red);">*</span>
                            </label>
                            <input
                                type="text"
                                name="full_name"
                                id="edit-full-name"
                                class="form-input @error('full_name') input-invalid @enderror"
                                value="{{ old('full_name', $member->full_name) }}"
                                required
                                placeholder="e.g. Jonathan Wijaya"
                                style="width: 100%; height: 44px; padding: 10px 14px; font-size: 14px; border-radius: 10px; box-sizing: border-box;"
                            >
                            @error('full_name')
                                <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Date of Birth --}}
                        <div class="form-group" style="margin: 0;">
                            <label for="edit-dob" class="form-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                                Date of Birth
                            </label>
                            <input
                                type="date"
                                name="date_of_birth"
                                id="edit-dob"
                                max="{{ date('Y-m-d') }}"
                                class="form-input @error('date_of_birth') input-invalid @enderror"
                                value="{{ old('date_of_birth', $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '') }}"
                                style="width: 100%; height: 44px; padding: 10px 14px; font-size: 14px; border-radius: 10px; box-sizing: border-box;"
                            >
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">
                                Used for community birthday celebrations.
                            </small>
                            @error('date_of_birth')
                                <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section 2: Account Access & Credentials --}}
                <div style="background: var(--paper); border: 1px solid var(--line); border-radius: 20px; padding: 24px 26px; box-sizing: border-box;">
                    <div style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 18px;">
                        <span class="eyebrow" style="color: var(--forest); font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 3px;">
                            Step 2
                        </span>
                        <h4 style="font-family: 'Manrope', sans-serif; font-size: 17px; font-weight: 800; color: var(--ink); margin: 0 0 4px;">
                            User Login Account
                        </h4>
                        <p style="margin: 0; font-size: 13px; color: var(--muted);">
                            Manage this member's user portal login credentials (email & password) or account link.
                        </p>
                    </div>

                    @if($member->user)
                        {{-- Mode Selector when member HAS a linked user --}}
                        <div style="display: flex; gap: 18px; margin-bottom: 18px; flex-wrap: wrap;">
                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: var(--ink);">
                                <input type="radio" name="account_mode" id="mode-edit-current" value="current" checked style="accent-color: var(--forest); width: 16px; height: 16px;">
                                <span>Edit Current Account Credentials</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: var(--ink);">
                                <input type="radio" name="account_mode" id="mode-link-diff" value="link" style="accent-color: var(--forest); width: 16px; height: 16px;">
                                <span>Switch to Different Account</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: var(--red);">
                                <input type="radio" name="account_mode" id="mode-unlink" value="unlink" style="accent-color: var(--red); width: 16px; height: 16px;">
                                <span>Unlink Account</span>
                            </label>
                        </div>

                        {{-- Block A: Edit Current Credentials --}}
                        <div id="block-edit-current-account" style="display: flex; flex-direction: column; gap: 14px;">
                            {{-- Email --}}
                            <div class="form-group" style="margin: 0;">
                                <label for="edit-user-email" class="form-label" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                    Account Email <span style="color: var(--red);">*</span>
                                </label>
                                <input
                                    type="email"
                                    name="edit_user_email"
                                    id="edit-user-email"
                                    class="form-input @error('edit_user_email') input-invalid @enderror"
                                    value="{{ old('edit_user_email', $member->user->email) }}"
                                    required
                                    placeholder="member@bycgrowth.org"
                                    style="width: 100%; height: 42px; padding: 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                >
                                <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px;">
                                    Used for signing in to the member portal.
                                </small>
                                @error('edit_user_email')
                                    <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Username & Password Row --}}
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;" class="member-account-row">
                                {{-- Username --}}
                                <div>
                                    <label for="edit-user-username" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                        Username (Optional)
                                    </label>
                                    <input
                                        type="text"
                                        name="edit_user_username"
                                        id="edit-user-username"
                                        class="form-input @error('edit_user_username') input-invalid @enderror"
                                        value="{{ old('edit_user_username', $member->user->username) }}"
                                        placeholder="Username"
                                        style="width: 100%; height: 42px; padding: 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                    >
                                    @error('edit_user_username')
                                        <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Password --}}
                                <div>
                                    <label for="edit-user-password" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                        New Password (Optional)
                                    </label>
                                    <div class="password-field-wrapper" style="position: relative;">
                                        <input
                                            type="password"
                                            name="edit_user_password"
                                            id="edit-user-password"
                                            class="form-input @error('edit_user_password') input-invalid @enderror"
                                            value="{{ old('edit_user_password') }}"
                                            placeholder="Leave blank to keep unchanged"
                                            style="width: 100%; height: 42px; padding: 8px 40px 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                        >
                                        <button
                                            type="button"
                                            class="btn-toggle-password"
                                            data-target="edit-user-password"
                                            title="Toggle password visibility"
                                            aria-label="Toggle password visibility"
                                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px; display: grid; place-items: center;"
                                        >
                                            <span class="icon-eye"><x-icon name="eye" /></span>
                                            <span class="icon-eye-off" style="display: none;"><x-icon name="eye-off" /></span>
                                        </button>
                                    </div>
                                    <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px;">
                                        Leave blank to keep current password.
                                    </small>
                                    @error('edit_user_password')
                                        <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Block B: Switch to Different Account --}}
                        <div id="block-link-diff-account" style="display: none;">
                            <label for="btn-trigger-edit-diff-user" class="form-label" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 6px;">
                                Select Existing Account
                            </label>
                            <div class="byc-searchable-select" id="searchable-edit-diff-user" style="position: relative; width: 100%; max-width: 100%;">
                                <select
                                    name="user_id"
                                    id="edit-diff-user-id"
                                    tabindex="-1"
                                    aria-hidden="true"
                                    style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; margin: -1px; clip: rect(0,0,0,0); overflow: hidden;"
                                >
                                    <option value="">-- Choose Account to Link --</option>
                                    @foreach($users as $user)
                                        <option
                                            value="{{ $user->id }}"
                                            data-name="{{ $user->name ?: $user->username }}"
                                            data-username="{{ $user->username }}"
                                            data-email="{{ $user->email }}"
                                            data-linked-member="{{ ($user->member && $user->member->id !== $member->id) ? $user->member->full_name : '' }}"
                                            {{ old('user_id', $member->user->id) == $user->id ? 'selected' : '' }}
                                        >
                                            {{ $user->name ?: $user->username }} ({{ $user->email }})@if($user->member && $user->member->id !== $member->id) [Currently linked: {{ $user->member->full_name }}]@endif
                                        </option>
                                    @endforeach
                                </select>

                                <button
                                    type="button"
                                    class="byc-select-trigger"
                                    id="btn-trigger-edit-diff-user"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                    style="width: 100%; max-width: 100%; height: 44px; min-height: 44px; padding: 8px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; border: 1px solid var(--line); background: var(--white); color: var(--ink); display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; text-align: left; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; overflow: hidden;"
                                >
                                    <span class="byc-select-trigger-text" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; color: var(--muted);">
                                        -- Choose Account to Link --
                                    </span>
                                    <svg class="byc-select-chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted); flex-shrink: 0; transition: transform 0.2s ease;">
                                        <path d="M6 8l4 4 4-4"/>
                                    </svg>
                                </button>

                                <div
                                    class="byc-select-dropdown"
                                    id="dropdown-menu-edit-diff-user"
                                    role="listbox"
                                    style="display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; width: 100%; max-width: 100%; background: var(--white); border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 14px 34px rgba(18, 30, 23, 0.16); z-index: 1060; padding: 8px; box-sizing: border-box; flex-direction: column; overflow: hidden;"
                                >
                                    <div style="position: relative; margin-bottom: 6px; flex-shrink: 0;">
                                        <input
                                            type="text"
                                            class="byc-select-search-input"
                                            placeholder="Search by name, username, or email..."
                                            autocomplete="off"
                                            style="width: 100%; height: 38px; padding: 6px 12px 6px 34px; font-size: 13px; border: 1px solid var(--line); border-radius: 8px; background: var(--cream); color: var(--ink); box-sizing: border-box; outline: none; font-family: inherit;"
                                        >
                                        <svg style="position: absolute; left: 11px; top: 12px; color: var(--muted); pointer-events: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                    </div>
                                    <div class="byc-select-options-list" style="max-height: 176px; overflow-y: auto; overflow-x: hidden; overscroll-behavior: contain; display: flex; flex-direction: column; gap: 2px;">
                                        {{-- Dynamically populated --}}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Block C: Unlink Warning --}}
                        <div id="block-unlink-account" style="display: none; padding: 14px 18px; background: #fff5f5; border: 1px solid #ffdcdc; border-radius: 12px; font-size: 13px; color: #a93226;">
                            ⚠️ This will detach <strong>{{ $member->user->email }}</strong> from this member. The member will no longer have an active login account until linked again.
                        </div>

                    @else
                        {{-- Mode Selector when member has NO linked user --}}
                        <div style="display: flex; gap: 18px; margin-bottom: 18px; flex-wrap: wrap;">
                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: var(--ink);">
                                <input type="radio" name="account_mode" id="mode-create-new-edit" value="new" checked style="accent-color: var(--forest); width: 16px; height: 16px;">
                                <span>Create New Account</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: var(--ink);">
                                <input type="radio" name="account_mode" id="mode-link-existing-edit" value="link" style="accent-color: var(--forest); width: 16px; height: 16px;">
                                <span>Link to Existing Account</span>
                            </label>
                        </div>

                        {{-- Block: Create New User Account --}}
                        <div id="block-create-new-account" style="display: flex; flex-direction: column; gap: 14px;">
                            <div class="form-group" style="margin: 0;">
                                <label for="new-user-email-edit" class="form-label" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                    Account Email <span style="color: var(--red);">*</span>
                                </label>
                                <input
                                    type="email"
                                    name="new_user_email"
                                    id="new-user-email-edit"
                                    class="form-input @error('new_user_email') input-invalid @enderror"
                                    value="{{ old('new_user_email') }}"
                                    placeholder="member@bycgrowth.org"
                                    style="width: 100%; height: 42px; padding: 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                >
                                @error('new_user_email')
                                    <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;" class="member-account-row">
                                <div>
                                    <label for="new-user-username-edit" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                        Username (Optional)
                                    </label>
                                    <input
                                        type="text"
                                        name="new_user_username"
                                        id="new-user-username-edit"
                                        class="form-input @error('new_user_username') input-invalid @enderror"
                                        value="{{ old('new_user_username') }}"
                                        placeholder="Auto-generated if blank"
                                        style="width: 100%; height: 42px; padding: 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                    >
                                    @error('new_user_username')
                                        <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="new-user-password-edit" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                        Initial Password
                                    </label>
                                    <div class="password-field-wrapper" style="position: relative;">
                                        <input
                                            type="password"
                                            name="new_user_password"
                                            id="new-user-password-edit"
                                            class="form-input @error('new_user_password') input-invalid @enderror"
                                            value="{{ old('new_user_password', 'password123') }}"
                                            placeholder="password123"
                                            style="width: 100%; height: 42px; padding: 8px 40px 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                        >
                                        <button
                                            type="button"
                                            class="btn-toggle-password"
                                            data-target="new-user-password-edit"
                                            title="Toggle password visibility"
                                            aria-label="Toggle password visibility"
                                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px; display: grid; place-items: center;"
                                        >
                                            <span class="icon-eye"><x-icon name="eye" /></span>
                                            <span class="icon-eye-off" style="display: none;"><x-icon name="eye-off" /></span>
                                        </button>
                                    </div>
                                    <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px;">
                                        Default: password123 (member can change anytime).
                                    </small>
                                    @error('new_user_password')
                                        <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Block: Link Existing User Account --}}
                        <div id="block-link-account-edit" style="display: none;">
                            <label for="btn-trigger-edit-existing-user" class="form-label" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 6px;">
                                Select Existing User Account
                            </label>
                            <div class="byc-searchable-select" id="searchable-edit-existing-user" style="position: relative; width: 100%; max-width: 100%;">
                                <select
                                    name="user_id"
                                    id="edit-existing-user-id"
                                    tabindex="-1"
                                    aria-hidden="true"
                                    style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; margin: -1px; clip: rect(0,0,0,0); overflow: hidden;"
                                >
                                    <option value="">-- Choose Account to Link --</option>
                                    @foreach($users as $user)
                                        <option
                                            value="{{ $user->id }}"
                                            data-name="{{ $user->name ?: $user->username }}"
                                            data-username="{{ $user->username }}"
                                            data-email="{{ $user->email }}"
                                            data-linked-member="{{ $user->member ? $user->member->full_name : '' }}"
                                            {{ old('user_id') == $user->id ? 'selected' : '' }}
                                        >
                                            {{ $user->name ?: $user->username }} ({{ $user->email }})@if($user->member) [Currently linked: {{ $user->member->full_name }}]@endif
                                        </option>
                                    @endforeach
                                </select>

                                <button
                                    type="button"
                                    class="byc-select-trigger"
                                    id="btn-trigger-edit-existing-user"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                    style="width: 100%; max-width: 100%; height: 44px; min-height: 44px; padding: 8px 14px; font-size: 14px; line-height: 1.4; border-radius: 10px; border: 1px solid var(--line); background: var(--white); color: var(--ink); display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; text-align: left; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; overflow: hidden;"
                                >
                                    <span class="byc-select-trigger-text" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; color: var(--muted);">
                                        -- Choose Account to Link --
                                    </span>
                                    <svg class="byc-select-chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted); flex-shrink: 0; transition: transform 0.2s ease;">
                                        <path d="M6 8l4 4 4-4"/>
                                    </svg>
                                </button>

                                <div
                                    class="byc-select-dropdown"
                                    id="dropdown-menu-edit-existing-user"
                                    role="listbox"
                                    style="display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; width: 100%; max-width: 100%; background: var(--white); border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 14px 34px rgba(18, 30, 23, 0.16); z-index: 1060; padding: 8px; box-sizing: border-box; flex-direction: column; overflow: hidden;"
                                >
                                    <div style="position: relative; margin-bottom: 6px; flex-shrink: 0;">
                                        <input
                                            type="text"
                                            class="byc-select-search-input"
                                            placeholder="Search by name, username, or email..."
                                            autocomplete="off"
                                            style="width: 100%; height: 38px; padding: 6px 12px 6px 34px; font-size: 13px; border: 1px solid var(--line); border-radius: 8px; background: var(--cream); color: var(--ink); box-sizing: border-box; outline: none; font-family: inherit;"
                                        >
                                        <svg style="position: absolute; left: 11px; top: 12px; color: var(--muted); pointer-events: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                    </div>
                                    <div class="byc-select-options-list" style="max-height: 176px; overflow-y: auto; overflow-x: hidden; overscroll-behavior: contain; display: flex; flex-direction: column; gap: 2px;">
                                        {{-- Dynamically populated --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="member-create-actions" style="display: flex; justify-content: flex-end; align-items: center; gap: 14px; margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line);">
                    <a href="{{ route('admin.members') }}" class="button button-ghost" style="height: 44px; padding: 11px 24px; font-weight: 700; text-decoration: none;">
                        Cancel
                    </a>
                    <button type="submit" class="button button-primary" id="btn-submit-edit-member" style="height: 44px; min-width: 160px; font-weight: 700;">
                        Update Member
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.member-create-grid {
    align-items: stretch !important;
}
.member-photo-card {
    height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
}
.crop-viewport-wrap:active {
    cursor: grabbing !important;
}
.crop-viewport-wrap:active .crop-grid-overlay {
    opacity: 0.65 !important;
}
@media (max-width: 960px) {
    .member-create-grid {
        grid-template-columns: 1fr !important;
        gap: 24px !important;
    }
    .member-details-card {
        padding: 26px 20px !important;
    }
    .member-form-row {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    .member-account-row {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
}
@media (max-width: 600px) {
    .member-photo-card {
        padding: 24px 16px !important;
        border-radius: 18px !important;
    }
    .member-details-card {
        padding: 24px 16px !important;
        border-radius: 18px !important;
    }
    .member-create-actions {
        flex-direction: column-reverse !important;
        width: 100% !important;
        gap: 12px !important;
    }
    .member-create-actions .button {
        width: 100% !important;
        justify-content: center !important;
        text-align: center !important;
        min-height: 44px !important;
    }
    .crop-zoom-control-bar {
        width: 100% !important;
        box-sizing: border-box !important;
    }
}
@media (max-width: 480px) {
    .member-create-wrapper {
        padding-bottom: 30px !important;
    }
    .member-photo-card {
        padding: 20px 14px !important;
        border-radius: 16px !important;
    }
    .member-details-card {
        padding: 20px 14px !important;
        border-radius: 16px !important;
    }
}
</style>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/heic2any.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Searchable Account Select Controller (with Viewport Height & Width Clamping)
    function initSearchableAccountSelect(selectId, wrapperId, triggerId, menuId) {
        const select = document.getElementById(selectId);
        const wrapper = document.getElementById(wrapperId);
        const trigger = document.getElementById(triggerId);
        const menu = document.getElementById(menuId);
        if (!select || !wrapper || !trigger || !menu) return null;

        const triggerText = trigger.querySelector('.byc-select-trigger-text');
        const chevron = trigger.querySelector('.byc-select-chevron');
        const searchInput = menu.querySelector('.byc-select-search-input');
        const optionsList = menu.querySelector('.byc-select-options-list');

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function buildOptions() {
            if (!optionsList) return;
            optionsList.innerHTML = '';
            const options = Array.from(select.querySelectorAll('option'));

            options.forEach(opt => {
                const val = opt.value;
                const text = opt.textContent.trim();
                const name = opt.getAttribute('data-name') || '';
                const username = opt.getAttribute('data-username') || '';
                const email = opt.getAttribute('data-email') || '';
                const linkedMember = opt.getAttribute('data-linked-member') || '';
                const isSelected = String(select.value) === String(val);

                const optItem = document.createElement('div');
                optItem.className = 'byc-select-option' + (isSelected ? ' selected' : '');
                optItem.setAttribute('data-value', val);
                optItem.setAttribute('data-search', `${name} ${username} ${email} ${linkedMember} ${text}`.toLowerCase());
                optItem.setAttribute('title', text);
                optItem.style.cssText = 'padding: 8px 10px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 13px; color: var(--ink); user-select: none; transition: background 0.12s; max-width: 100%; box-sizing: border-box; overflow: hidden;';

                if (!val) {
                    optItem.innerHTML = `
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; color: var(--muted); font-style: italic;">
                            ${escapeHtml(text)}
                        </span>
                        <span class="byc-select-check" style="color: var(--forest); font-weight: 800; font-size: 13px; flex-shrink: 0; ${isSelected ? '' : 'display: none;'}">✓</span>
                    `;
                } else {
                    optItem.innerHTML = `
                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;">
                            <span style="font-weight: 700; color: var(--ink);">${escapeHtml(name || username || email)}</span>
                            <span style="color: var(--muted); font-size: 12.5px; margin-left: 4px;">(${escapeHtml(email)})</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            ${linkedMember ? `<span style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 10.5px; font-weight: 700; color: var(--forest); background: rgba(33, 77, 51, 0.09); padding: 2px 7px; border-radius: 6px;">Linked: ${escapeHtml(linkedMember)}</span>` : ''}
                            <span class="byc-select-check" style="color: var(--forest); font-weight: 800; font-size: 13px; ${isSelected ? '' : 'display: none;'}">✓</span>
                        </div>
                    `;
                }

                optItem.addEventListener('mouseenter', () => {
                    optItem.style.background = 'var(--cream)';
                });
                optItem.addEventListener('mouseleave', () => {
                    optItem.style.background = optItem.classList.contains('selected') ? 'rgba(33, 77, 51, 0.08)' : 'transparent';
                });

                if (isSelected) {
                    optItem.style.background = 'rgba(33, 77, 51, 0.08)';
                }

                optItem.addEventListener('click', (e) => {
                    e.stopPropagation();
                    setValue(val);
                    close();
                });

                optionsList.appendChild(optItem);
            });

            updateTrigger();
        }

        function updateTrigger() {
            const selectedOpt = select.options[select.selectedIndex];
            const text = selectedOpt ? selectedOpt.textContent.replace(/\s+/g, ' ').trim() : '-- Choose Account to Link --';
            if (triggerText) {
                triggerText.textContent = text;
                triggerText.setAttribute('title', text);
                if (!select.value) {
                    triggerText.style.color = 'var(--muted)';
                } else {
                    triggerText.style.color = 'var(--ink)';
                }
            }
        }

        function setValue(val) {
            select.value = val;
            updateTrigger();
            if (optionsList) {
                optionsList.querySelectorAll('.byc-select-option').forEach(el => {
                    const isMatch = el.getAttribute('data-value') === String(val);
                    el.classList.toggle('selected', isMatch);
                    el.style.background = isMatch ? 'rgba(33, 77, 51, 0.08)' : 'transparent';
                    const check = el.querySelector('.byc-select-check');
                    if (check) check.style.display = isMatch ? 'inline' : 'none';
                });
            }
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function fitDropdownToViewport() {
            if (menu.style.display === 'none' || !optionsList) return;
            const rect = trigger.getBoundingClientRect();
            const vh = window.innerHeight || document.documentElement.clientHeight;
            const spaceBelow = vh - rect.bottom - 16;
            const spaceAbove = rect.top - 84;

            if (spaceBelow < 185 && spaceAbove > spaceBelow) {
                menu.style.top = 'auto';
                menu.style.bottom = 'calc(100% + 6px)';
                const cappedH = Math.max(105, Math.min(176, spaceAbove - 58));
                optionsList.style.maxHeight = `${cappedH}px`;
            } else {
                menu.style.bottom = 'auto';
                menu.style.top = 'calc(100% + 6px)';
                const cappedH = Math.max(105, Math.min(176, spaceBelow - 58));
                optionsList.style.maxHeight = `${cappedH}px`;
            }
        }

        function open() {
            menu.style.display = 'flex';
            trigger.setAttribute('aria-expanded', 'true');
            trigger.style.borderColor = 'var(--forest)';
            trigger.style.boxShadow = '0 0 0 3px rgba(33, 77, 51, 0.14)';
            if (chevron) chevron.style.transform = 'rotate(180deg)';

            fitDropdownToViewport();

            if (searchInput) {
                searchInput.value = '';
                filterOptions('');
                if (window.innerWidth > 768 && !window.matchMedia('(pointer: coarse)').matches) {
                    setTimeout(() => searchInput.focus({ preventScroll: true }), 30);
                }
            }
        }

        function close() {
            menu.style.display = 'none';
            trigger.setAttribute('aria-expanded', 'false');
            trigger.style.borderColor = 'var(--line)';
            trigger.style.boxShadow = 'none';
            if (chevron) chevron.style.transform = 'none';
        }

        function filterOptions(query) {
            if (!optionsList) return;
            const q = (query || '').toLowerCase().trim();
            const items = optionsList.querySelectorAll('.byc-select-option');
            let visibleCount = 0;

            items.forEach(item => {
                const val = item.getAttribute('data-value');
                if (!val && !q) {
                    item.style.display = 'flex';
                    visibleCount++;
                    return;
                }
                const searchData = item.getAttribute('data-search') || '';
                if (!q || searchData.includes(q)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            let noResultEl = optionsList.querySelector('.byc-select-no-results');
            if (visibleCount === 0) {
                if (!noResultEl) {
                    noResultEl = document.createElement('div');
                    noResultEl.className = 'byc-select-no-results';
                    noResultEl.style.cssText = 'padding: 14px 10px; text-align: center; color: var(--muted); font-size: 12.5px;';
                    optionsList.appendChild(noResultEl);
                }
                noResultEl.textContent = `No accounts found matching "${query}"`;
                noResultEl.style.display = 'block';
            } else if (noResultEl) {
                noResultEl.style.display = 'none';
            }
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (menu.style.display === 'flex') close();
            else open();
        });

        if (searchInput) {
            searchInput.addEventListener('input', (e) => filterOptions(e.target.value));
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    close();
                    trigger.focus();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstVisible = optionsList.querySelector('.byc-select-option[style*="display: flex"][data-value]:not([data-value=""])');
                    if (firstVisible) {
                        setValue(firstVisible.getAttribute('data-value'));
                        close();
                    }
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) close();
        });

        window.addEventListener('resize', fitDropdownToViewport, { passive: true });
        window.addEventListener('scroll', fitDropdownToViewport, { passive: true });

        buildOptions();
        return { setValue, close, updateTrigger };
    }

    const editDiffSelectCtrl = initSearchableAccountSelect(
        'edit-diff-user-id',
        'searchable-edit-diff-user',
        'btn-trigger-edit-diff-user',
        'dropdown-menu-edit-diff-user'
    );

    const editExistingSelectCtrl = initSearchableAccountSelect(
        'edit-existing-user-id',
        'searchable-edit-existing-user',
        'btn-trigger-edit-existing-user',
        'dropdown-menu-edit-existing-user'
    );

    // 2. Account Mode Switcher
    const modeEditCurrent = document.getElementById('mode-edit-current');
    const modeLinkDiff = document.getElementById('mode-link-diff');
    const modeUnlink = document.getElementById('mode-unlink');
    const blockEditCurrent = document.getElementById('block-edit-current-account');
    const blockLinkDiff = document.getElementById('block-link-diff-account');
    const blockUnlink = document.getElementById('block-unlink-account');
    const editUserEmail = document.getElementById('edit-user-email');

    function syncEditAccountMode() {
        if (!modeEditCurrent) return;
        if (modeEditCurrent.checked) {
            if (blockEditCurrent) blockEditCurrent.style.display = 'flex';
            if (blockLinkDiff) blockLinkDiff.style.display = 'none';
            if (blockUnlink) blockUnlink.style.display = 'none';
            if (editDiffSelectCtrl) editDiffSelectCtrl.close();
            if (editUserEmail) editUserEmail.setAttribute('required', 'required');
        } else if (modeLinkDiff && modeLinkDiff.checked) {
            if (blockEditCurrent) blockEditCurrent.style.display = 'none';
            if (blockLinkDiff) blockLinkDiff.style.display = 'block';
            if (blockUnlink) blockUnlink.style.display = 'none';
            if (editUserEmail) editUserEmail.removeAttribute('required');
        } else if (modeUnlink && modeUnlink.checked) {
            if (blockEditCurrent) blockEditCurrent.style.display = 'none';
            if (blockLinkDiff) blockLinkDiff.style.display = 'none';
            if (blockUnlink) blockUnlink.style.display = 'block';
            if (editDiffSelectCtrl) editDiffSelectCtrl.close();
            if (editUserEmail) editUserEmail.removeAttribute('required');
        }
    }

    [modeEditCurrent, modeLinkDiff, modeUnlink].forEach(radio => {
        if (radio) radio.addEventListener('change', syncEditAccountMode);
    });

    // Account mode switcher for unlinked member
    const modeCreateNewEdit = document.getElementById('mode-create-new-edit');
    const modeLinkExistingEdit = document.getElementById('mode-link-existing-edit');
    const blockCreateNew = document.getElementById('block-create-new-account');
    const blockLinkExisting = document.getElementById('block-link-account-edit');
    const newUserEmailEdit = document.getElementById('new-user-email-edit');

    function syncNewAccountMode() {
        if (!modeCreateNewEdit) return;
        if (modeLinkExistingEdit && modeLinkExistingEdit.checked) {
            if (blockCreateNew) blockCreateNew.style.display = 'none';
            if (blockLinkExisting) blockLinkExisting.style.display = 'block';
            if (newUserEmailEdit) newUserEmailEdit.removeAttribute('required');
        } else {
            if (blockCreateNew) blockCreateNew.style.display = 'flex';
            if (blockLinkExisting) blockLinkExisting.style.display = 'none';
            if (editExistingSelectCtrl) {
                editExistingSelectCtrl.setValue('');
                editExistingSelectCtrl.close();
            }
            if (newUserEmailEdit) newUserEmailEdit.setAttribute('required', 'required');
        }
    }

    [modeCreateNewEdit, modeLinkExistingEdit].forEach(radio => {
        if (radio) radio.addEventListener('change', syncNewAccountMode);
    });

    // 3. Interactive Photo Cropper
    const existingPhotoUrl = @json($member->raw_photo_url ?: $member->photo_url);
    const savedCropZoom = {{ (float) ($member->memberPhoto?->crop_zoom ?? 1.0) }};
    const savedOffsetX = {{ (float) ($member->memberPhoto?->crop_offset_x ?? 0) }};
    const savedOffsetY = {{ (float) ($member->memberPhoto?->crop_offset_y ?? 0) }};
    const dropzone = document.getElementById('edit-photo-dropzone');
    const studio = document.getElementById('edit-photo-studio');
    const fileInput = document.getElementById('edit-photo-input');
    const base64Input = document.getElementById('edit-photo-base64');
    const cropImg = document.getElementById('edit-crop-img');
    const viewport = document.getElementById('edit-crop-viewport');
    const zoomRange = document.getElementById('edit-crop-zoom-range');
    const zoomLabel = document.getElementById('edit-crop-zoom-label');
    const btnReset = document.getElementById('btn-edit-crop-reset');
    const btnTriggerChange = document.getElementById('btn-edit-trigger-change-photo');
    const btnCancelNewFile = document.getElementById('btn-edit-cancel-new-file');
    const removePhotoCb = document.getElementById('edit-remove-photo-cb');
    const form = document.getElementById('form-edit-member-page');
    const submitBtn = document.getElementById('btn-submit-edit-member');

    let originalFile = null;
    let rawPhotoBase64 = null;
    let imgNaturalW = 0;
    let imgNaturalH = 0;
    let currentZoom = savedCropZoom || 1.0;
    let currentOffsetX = savedOffsetX || 0;
    let currentOffsetY = savedOffsetY || 0;
    const vpSize = 190;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startOffsetX = 0;
    let startOffsetY = 0;
    let hasNewCrop = false;

    function getDimensions() {
        const baseScale = Math.max(vpSize / (imgNaturalW || 1), vpSize / (imgNaturalH || 1));
        const scale = baseScale * currentZoom;
        const displayW = imgNaturalW * scale;
        const displayH = imgNaturalH * scale;
        const minOffsetX = vpSize - displayW;
        const maxOffsetX = 0;
        const minOffsetY = vpSize - displayH;
        const maxOffsetY = 0;
        return { scale, displayW, displayH, minOffsetX, maxOffsetX, minOffsetY, maxOffsetY };
    }

    function clampOffsets() {
        const { minOffsetX, maxOffsetX, minOffsetY, maxOffsetY } = getDimensions();
        currentOffsetX = Math.min(maxOffsetX, Math.max(minOffsetX, currentOffsetX));
        currentOffsetY = Math.min(maxOffsetY, Math.max(minOffsetY, currentOffsetY));
    }

    function updateImageStyle() {
        const { displayW, displayH } = getDimensions();
        cropImg.style.width = `${displayW}px`;
        cropImg.style.height = `${displayH}px`;
        cropImg.style.left = `${currentOffsetX}px`;
        cropImg.style.top = `${currentOffsetY}px`;
    }

    function centerImage() {
        const { displayW, displayH } = getDimensions();
        currentOffsetX = (vpSize - displayW) / 2;
        currentOffsetY = (vpSize - displayH) / 2;
        clampOffsets();
        updateImageStyle();
    }

    function loadExistingUrl(url) {
        if (!url) return;
        originalFile = null;
        hasNewCrop = false;

        const applyImage = (src) => {
            cropImg.onload = () => {
                imgNaturalW = cropImg.naturalWidth;
                imgNaturalH = cropImg.naturalHeight;
                currentZoom = Math.max(1.0, savedCropZoom || 1.0);
                if (zoomRange) {
                    zoomRange.value = String(currentZoom);
                    zoomRange.disabled = false;
                }
                if (zoomLabel) zoomLabel.textContent = `${currentZoom.toFixed(1)}x`;
                if (dropzone) dropzone.style.display = 'none';
                if (studio) studio.style.display = 'block';
                if (btnCancelNewFile) btnCancelNewFile.style.display = 'none';

                if (savedOffsetX !== 0 || savedOffsetY !== 0) {
                    currentOffsetX = savedOffsetX;
                    currentOffsetY = savedOffsetY;
                    clampOffsets();
                    updateImageStyle();
                } else {
                    centerImage();
                }
            };
            cropImg.onerror = () => {
                console.warn('Failed to load existing photo url:', url);
                if (dropzone) dropzone.style.display = 'flex';
                if (studio) studio.style.display = 'none';
            };
            cropImg.src = src;
        };

        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('Fetch failed');
                return res.blob();
            })
            .then(blob => {
                const objectUrl = URL.createObjectURL(blob);
                applyImage(objectUrl);
            })
            .catch(() => {
                cropImg.crossOrigin = 'anonymous';
                applyImage(url + (url.includes('?') ? '&' : '?') + 't=' + Date.now());
            });
    }

    async function loadFile(file) {
        if (!file) return;
        if (file.size > 50 * 1024 * 1024) {
            alert('Photo is too large. Maximum size is 50MB.');
            fileInput.value = '';
            return;
        }

        originalFile = file;
        hasNewCrop = true;
        if (removePhotoCb) {
            removePhotoCb.checked = false;
            if (viewport) viewport.style.opacity = '1';
            if (zoomRange) zoomRange.disabled = false;
        }

        let displayBlob = file;
        if (/\.(heic|heif)$/i.test(file.name) && window.heic2any) {
            try {
                const converted = await window.heic2any({
                    blob: file,
                    toType: 'image/jpeg',
                    quality: 0.92
                });
                displayBlob = Array.isArray(converted) ? converted[0] : converted;
            } catch (err) {
                console.warn('heic2any conversion fallback:', err);
            }
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            rawPhotoBase64 = e.target.result;
            cropImg.onload = () => {
                imgNaturalW = cropImg.naturalWidth;
                imgNaturalH = cropImg.naturalHeight;
                currentZoom = 1.0;
                if (zoomRange) {
                    zoomRange.value = '1';
                    zoomRange.disabled = false;
                }
                if (zoomLabel) zoomLabel.textContent = '1.0x';
                if (dropzone) dropzone.style.display = 'none';
                if (studio) studio.style.display = 'block';
                if (btnCancelNewFile) btnCancelNewFile.style.display = existingPhotoUrl ? 'inline-flex' : 'none';
                centerImage();
            };
            cropImg.src = e.target.result;
        };
        reader.readAsDataURL(displayBlob);
    }

    // Initialize with existing photo if member already has one
    if (existingPhotoUrl) {
        loadExistingUrl(existingPhotoUrl);
    }

    if (btnTriggerChange && fileInput) {
        btnTriggerChange.addEventListener('click', () => fileInput.click());
    }

    if (btnCancelNewFile) {
        btnCancelNewFile.addEventListener('click', () => {
            fileInput.value = '';
            if (base64Input) base64Input.value = '';
            originalFile = null;
            rawPhotoBase64 = null;
            hasNewCrop = false;
            if (existingPhotoUrl) {
                loadExistingUrl(existingPhotoUrl);
            } else {
                if (studio) studio.style.display = 'none';
                if (dropzone) dropzone.style.display = 'flex';
            }
        });
    }

    if (removePhotoCb) {
        removePhotoCb.addEventListener('change', function () {
            if (this.checked) {
                if (viewport) viewport.style.opacity = '0.35';
                if (zoomRange) zoomRange.disabled = true;
                if (btnReset) btnReset.disabled = true;
                if (btnTriggerChange) btnTriggerChange.disabled = true;
            } else {
                if (viewport) viewport.style.opacity = '1';
                if (zoomRange) zoomRange.disabled = false;
                if (btnReset) btnReset.disabled = false;
                if (btnTriggerChange) btnTriggerChange.disabled = false;
            }
        });
    }

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover'].forEach(evt => {
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzone.style.borderColor = 'var(--forest)';
                dropzone.style.backgroundColor = '#eaf3dc';
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzone.style.borderColor = 'var(--line)';
                dropzone.style.backgroundColor = 'var(--cream)';
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                const file = dt.files[0];
                fileInput.files = dt.files;
                loadFile(file);
            }
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0];
            if (file) loadFile(file);
        });
    }

    // Drag to Pan
    if (viewport) {
        const onDragStart = (clientX, clientY) => {
            if (removePhotoCb && removePhotoCb.checked) return;
            isDragging = true;
            startX = clientX;
            startY = clientY;
            startOffsetX = currentOffsetX;
            startOffsetY = currentOffsetY;
        };

        const onDragMove = (clientX, clientY) => {
            if (!isDragging) return;
            const deltaX = clientX - startX;
            const deltaY = clientY - startY;
            if (Math.abs(deltaX) > 2 || Math.abs(deltaY) > 2) {
                hasNewCrop = true;
            }
            currentOffsetX = startOffsetX + deltaX;
            currentOffsetY = startOffsetY + deltaY;
            clampOffsets();
            updateImageStyle();
        };

        const onDragEnd = () => { isDragging = false; };

        viewport.addEventListener('mousedown', (e) => onDragStart(e.clientX, e.clientY));
        window.addEventListener('mousemove', (e) => onDragMove(e.clientX, e.clientY));
        window.addEventListener('mouseup', onDragEnd);

        viewport.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) onDragStart(e.touches[0].clientX, e.touches[0].clientY);
        }, { passive: true });
        window.addEventListener('touchmove', (e) => {
            if (isDragging && e.touches.length === 1) onDragMove(e.touches[0].clientX, e.touches[0].clientY);
        }, { passive: true });
        window.addEventListener('touchend', onDragEnd);
    }

    // Zoom Slider
    if (zoomRange) {
        zoomRange.addEventListener('input', (e) => {
            currentZoom = parseFloat(e.target.value);
            if (zoomLabel) zoomLabel.textContent = `${currentZoom.toFixed(1)}x`;
            hasNewCrop = true;
            clampOffsets();
            updateImageStyle();
        });
    }

    if (btnReset) {
        btnReset.addEventListener('click', () => {
            currentZoom = 1.0;
            if (zoomRange) zoomRange.value = '1';
            if (zoomLabel) zoomLabel.textContent = '1.0x';
            centerImage();
            hasNewCrop = true;
        });
    }

    // Export cropped canvas to blob
    function getCroppedBlob() {
        return new Promise((resolve, reject) => {
            try {
                const { scale } = getDimensions();
                const cropX = -currentOffsetX / scale;
                const cropY = -currentOffsetY / scale;
                const cropW = vpSize / scale;
                const cropH = vpSize / scale;

                const targetSize = Math.min(1200, Math.max(480, Math.round(cropW)));
                const canvas = document.createElement('canvas');
                canvas.width = targetSize;
                canvas.height = targetSize;
                const ctx = canvas.getContext('2d');

                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(cropImg, cropX, cropY, cropW, cropH, 0, 0, targetSize, targetSize);

                const mimeType = (originalFile && originalFile.type && originalFile.type.startsWith('image/'))
                    ? originalFile.type
                    : 'image/jpeg';
                canvas.toBlob((blob) => {
                    if (blob) resolve(blob);
                    else reject(new Error('Canvas export failed'));
                }, mimeType.includes('png') ? 'image/png' : 'image/jpeg', 0.90);
            } catch (err) {
                reject(err);
            }
        });
    }

    // Form submission
    let isSubmitting = false;
    if (form) {
        form.addEventListener('submit', async (e) => {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            // If user checked remove photo, no need to crop
            if (removePhotoCb && removePhotoCb.checked) {
                if (base64Input) base64Input.value = '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Updating...';
                    submitBtn.style.opacity = '0.7';
                }
                isSubmitting = true;
                return;
            }

            if (hasNewCrop) {
                e.preventDefault();
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Processing photo...';
                    submitBtn.style.opacity = '0.7';
                }
                try {
                    const blob = await getCroppedBlob();
                    const base64Data = await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.onerror = reject;
                        reader.readAsDataURL(blob);
                    });
                    if (base64Input) {
                        base64Input.value = base64Data;
                    }
                    const rawInput = document.getElementById('edit-raw-photo-base64');
                    if (rawInput) rawInput.value = rawPhotoBase64 || '';
                    const zoomInput = document.getElementById('edit-crop-zoom');
                    if (zoomInput) zoomInput.value = currentZoom.toFixed(2);
                    const offsetXInput = document.getElementById('edit-crop-offset-x');
                    if (offsetXInput) offsetXInput.value = Math.round(currentOffsetX);
                    const offsetYInput = document.getElementById('edit-crop-offset-y');
                    if (offsetYInput) offsetYInput.value = Math.round(currentOffsetY);
                    if (fileInput) {
                        fileInput.value = '';
                    }
                    isSubmitting = true;
                    if (submitBtn) submitBtn.textContent = 'Updating...';
                    form.submit();
                } catch (err) {
                    console.warn('Canvas crop fallback, submitting raw file:', err);
                    isSubmitting = true;
                    if (submitBtn) submitBtn.textContent = 'Updating...';
                    form.submit();
                }
            } else {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Updating...';
                    submitBtn.style.opacity = '0.7';
                }
                isSubmitting = true;
            }
        });
    }
});
</script>
@endpush


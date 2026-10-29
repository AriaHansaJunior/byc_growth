@extends('layouts.app')

@section('title', 'Member Profile — BYC GROWTH')

@section('content')
@php
    $member = $member ?? $user->member;
    $hasPhoto = $member && !empty($member->photo_url);
@endphp
<div class="page-shell" style="padding-top: 24px; padding-bottom: 80px;">
    {{-- Header --}}
    <header class="page-header" style="margin-bottom: 32px;">
        <span class="eyebrow" style="color: var(--forest); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">
            Account Settings
        </span>
        <h1 style="margin: 0 0 10px; font-family: 'Manrope', sans-serif; font-weight: 800; font-size: clamp(32px, 3.5vw, 44px); letter-spacing: -0.03em; color: var(--ink);">
            Member Profile
        </h1>
        <p style="margin: 0; color: var(--muted); font-size: 16px; max-width: 780px; line-height: 1.5;">
            Manage your personal profile photo, login email address, and security password.
        </p>
    </header>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert-success-box" style="margin-bottom: 26px; padding: 14px 20px; background: #eaf3dc; border: 1px solid var(--lime); border-radius: 14px; color: var(--forest-dark); font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 18px;">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger-box" style="margin-bottom: 26px; padding: 14px 20px; background: #fdf0ee; border: 1px solid var(--red); border-radius: 14px; color: var(--red); font-weight: 600;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Profile Form --}}
    <form method="POST" action="{{ route('profile.update') }}" id="form-profile" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        {{-- 2-Column Full-Width Aligned Grid (Matches Navbar Margins, Equal Height Cards) --}}
        <div class="profile-layout-grid" style="display: grid; grid-template-columns: 380px 1fr; gap: 36px; align-items: stretch;">
            
            {{-- Left Column: Identity & Interactive Photo Cropper Card (Stretches to match right card) --}}
            <div class="profile-identity-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 36px 30px; box-shadow: var(--shadow); text-align: center; display: flex; flex-direction: column; align-items: center; height: 100%; box-sizing: border-box;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 20px; display: block;">
                    Community Identity
                </span>

                {{-- Default Photo View (Shown when not cropping) --}}
                <div id="profile-photo-static-wrap" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <div class="profile-photo-frame" style="width: 180px; height: 180px; border-radius: 50%; overflow: hidden; background: var(--cream); border: 3px solid var(--forest); box-shadow: 0 8px 26px rgba(44, 76, 56, 0.16); position: relative; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <img
                            id="profile-photo-preview"
                            src="{{ $hasPhoto ? $member->photo_url : '' }}"
                            alt="{{ $member ? $member->full_name : $user->name }}"
                            style="{{ $hasPhoto ? 'display: block;' : 'display: none;' }} width: 100%; height: 100%; object-fit: cover;"
                        >
                        <div
                            id="profile-photo-fallback"
                            style="{{ $hasPhoto ? 'display: none;' : 'display: flex;' }} width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 64px; font-weight: 800; color: var(--white); background: linear-gradient(135deg, var(--forest), var(--lime)); font-family: 'Manrope', sans-serif;"
                        >
                            {{ strtoupper(substr($member ? $member->full_name : $user->name, 0, 1)) }}
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <button type="button" id="btn-trigger-photo-picker" class="button button-secondary button-sm" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 20px; font-weight: 700; border-radius: 999px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                            <span>Change Photo</span>
                        </button>
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 8px;">
                            JPG, PNG, or WEBP (Max 5MB)
                        </small>
                    </div>
                </div>

                {{-- Interactive Crop / Pan & Zoom Studio (Shown after file is selected) --}}
                <div id="profile-crop-studio" style="display: none; width: 100%; margin-bottom: 20px;">
                    <div class="crop-viewport-wrap" style="position: relative; width: 180px; height: 180px; margin: 0 auto 12px; border-radius: 50%; overflow: hidden; border: 3px solid var(--forest); box-shadow: 0 8px 26px rgba(44, 76, 56, 0.2); background: #141f17; cursor: grab; user-select: none; touch-action: none;" id="profile-crop-viewport">
                        <img id="profile-crop-img" alt="Crop Preview" src="" style="position: absolute; top: 0; left: 0; pointer-events: none; will-change: transform, left, top, width, height;">
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
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; max-width: 300px; margin: 0 auto 10px; background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 6px 12px;">
                        <span id="profile-crop-zoom-label" style="font-size: 11.5px; font-weight: 700; color: var(--ink); min-width: 32px;">1.0x</span>
                        <input type="range" id="profile-crop-zoom-range" min="1" max="2.5" step="0.05" value="1" style="flex: 1; accent-color: var(--forest); cursor: pointer; height: 5px;">
                        <button type="button" id="btn-crop-reset" class="button button-ghost button-sm" style="padding: 2px 8px; height: 24px; font-size: 11px;">Reset</button>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <button type="button" id="btn-crop-cancel" class="button button-ghost button-sm" style="padding: 4px 12px; font-size: 12px; height: 30px; color: var(--red);">
                            Cancel
                        </button>
                        <button type="button" id="btn-crop-change-file" class="button button-ghost button-sm" style="padding: 4px 12px; font-size: 12px; height: 30px;">
                            Choose Another Photo
                        </button>
                    </div>
                </div>

                {{-- Hidden Inputs for Upload & Cropped Base64 --}}
                <input
                    type="file"
                    name="photo"
                    id="profile-photo-input"
                    accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/heic,image/heif,.heic,.heif"
                    style="display: none;"
                >
                <input
                    type="hidden"
                    name="photo_base64"
                    id="profile-photo-base64"
                    value=""
                >

                {{-- Member Full Name --}}
                <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; font-weight: 800; color: var(--ink); margin: 0 0 6px; line-height: 1.3;">
                    {{ $member ? $member->full_name : $user->name }}
                    <span style="font-size: 13.5px; font-weight: 700; color: var(--forest); display: block; margin-top: 2px;">(yourself)</span>
                </h2>

                {{-- User Badge & Role --}}
                <div style="display: flex; flex-direction: column; align-items: center; gap: 6px; margin-bottom: 22px;">
                    <span style="display: inline-flex; align-items: center; gap: 5px; background: var(--paper); border: 1px solid var(--line); padding: 4px 14px; border-radius: 12px; font-size: 13px; font-weight: 700; color: var(--forest);">
                        <span>@</span>{{ $user->username }}
                    </span>
                    <span style="font-size: 13px; color: var(--muted); font-weight: 600;">
                        {{ $user->isAdmin() ? 'Administrator' : 'Fellowship Member' }}
                    </span>
                </div>

                {{-- Details Section (Birthday & Status ONLY - Pinned to bottom of equal-height card) --}}
                <div class="profile-identity-details" style="width: 100%; border-top: 1px solid var(--line); padding-top: 20px; margin-top: auto; display: flex; flex-direction: column; gap: 14px; text-align: left; font-size: 13.5px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--muted); font-weight: 600;">Birthday</span>
                        <span style="color: var(--ink); font-weight: 700;">
                            {{ $member && $member->date_of_birth ? $member->date_of_birth->format('F j, Y') : 'Not set' }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--muted); font-weight: 600;">Community Status</span>
                        <span style="color: var(--forest); font-weight: 700; background: #eaf3dc; padding: 2px 10px; border-radius: 6px; font-size: 12px;">
                            Active Member
                        </span>
                    </div>
                </div>
            </div>

            {{-- Right Column: Account & Security Form Card --}}
            <div class="profile-card-right" style="background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 38px 44px; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 30px;">
                
                {{-- Section 1: Account Credentials --}}
                <div>
                    <div style="border-bottom: 1px solid var(--line); padding-bottom: 14px; margin-bottom: 22px;">
                        <h3 style="font-family: 'Manrope', sans-serif; font-size: 19px; font-weight: 800; color: var(--ink); margin: 0 0 4px;">
                            Account Credentials
                        </h3>
                        <p style="margin: 0; color: var(--muted); font-size: 14px;">
                            Your login email address and permanent community identifiers.
                        </p>
                    </div>

                    {{-- Identifiers: Exactly 50% / 50% Equal Columns --}}
                    <div class="profile-parallel-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 22px;">
                        {{-- Read-Only Username --}}
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); margin-bottom: 6px;">
                                Account Name (Username)
                            </label>
                            <input
                                type="text"
                                value="{{ $user->username }}"
                                readonly
                                disabled
                                style="width: 100%; height: 46px; min-height: 46px; padding: 11px 16px; border: 1px solid var(--line); border-radius: 12px; background: var(--paper); color: var(--muted); font-family: inherit; font-size: 14px; cursor: not-allowed; box-sizing: border-box;"
                            >
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 5px;">
                                Unique identifier across BYC Growth.
                            </small>
                        </div>

                        {{-- Read-Only Full Name --}}
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); margin-bottom: 6px;">
                                Full Name
                            </label>
                            <input
                                type="text"
                                value="{{ $member ? $member->full_name : $user->name }}"
                                readonly
                                disabled
                                style="width: 100%; height: 46px; min-height: 46px; padding: 11px 16px; border: 1px solid var(--line); border-radius: 12px; background: var(--paper); color: var(--muted); font-family: inherit; font-size: 14px; cursor: not-allowed; box-sizing: border-box;"
                            >
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 5px;">
                                Managed through Fellowship Directory.
                            </small>
                        </div>
                    </div>

                    {{-- Editable Email Address (Full Width) --}}
                    <div>
                        <label for="profile-email" style="display: block; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); margin-bottom: 6px;">
                            Email Address <span style="color: var(--red);">*</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="profile-email"
                            value="{{ old('email', $user->email) }}"
                            required
                            style="width: 100%; height: 46px; min-height: 46px; padding: 11px 16px; border: 1.5px solid var(--line); border-radius: 12px; background: var(--white); color: var(--ink); font-family: inherit; font-size: 14px; outline: none; transition: border-color 0.2s; box-sizing: border-box;"
                        >
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 5px;">
                            Used for signing in and receiving community notifications.
                        </small>
                    </div>
                </div>

                {{-- Section 2: Password Settings --}}
                <div style="background: var(--paper); border: 1px solid var(--line); border-radius: 20px; padding: 26px 28px; box-sizing: border-box;">
                    <div style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 18px;">
                        <h4 style="font-family: 'Manrope', sans-serif; font-size: 16.5px; font-weight: 800; color: var(--ink); margin: 0 0 4px;">
                            Password Settings
                        </h4>
                        <p style="margin: 0; font-size: 13px; color: var(--muted);">
                            To update your password, enter a new password below. If you wish to keep your current password, leave the new password field empty.
                        </p>
                    </div>

                    {{-- Password Fields: Exactly 50% / 50% Equal Columns Matching Row Above --}}
                    <div class="profile-parallel-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px;">
                        {{-- Current Password (Masked Display) --}}
                        <div>
                            <label for="profile-current-password" style="display: block; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); margin-bottom: 6px;">
                                Current Password
                            </label>
                            <div class="password-field-wrapper" style="position: relative;">
                                <input
                                    type="password"
                                    id="profile-current-password"
                                    value="••••••••••••"
                                    readonly
                                    disabled
                                    style="width: 100%; height: 46px; min-height: 46px; padding: 11px 44px 11px 16px; border: 1px solid var(--line); border-radius: 12px; background: #ebe6d9; color: var(--muted); font-family: inherit; font-size: 14px; cursor: not-allowed; box-sizing: border-box;"
                                >
                                <button
                                    type="button"
                                    class="btn-toggle-password"
                                    data-target="profile-current-password"
                                    title="Show password"
                                    aria-label="Show password"
                                    style="right: 12px;"
                                >
                                    <span class="icon-eye"><x-icon name="eye" /></span>
                                    <span class="icon-eye-off" style="display: none;"><x-icon name="eye-off" /></span>
                                </button>
                            </div>
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 5px;">
                                Currently active and securely encrypted.
                            </small>
                        </div>

                        {{-- New Password (Spacious Input) --}}
                        <div>
                            <label for="profile-new-password" style="display: block; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); margin-bottom: 6px;">
                                New Password
                            </label>
                            <div class="password-field-wrapper" style="position: relative;">
                                <input
                                    type="password"
                                    name="new_password"
                                    id="profile-new-password"
                                    placeholder="Enter new password (optional)"
                                    autocomplete="new-password"
                                    style="width: 100%; height: 46px; min-height: 46px; padding: 11px 44px 11px 16px; border: 1.5px solid var(--line); border-radius: 12px; background: var(--white); color: var(--ink); font-family: inherit; font-size: 14px; outline: none; box-sizing: border-box;"
                                >
                                <button
                                    type="button"
                                    class="btn-toggle-password"
                                    data-target="profile-new-password"
                                    title="Show password"
                                    aria-label="Show password"
                                    style="right: 12px;"
                                >
                                    <span class="icon-eye"><x-icon name="eye" /></span>
                                    <span class="icon-eye-off" style="display: none;"><x-icon name="eye-off" /></span>
                                </button>
                            </div>
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 5px;">
                                Minimum 6 characters. Leave blank to keep current.
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="profile-form-actions" style="display: flex; justify-content: flex-end; align-items: center; gap: 16px; padding-top: 6px;">
                    <a href="{{ route('members') }}" class="button button-ghost" style="font-weight: 700; text-decoration: none; padding: 12px 26px; height: 44px; display: inline-flex; align-items: center;">
                        Cancel
                    </a>
                    <button type="button" id="btn-submit-profile" class="button button-primary" style="padding: 12px 32px; font-weight: 800; font-size: 15px; height: 44px; display: inline-flex; align-items: center;">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Save Changes Confirmation Modal --}}
<div class="game-modal-overlay" id="modal-confirm-profile" role="dialog" aria-modal="true" aria-labelledby="modal-confirm-profile-title" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(32, 48, 41, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="game-modal" style="max-width: 440px; width: 92%; background: var(--white); border: 2px solid var(--forest); border-radius: 22px; padding: 30px 26px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
        <h3 id="modal-confirm-profile-title" style="font-family: 'Manrope', sans-serif; font-size: 19px; font-weight: 800; color: var(--ink); margin: 0 0 10px;">
            Confirm Profile Changes
        </h3>
        <p style="color: var(--muted); font-size: 14px; line-height: 1.5; margin: 0 0 24px;">
            Are you sure you want to save these changes to your profile?
        </p>
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <button type="button" id="btn-cancel-modal" class="button button-secondary" style="padding: 10px 20px;">
                Cancel
            </button>
            <button type="button" id="btn-confirm-save" class="button button-primary" style="padding: 10px 24px;">
                Confirm Changes
            </button>
        </div>
    </div>
</div>

<style>
.profile-layout-grid {
    align-items: stretch !important;
}
.profile-identity-card {
    height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
}
.profile-identity-details {
    margin-top: auto !important;
}
.crop-viewport-wrap:active {
    cursor: grabbing !important;
}
.crop-viewport-wrap:active .crop-grid-overlay {
    opacity: 0.65 !important;
}
@media (max-width: 960px) {
    .profile-layout-grid {
        grid-template-columns: 1fr !important;
        gap: 28px !important;
    }
    .profile-card-right {
        padding: 24px 20px !important;
    }
    .profile-parallel-grid {
        grid-template-columns: 1fr !important;
        gap: 18px !important;
    }
}
</style>

<script src="{{ asset('assets/js/heic2any.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const submitBtn = document.getElementById('btn-submit-profile');
    const confirmModal = document.getElementById('modal-confirm-profile');
    const cancelModalBtn = document.getElementById('btn-cancel-modal');
    const confirmSaveBtn = document.getElementById('btn-confirm-save');
    const profileForm = document.getElementById('form-profile');
    const emailInput = document.getElementById('profile-email');
    const newPasswordInput = document.getElementById('profile-new-password');

    // Cropper elements
    const fileInput = document.getElementById('profile-photo-input');
    const triggerBtn = document.getElementById('btn-trigger-photo-picker');
    const staticWrap = document.getElementById('profile-photo-static-wrap');
    const studioWrap = document.getElementById('profile-crop-studio');
    const viewport = document.getElementById('profile-crop-viewport');
    const cropImg = document.getElementById('profile-crop-img');
    const zoomRange = document.getElementById('profile-crop-zoom-range');
    const zoomLabel = document.getElementById('profile-crop-zoom-label');
    const btnReset = document.getElementById('btn-crop-reset');
    const btnChangeFile = document.getElementById('btn-crop-change-file');
    const btnCancelCrop = document.getElementById('btn-crop-cancel');

    let originalFile = null;
    let imgNaturalW = 0;
    let imgNaturalH = 0;
    let currentZoom = 1.0;
    let currentOffsetX = 0;
    let currentOffsetY = 0;
    const viewportSize = 180;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startOffsetX = 0;
    let startOffsetY = 0;
    let hasNewCrop = false;

    if (triggerBtn && fileInput) {
        triggerBtn.addEventListener('click', () => fileInput.click());
    }
    if (btnChangeFile && fileInput) {
        btnChangeFile.addEventListener('click', () => fileInput.click());
    }

    function getDimensions() {
        const vpSize = (viewport && viewport.clientWidth) ? viewport.clientWidth : viewportSize;
        const baseScale = Math.max(vpSize / (imgNaturalW || 1), vpSize / (imgNaturalH || 1));
        const scale = baseScale * currentZoom;
        const displayW = imgNaturalW * scale;
        const displayH = imgNaturalH * scale;

        const minOffsetX = vpSize - displayW;
        const maxOffsetX = 0;
        const minOffsetY = vpSize - displayH;
        const maxOffsetY = 0;

        return { scale, displayW, displayH, minOffsetX, maxOffsetX, minOffsetY, maxOffsetY, vpSize };
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
        const { displayW, displayH, vpSize } = getDimensions();
        currentOffsetX = (vpSize - displayW) / 2;
        currentOffsetY = (vpSize - displayH) / 2;
        clampOffsets();
        updateImageStyle();
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
            cropImg.onload = () => {
                imgNaturalW = cropImg.naturalWidth;
                imgNaturalH = cropImg.naturalHeight;
                currentZoom = 1.0;
                if (zoomRange) zoomRange.value = '1';
                if (zoomLabel) zoomLabel.textContent = '1.0x';
                staticWrap.style.display = 'none';
                studioWrap.style.display = 'block';
                centerImage();
            };
            cropImg.onerror = () => {
                // If browser does not natively decode HEIC in <img> (non-Safari),
                // gracefully leave static display and allow saving original file directly
                staticWrap.style.display = 'flex';
                studioWrap.style.display = 'none';
                hasNewCrop = false;
            };
            cropImg.src = e.target.result;
        };
        reader.readAsDataURL(displayBlob);
    }

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0];
            if (file) {
                loadFile(file);
            }
        });
    }

    // Drag to Pan
    if (viewport) {
        viewport.addEventListener('pointerdown', (e) => {
            if (!imgNaturalW) return;
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startOffsetX = currentOffsetX;
            startOffsetY = currentOffsetY;
            viewport.setPointerCapture(e.pointerId);
        });

        viewport.addEventListener('pointermove', (e) => {
            if (!isDragging) return;
            currentOffsetX = startOffsetX + (e.clientX - startX);
            currentOffsetY = startOffsetY + (e.clientY - startY);
            clampOffsets();
            updateImageStyle();
        });

        const endDrag = (e) => {
            if (isDragging) {
                isDragging = false;
                try { viewport.releasePointerCapture(e.pointerId); } catch(err) {}
            }
        };
        viewport.addEventListener('pointerup', endDrag);
        viewport.addEventListener('pointercancel', endDrag);
    }

    // Zoom Slider
    if (zoomRange) {
        zoomRange.addEventListener('input', () => {
            currentZoom = parseFloat(zoomRange.value);
            if (zoomLabel) zoomLabel.textContent = `${currentZoom.toFixed(1)}x`;
            clampOffsets();
            updateImageStyle();
        });
    }

    // Reset Zoom
    if (btnReset) {
        btnReset.addEventListener('click', () => {
            currentZoom = 1.0;
            if (zoomRange) zoomRange.value = '1';
            if (zoomLabel) zoomLabel.textContent = '1.0x';
            centerImage();
        });
    }

    // Cancel Crop
    if (btnCancelCrop) {
        btnCancelCrop.addEventListener('click', () => {
            hasNewCrop = false;
            originalFile = null;
            fileInput.value = '';
            cropImg.src = '';
            studioWrap.style.display = 'none';
            staticWrap.style.display = 'flex';
        });
    }

    // Generate Cropped Blob
    function getCroppedBlob() {
        return new Promise((resolve, reject) => {
            try {
                const { scale, vpSize } = getDimensions();
                const cropX = -currentOffsetX / scale;
                const cropY = -currentOffsetY / scale;
                const cropW = vpSize / scale;
                const cropH = vpSize / scale;

                const targetSize = Math.min(1200, Math.max(500, Math.round(cropW)));
                const canvas = document.createElement('canvas');
                canvas.width = targetSize;
                canvas.height = targetSize;
                const ctx = canvas.getContext('2d');

                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(cropImg, cropX, cropY, cropW, cropH, 0, 0, targetSize, targetSize);

                const mimeType = (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg';
                canvas.toBlob((blob) => {
                    if (blob) resolve(blob);
                    else reject(new Error('Canvas toBlob failed'));
                }, mimeType, 0.9);
            } catch (err) {
                reject(err);
            }
        });
    }

    function setFieldError(field, message) {
        if (!field) return;
        clearFieldError(field);
        field.classList.add('input-invalid');
        const errorEl = document.createElement('div');
        errorEl.className = 'form-field-error';
        errorEl.setAttribute('data-for', field.id || field.name);
        errorEl.textContent = message;
        const target = field.closest('.password-field-wrapper') || field;
        target.insertAdjacentElement('afterend', errorEl);
    }

    function clearFieldError(field) {
        if (!field) return;
        field.classList.remove('input-invalid');
        const parent = field.closest('div')?.parentElement || field.parentElement;
        if (parent) {
            parent.querySelectorAll(`.form-field-error[data-for="${field.id || field.name}"]`).forEach(el => el.remove());
        }
        const target = field.closest('.password-field-wrapper') || field;
        if (target.nextElementSibling && target.nextElementSibling.classList.contains('form-field-error')) {
            target.nextElementSibling.remove();
        }
    }

    if (emailInput) {
        emailInput.addEventListener('input', () => clearFieldError(emailInput));
    }
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', () => clearFieldError(newPasswordInput));
    }

    if (submitBtn && confirmModal && profileForm) {
        submitBtn.addEventListener('click', (e) => {
            e.preventDefault();
            let hasError = false;

            if (emailInput) {
                const emailVal = emailInput.value.trim();
                if (!emailVal) {
                    setFieldError(emailInput, 'Email address is required.');
                    hasError = true;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                    setFieldError(emailInput, 'Please enter a valid email address.');
                    hasError = true;
                }
            }

            if (newPasswordInput && newPasswordInput.value.length > 0) {
                if (newPasswordInput.value.length < 6) {
                    setFieldError(newPasswordInput, 'New password must be at least 6 characters.');
                    hasError = true;
                }
            }

            if (hasError) return;

            confirmModal.style.display = 'flex';
        });

        if (cancelModalBtn) {
            cancelModalBtn.addEventListener('click', () => {
                confirmModal.style.display = 'none';
            });
        }

        if (confirmSaveBtn) {
            confirmSaveBtn.addEventListener('click', async () => {
                confirmSaveBtn.disabled = true;
                confirmSaveBtn.textContent = 'Saving...';

                // If a photo was cropped, extract the cropped image as Base64 Data URI
                if (hasNewCrop) {
                    try {
                        const blob = await getCroppedBlob();
                        const base64Data = await new Promise((resolve, reject) => {
                            const reader = new FileReader();
                            reader.onload = () => resolve(reader.result);
                            reader.onerror = reject;
                            reader.readAsDataURL(blob);
                        });

                        const base64Input = document.getElementById('profile-photo-base64');
                        if (base64Input) {
                            base64Input.value = base64Data;
                        }

                        // Also attach to file input for compatibility
                        try {
                            const croppedFile = new File([blob], originalFile ? originalFile.name : 'avatar.jpg', {
                                type: blob.type || 'image/jpeg',
                                lastModified: Date.now()
                            });
                            if (window.DataTransfer) {
                                const dt = new DataTransfer();
                                dt.items.add(croppedFile);
                                fileInput.files = dt.files;
                            }
                        } catch (e) {
                            // File input fallback ignored since base64 input is populated
                        }
                    } catch (err) {
                        console.error('Failed to crop photo, uploading original:', err);
                    }
                }

                profileForm.submit();
            });
        }
    }
});
</script>
@endsection

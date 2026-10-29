@extends('layouts.admin')

@section('title', 'Add New Member — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow" style="color: var(--forest); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; font-size: 11.5px; display: block; margin-bottom: 4px;">
            Community Directory
        </span>
        <h1 style="font: 800 28px 'Manrope', sans-serif; color: var(--ink); margin: 0 0 6px;">Add New Member</h1>
        <p style="color: var(--muted); font-size: 14.5px; margin: 0; max-width: 680px; line-height: 1.5;">
            Register a new fellowship member into the community directory, set their birthday for celebrations, and create or link their user login account.
        </p>
    </div>
    <div class="admin-header-actions">
    </div>
</div>
@endsection

@section('content')
<div class="member-create-wrapper" style="max-width: 1200px; margin: 0 auto; padding-bottom: 40px;">
    <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data" id="form-create-member" novalidate>
        @csrf

        {{-- 2-Column Responsive Grid --}}
        <div class="member-create-grid" style="display: grid; grid-template-columns: 380px 1fr; gap: 32px; align-items: stretch;">

            {{-- Left Column: Member Photo & Studio Card --}}
            <div class="member-photo-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 36px 30px; box-shadow: var(--shadow); text-align: center; display: flex; flex-direction: column; align-items: center; height: 100%; box-sizing: border-box;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 22px; display: block;">
                    Profile Photo
                </span>

                {{-- Empty Dropzone (Initial state) --}}
                <div id="create-photo-dropzone" class="crop-dropzone" style="height: 190px; width: 190px; margin: 0 auto 18px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 18px; border-radius: 50%; cursor: pointer; border: 2px dashed var(--line); background: var(--cream); transition: border-color 0.2s, background-color 0.2s; box-sizing: border-box;">
                    <div style="font-size: 42px; margin-bottom: 6px; line-height: 1;">👤</div>
                    <div style="font-weight: 700; color: var(--ink); font-size: 14px; margin-bottom: 3px;">Choose Photo</div>
                    <small style="color: var(--muted); font-size: 11px; display: block; line-height: 1.2;">Click or drag photo</small>
                </div>

                {{-- Interactive Crop Studio (Revealed on file selection) --}}
                <div id="create-photo-studio" style="display: none; width: 100%; margin-bottom: 18px;">
                    <div class="crop-viewport-wrap" style="position: relative; width: 190px; height: 190px; margin: 0 auto 12px; border-radius: 50%; overflow: hidden; border: 3px solid var(--forest); box-shadow: 0 8px 26px rgba(44, 76, 56, 0.2); background: #141f17; cursor: grab; user-select: none; touch-action: none;" id="create-crop-viewport">
                        <img id="create-crop-img" alt="Crop Preview" src="" style="position: absolute; top: 0; left: 0; pointer-events: none; will-change: transform, left, top, width, height;">
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
                        <span id="create-crop-zoom-label" style="font-size: 11.5px; font-weight: 700; color: var(--ink); min-width: 32px;">1.0x</span>
                        <input type="range" id="create-crop-zoom-range" min="1" max="2.5" step="0.05" value="1" style="flex: 1; accent-color: var(--forest); cursor: pointer; height: 5px;">
                        <button type="button" id="btn-create-crop-reset" class="button button-ghost button-sm" style="padding: 2px 8px; height: 24px; font-size: 11px;">Reset</button>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <button type="button" id="btn-create-cancel-photo" class="button button-ghost button-sm" style="padding: 4px 12px; font-size: 12px; height: 30px; color: var(--red);">
                            Cancel
                        </button>
                        <button type="button" id="btn-create-choose-diff" class="button button-ghost button-sm" style="padding: 4px 12px; font-size: 12px; height: 30px;">
                            Choose Another Photo
                        </button>
                    </div>
                </div>

                {{-- Hidden Inputs for Upload & Cropped Base64 --}}
                <input
                    type="file"
                    name="photo"
                    id="create-photo-input"
                    accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/heic,image/heif,.heic,.heif"
                    style="display: none;"
                >
                <input
                    type="hidden"
                    name="photo_base64"
                    id="create-photo-base64"
                    value=""
                >

                {{-- Helper Note (Pinned to bottom of left card) --}}
                <div style="width: 100%; border-top: 1px solid var(--line); padding-top: 18px; margin-top: auto; text-align: left; font-size: 12px; color: var(--muted); line-height: 1.5;">
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <span style="font-size: 14px; line-height: 1; flex-shrink: 0; margin-top: 2px;">💡</span>
                        <span>
                            Supports <strong>JPG, PNG, WEBP, GIF, and iPhone HEIC</strong> (Max 50MB). Photos are automatically web-optimized while maintaining crisp quality.
                        </span>
                    </div>
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
                            <label for="create-full-name" class="form-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                                Full Name <span style="color: var(--red);">*</span>
                            </label>
                            <input
                                type="text"
                                name="full_name"
                                id="create-full-name"
                                class="form-input @error('full_name') input-invalid @enderror"
                                value="{{ old('full_name') }}"
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
                            <label for="create-dob" class="form-label" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); display: block; margin-bottom: 6px;">
                                Date of Birth
                            </label>
                            <input
                                type="date"
                                name="date_of_birth"
                                id="create-dob"
                                max="{{ date('Y-m-d') }}"
                                class="form-input @error('date_of_birth') input-invalid @enderror"
                                value="{{ old('date_of_birth') }}"
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
                            Every member requires a user account to log in and interact with community features.
                        </p>
                    </div>

                    {{-- Account Mode Selector: New vs Existing --}}
                    <div style="display: flex; gap: 18px; margin-bottom: 18px; flex-wrap: wrap;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: var(--ink);">
                            <input type="radio" name="account_mode" id="mode-create-new" value="new" checked style="accent-color: var(--forest); width: 16px; height: 16px;">
                            <span>Create New Account (Recommended)</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: var(--ink);">
                            <input type="radio" name="account_mode" id="mode-link-existing" value="link" style="accent-color: var(--forest); width: 16px; height: 16px;">
                            <span>Link to Existing Account</span>
                        </label>
                    </div>

                    {{-- Block A: Create New Account Form Fields --}}
                    <div id="block-new-account" style="display: flex; flex-direction: column; gap: 14px;">
                        {{-- Email Address --}}
                        <div class="form-group" style="margin: 0;">
                            <label for="create-user-email" class="form-label" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                Account Email <span style="color: var(--red);">*</span>
                            </label>
                            <input
                                type="email"
                                name="new_user_email"
                                id="create-user-email"
                                class="form-input @error('new_user_email') input-invalid @enderror"
                                value="{{ old('new_user_email') }}"
                                placeholder="e.g. member@bycgrowth.org"
                                style="width: 100%; height: 42px; padding: 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                            >
                            <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px;">
                                Used for signing in to the member portal.
                            </small>
                            @error('new_user_email')
                                <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Username & Password Row --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;" class="member-account-row">
                            {{-- Username --}}
                            <div>
                                <label for="create-user-username" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                    Username (Optional)
                                </label>
                                <input
                                    type="text"
                                    name="new_user_username"
                                    id="create-user-username"
                                    class="form-input @error('new_user_username') input-invalid @enderror"
                                    value="{{ old('new_user_username') }}"
                                    placeholder="Auto-generated if blank"
                                    style="width: 100%; height: 42px; padding: 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                >
                                @error('new_user_username')
                                    <div class="form-field-error" style="color: var(--red); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div>
                                <label for="create-user-password" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 4px;">
                                    Initial Password
                                </label>
                                <div class="password-field-wrapper" style="position: relative;">
                                    <input
                                        type="password"
                                        name="new_user_password"
                                        id="create-user-password"
                                        class="form-input @error('new_user_password') input-invalid @enderror"
                                        value="{{ old('new_user_password', 'password123') }}"
                                        placeholder="password123"
                                        style="width: 100%; height: 42px; padding: 8px 40px 8px 12px; font-size: 13.5px; border-radius: 10px; box-sizing: border-box;"
                                    >
                                    <button
                                        type="button"
                                        class="btn-toggle-password"
                                        data-target="create-user-password"
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

                    {{-- Block B: Link Existing User Account (Hidden by default) --}}
                    <div id="block-link-account" style="display: none;">
                        <label for="create-existing-user-id" class="form-label" style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 6px;">
                            Select Existing User Account
                        </label>
                        <select
                            name="user_id"
                            id="create-existing-user-id"
                            class="form-input"
                            style="width: 100%; height: 44px; padding: 8px 14px; font-size: 14px; border-radius: 10px; box-sizing: border-box; background: var(--white);"
                        >
                            <option value="">-- Choose Account to Link --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name ?: $user->username }} ({{ $user->email }})
                                    @if($user->member) [Currently linked: {{ $user->member->full_name }}] @endif
                                </option>
                            @endforeach
                        </select>
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 5px;">
                            Linking this member gives this existing user account access to view and respond to their birthday letters.
                        </small>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 14px; margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line);">
                    <a href="{{ route('admin.members') }}" class="button button-ghost" style="height: 44px; padding: 11px 24px; font-weight: 700; text-decoration: none;">
                        Cancel
                    </a>
                    <button type="submit" class="button button-primary" id="btn-submit-create-member" style="height: 44px; min-width: 160px; font-weight: 700;">
                        Save Member
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
</style>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/heic2any.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Account Mode Switcher (New Account vs Link Existing)
    const modeNew = document.getElementById('mode-create-new');
    const modeLink = document.getElementById('mode-link-existing');
    const blockNew = document.getElementById('block-new-account');
    const blockLink = document.getElementById('block-link-account');
    const existingSelect = document.getElementById('create-existing-user-id');
    const newEmail = document.getElementById('create-user-email');

    function syncAccountMode() {
        if (modeLink && modeLink.checked) {
            blockNew.style.display = 'none';
            blockLink.style.display = 'block';
            if (newEmail) newEmail.removeAttribute('required');
        } else {
            blockNew.style.display = 'flex';
            blockLink.style.display = 'none';
            if (existingSelect) existingSelect.value = '';
            if (newEmail) newEmail.setAttribute('required', 'required');
        }
    }

    if (modeNew && modeLink) {
        modeNew.addEventListener('change', syncAccountMode);
        modeLink.addEventListener('change', syncAccountMode);
        syncAccountMode();
    }

    // 2. Interactive Photo Cropper
    const dropzone = document.getElementById('create-photo-dropzone');
    const studio = document.getElementById('create-photo-studio');
    const fileInput = document.getElementById('create-photo-input');
    const base64Input = document.getElementById('create-photo-base64');
    const cropImg = document.getElementById('create-crop-img');
    const viewport = document.getElementById('create-crop-viewport');
    const zoomRange = document.getElementById('create-crop-zoom-range');
    const zoomLabel = document.getElementById('create-crop-zoom-label');
    const btnReset = document.getElementById('btn-create-crop-reset');
    const btnChooseDiff = document.getElementById('btn-create-choose-diff');
    const btnCancelPhoto = document.getElementById('btn-create-cancel-photo');
    const form = document.getElementById('form-create-member');
    const submitBtn = document.getElementById('btn-submit-create-member');

    let originalFile = null;
    let imgNaturalW = 0;
    let imgNaturalH = 0;
    let currentZoom = 1.0;
    let currentOffsetX = 0;
    let currentOffsetY = 0;
    const vpSize = 190;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startOffsetX = 0;
    let startOffsetY = 0;
    let hasNewCrop = false;

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

    if (btnChooseDiff && fileInput) {
        btnChooseDiff.addEventListener('click', () => fileInput.click());
    }

    if (btnCancelPhoto) {
        btnCancelPhoto.addEventListener('click', () => {
            fileInput.value = '';
            if (base64Input) base64Input.value = '';
            originalFile = null;
            hasNewCrop = false;
            studio.style.display = 'none';
            dropzone.style.display = 'flex';
        });
    }

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
                dropzone.style.display = 'none';
                studio.style.display = 'block';
                centerImage();
            };
            cropImg.onerror = () => {
                dropzone.style.display = 'flex';
                studio.style.display = 'none';
                const label = dropzone.querySelector('small');
                if (label) label.textContent = 'Selected: ' + file.name;
                hasNewCrop = false;
            };
            cropImg.src = e.target.result;
        };
        reader.readAsDataURL(displayBlob);
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

    // Form submission interception
    if (form) {
        form.addEventListener('submit', async (e) => {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }

            if (hasNewCrop) {
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
                } catch (err) {
                    console.warn('Canvas crop fallback, submitting raw file:', err);
                }
            }
        });
    }

    // 3. Password Show/Hide Toggle
    document.querySelectorAll('.btn-toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const iconEye = this.querySelector('.icon-eye');
            const iconEyeOff = this.querySelector('.icon-eye-off');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                if (iconEye) iconEye.style.display = 'none';
                if (iconEyeOff) iconEyeOff.style.display = 'inline';
            } else {
                input.type = 'password';
                if (iconEye) iconEye.style.display = 'inline';
                if (iconEyeOff) iconEyeOff.style.display = 'none';
            }
        });
    });
});
</script>
@endpush


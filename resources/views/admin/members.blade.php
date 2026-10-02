@extends('layouts.admin')

@section('title', 'Members Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Community & Fellowship</span>
        <h1>Members Management</h1>
        <p>
            Connected in fellowship, growing together in faith, love, and unity across BYC Growth. Manage community member records directly on the live roster cards.
        </p>
    </div>
    <div class="admin-header-actions">
        <button type="button" class="button button-primary button-sm" id="btn-open-add-member">
            <x-icon name="plus" /> Add Member
        </button>
    </div>
</div>
@endsection

@section('content')
    {{-- Search & Filtering Bar --}}
    <div class="admin-card" style="background: var(--cream); border: 1px solid var(--line); border-radius: 16px; padding: 18px 22px; margin-bottom: 24px;">
        <form method="GET" action="{{ route('admin.members') }}" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Search Member</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name..." class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 160px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Filter</label>
                <select name="filter" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="all" {{ ($filters['filter'] ?? '') === 'all' ? 'selected' : '' }}>All Members</option>
                    <option value="birthday_today" {{ ($filters['filter'] ?? '') === 'birthday_today' ? 'selected' : '' }}>Birthday Today 🎂</option>
                </select>
            </div>

            <div style="min-width: 180px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Sort By</label>
                <select name="sort" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Name (A – Z)</option>
                    <option value="name_desc" {{ ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' }}>Name (Z – A)</option>
                    <option value="dob_asc" {{ ($filters['sort'] ?? '') === 'dob_asc' ? 'selected' : '' }}>Birthday (Earliest)</option>
                    <option value="dob_desc" {{ ($filters['sort'] ?? '') === 'dob_desc' ? 'selected' : '' }}>Birthday (Latest)</option>
                    <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest Registered</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="button button-primary button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Filter
                </button>
                <a href="{{ route('admin.members') }}" class="button button-ghost button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Members Grid --}}
    @if($members->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 48px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px;">
            <div style="font-size: 36px; margin-bottom: 12px;">👥</div>
            <h2>No members found.</h2>
            <p style="max-width: 480px; margin: 0 auto; color: var(--muted);">
                @if(!empty($filters['search']) || ($filters['filter'] ?? '') !== 'all')
                    No members match your current filters. Try resetting the filters.
                @else
                    Click "Add Member" above to create the first community member profile.
                @endif
            </p>
        </div>
    @else
        {{-- Batch Actions Bar --}}
        <form id="form-batch-delete-members" method="POST" action="{{ route('admin.members.batch-delete') }}" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; background: var(--white); border: 1px solid var(--line); border-radius: 14px; padding: 12px 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            @csrf
            <div style="display: flex; align-items: center; gap: 12px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--ink); cursor: pointer; user-select: none;">
                    <input type="checkbox" id="check-select-all-members" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;">
                    <span>Select All</span>
                </label>
                <span id="batch-selected-count-members" style="font-size: 12px; color: var(--muted); font-weight: 600;">(0 selected)</span>
            </div>
            <div>
                <button type="button" class="button button-danger button-sm" id="btn-batch-delete-members" disabled style="opacity: 0.5; height: 32px; font-size: 12px;">
                    Delete Selected
                </button>
            </div>
        </form>

        <div class="members-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 24px;">
            @foreach($members as $member)
                @php
                    $isBirthday = $member->isBirthdayToday();
                @endphp
                <div class="member-card {{ $isBirthday ? 'member-card-birthday' : '' }}" data-birthday="{{ $isBirthday ? '1' : '0' }}" style="background: var(--white); border: {{ $isBirthday ? '2px solid var(--gold)' : '1px solid var(--line)' }}; border-radius: 20px; padding: 20px; text-align: center; box-shadow: {{ $isBirthday ? '0 12px 30px rgba(231, 189, 82, 0.2)' : 'var(--shadow)' }}; position: relative; display: flex; flex-direction: column; align-items: center; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <input type="checkbox" name="ids[]" value="{{ $member->id }}" form="form-batch-delete-members" class="member-batch-checkbox" style="position: absolute; top: 14px; left: 14px; width: 18px; height: 18px; accent-color: var(--forest); cursor: pointer; z-index: 2;" title="Select for batch delete">
                    
                    {{-- Photo Frame --}}
                    <div class="member-photo-frame" style="width: 130px; height: 130px; border-radius: 50%; overflow: hidden; background: var(--cream); border: 3px solid {{ $isBirthday ? 'var(--gold)' : 'var(--line)' }}; {{ $isBirthday ? 'box-shadow: 0 0 0 3px var(--gold), 0 8px 24px rgba(231, 189, 82, 0.45);' : '' }} margin-bottom: 14px; display: flex; align-items: center; justify-content: center; position: relative;">
                        @if($member->photo_url)
                            <img
                                src="{{ $member->photo_url }}"
                                alt="{{ $member->full_name }}"
                                class="member-photo-img"
                                style="width: 100%; height: 100%; object-fit: cover;"
                                loading="lazy"
                                onerror="this.style.display='none'; document.getElementById('fallback-{{ $member->id }}').style.display='flex';"
                            >
                            <div id="fallback-{{ $member->id }}" class="member-photo-fallback" style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 38px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                                {{ strtoupper(substr($member->full_name, 0, 1)) }}
                            </div>
                        @else
                            <div class="member-photo-fallback" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 38px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                                {{ strtoupper(substr($member->full_name, 0, 1)) }}
                            </div>
                        @endif

                        @if($isBirthday)
                            <div class="birthday-badge-pin" title="Celebrating Birthday Today!" style="position: absolute; bottom: 2px; right: 2px; width: 30px; height: 30px; background: #fdf5d7; border: 2px solid var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 3px 8px rgba(0,0,0,0.18);">
                                🎂
                            </div>
                        @endif
                    </div>

                    {{-- Member Full Name --}}
                    <h2 class="member-name" style="font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 700; color: var(--ink); margin: 0 0 4px; line-height: 1.3;">
                        {{ $member->full_name }}
                    </h2>

                    {{-- Admin-Only Birthdate & Status Context --}}
                    <small style="color: var(--muted); font-size: 12px; margin-bottom: 12px; display: block;">
                        🎂 {{ $member->date_of_birth ? $member->date_of_birth->format('M j, Y') : 'DOB not set' }}
                    </small>

                    {{-- Administrative Controls (Edit / Delete) --}}
                    <div class="admin-action-group" style="display: flex; gap: 8px; width: 100%; justify-content: center; margin-top: auto;">
                        <button
                            type="button"
                            class="button button-secondary button-sm btn-edit-member"
                            data-id="{{ $member->id }}"
                            data-name="{{ $member->full_name }}"
                            data-dob="{{ $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '' }}"
                            data-photo="{{ $member->photo_url }}"
                            style="font-size: 12px; padding: 4px 10px; height: 32px;"
                        >
                            ✎ Edit
                        </button>
                        <button
                            type="button"
                            class="button button-danger button-sm"
                            data-admin-confirm="Are you sure you want to remove {{ $member->full_name }}?"
                            data-confirm-title="Delete Member"
                            data-confirm-body="Are you sure you want to remove {{ $member->full_name }} from the community roster? Historical records will be handled safely."
                            data-confirm-btn="Yes, Delete Member"
                            data-action="{{ route('admin.members.destroy', $member->id) }}"
                            data-method="DELETE"
                            style="font-size: 12px; padding: 4px 10px; height: 32px;"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Member Modal --}}
    <div id="modal-add-member" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-add-member-title">
        <div class="info-modal" style="width: min(720px, 94vw); max-height: calc(100vh - 40px); overflow: hidden; padding: 22px 28px; background: var(--white); border-radius: 22px; position: relative; box-shadow: var(--shadow);">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-add-member" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 14px; padding-right: 32px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Community Directory</span>
                <h3 id="modal-add-member-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Add New Member</h3>
            </div>

            <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data" id="form-add-member" class="member-modal-grid">
                @csrf

                {{-- Left Column: Photo Picker & Live Large Preview --}}
                <div style="text-align: center;">
                    <label class="form-label" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 8px; text-align: left;">
                        Profile Photo
                    </label>

                    {{-- Empty Dropzone --}}
                    <div id="add-member-dropzone" class="crop-dropzone" style="height: 240px; width: 240px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border-radius: 50%;">
                        <div class="crop-dropzone-icon" style="font-size: 38px; margin-bottom: 6px;">👤</div>
                        <div style="font-weight: 700; color: var(--ink); font-size: 13.5px; margin-bottom: 4px;">Choose Photo</div>
                        <small style="color: var(--muted); font-size: 11px; display: block; line-height: 1.3;">Click or drag photo<br>Any size supported</small>
                    </div>

                    <input type="file" id="add-photo" name="photo" accept="image/*" style="display: none;">

                    {{-- Live Large Preview Studio --}}
                    <div id="add-member-studio" style="display: none;">
                        <div class="crop-viewport-container member-crop-viewport" id="add-member-viewport" style="width: 240px; height: 240px; margin: 0 auto 10px; border-radius: 50%;">
                            <img id="add-member-preview-img" class="crop-viewport-image" alt="Member Photo Preview" src="">
                            <div class="crop-grid-overlay" style="border-radius: 50%;">
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; max-width: 240px; margin: 0 auto;">
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

                {{-- Right Column: Member Details --}}
                <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                    <div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label class="form-label" for="add-full-name" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 6px;">
                                Full Name <span style="color: var(--red);">*</span>
                            </label>
                            <input type="text" id="add-full-name" name="full_name" class="form-input" required placeholder="e.g. Jonathan Wijaya" style="height: 38px; font-size: 13.5px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 14px;">
                            <label class="form-label" for="add-dob" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 6px;">
                                Date of Birth
                            </label>
                            <input type="date" id="add-dob" name="date_of_birth" class="form-input" style="height: 38px; font-size: 13.5px;">
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Used for community birthday celebrations.</small>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; padding-top: 14px; border-top: 1px solid var(--line);">
                        <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                        <button type="submit" class="button button-primary button-sm" id="btn-submit-add-member">
                            <x-icon name="plus" /> Save Member
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Member Modal --}}
    <div id="modal-edit-member" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-edit-member-title">
        <div class="info-modal" style="width: min(720px, 94vw); max-height: calc(100vh - 40px); overflow: hidden; padding: 22px 28px; background: var(--white); border-radius: 22px; position: relative; box-shadow: var(--shadow);">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-edit-member" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 14px; padding-right: 32px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Community Directory</span>
                <h3 id="modal-edit-member-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Edit Member</h3>
            </div>

            <form id="form-edit-member" method="POST" action="" enctype="multipart/form-data" class="member-modal-grid">
                @csrf
                @method('PUT')

                {{-- Left Column: Photo Preview & Editor Studio --}}
                <div style="text-align: center;">
                    <label class="form-label" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 8px; text-align: left;">
                        Profile Photo
                    </label>

                    {{-- Empty Dropzone when no photo --}}
                    <div id="edit-member-dropzone" class="crop-dropzone" style="height: 240px; width: 240px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border-radius: 50%;">
                        <div class="crop-dropzone-icon" style="font-size: 38px; margin-bottom: 6px;">👤</div>
                        <div style="font-weight: 700; color: var(--ink); font-size: 13.5px; margin-bottom: 4px;">Choose Photo</div>
                        <small style="color: var(--muted); font-size: 11px; display: block; line-height: 1.3;">Click or drag photo<br>Any size supported</small>
                    </div>

                    <input type="file" id="edit-photo" name="photo" accept="image/*" style="display: none;">

                    {{-- Photo Preview & Crop Studio --}}
                    <div id="edit-member-studio" style="display: none;">
                        <div class="crop-viewport-container member-crop-viewport" id="edit-member-viewport" style="width: 240px; height: 240px; margin: 0 auto 10px; border-radius: 50%;">
                            <img id="edit-member-preview-img" class="crop-viewport-image" alt="Edit Photo Preview" src="">
                            <div class="crop-grid-overlay" style="border-radius: 50%;">
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                                <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; max-width: 240px; margin: 0 auto 10px;">
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
                    <div id="edit-member-remove-photo-wrap" style="display: none; margin-top: 8px;">
                        <label style="font-size: 12px; color: var(--red); display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" id="edit-remove-photo" name="remove_photo" value="1">
                            Remove current photo
                        </label>
                    </div>
                </div>

                {{-- Right Column: Member Details --}}
                <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                    <div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label class="form-label" for="edit-full-name" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 6px;">
                                Full Name <span style="color: var(--red);">*</span>
                            </label>
                            <input type="text" id="edit-full-name" name="full_name" class="form-input" required style="height: 38px; font-size: 13.5px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 14px;">
                            <label class="form-label" for="edit-dob" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 6px;">
                                Date of Birth
                            </label>
                            <input type="date" id="edit-dob" name="date_of_birth" class="form-input" style="height: 38px; font-size: 13.5px;">
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Used for community birthday celebrations.</small>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; padding-top: 14px; border-top: 1px solid var(--line);">
                        <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                        <button type="submit" class="button button-primary button-sm" id="btn-submit-edit-member">
                            Update Member
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('modal-add-member');
    const editModal = document.getElementById('modal-edit-member');
    const btnOpenAdd = document.getElementById('btn-open-add-member');

    const formAdd = document.getElementById('form-add-member');
    const formEdit = document.getElementById('form-edit-member');

    const editName = document.getElementById('edit-full-name');
    const editDob = document.getElementById('edit-dob');
    const removeWrap = document.getElementById('edit-member-remove-photo-wrap');
    const removeCb = document.getElementById('edit-remove-photo');

    // Cropper instance controller helper
    function setupCropper(config) {
        const {
            dropzone,
            fileInput,
            studio,
            viewport,
            img,
            zoomRange,
            zoomLabel,
            btnReset,
            btnChange,
            form,
            submitBtn
        } = config;

        let originalFile = null;
        let imgNaturalW = 0;
        let imgNaturalH = 0;
        let currentZoom = 1.0;
        let currentOffsetX = 0;
        let currentOffsetY = 0;
        const viewportSize = 240;
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let startOffsetX = 0;
        let startOffsetY = 0;
        let hasNewFile = false;

        function getDimensions() {
            const baseScale = Math.max(viewportSize / (imgNaturalW || 1), viewportSize / (imgNaturalH || 1));
            const scale = baseScale * currentZoom;
            const displayW = imgNaturalW * scale;
            const displayH = imgNaturalH * scale;

            const minOffsetX = viewportSize - displayW;
            const maxOffsetX = 0;
            const minOffsetY = viewportSize - displayH;
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
            img.style.width = `${displayW}px`;
            img.style.height = `${displayH}px`;
            img.style.left = `${currentOffsetX}px`;
            img.style.top = `${currentOffsetY}px`;
        }

        function centerImage() {
            const { displayW, displayH } = getDimensions();
            currentOffsetX = (viewportSize - displayW) / 2;
            currentOffsetY = (viewportSize - displayH) / 2;
            clampOffsets();
            updateImageStyle();
        }

        function loadFile(file) {
            originalFile = file;
            hasNewFile = true;
            const reader = new FileReader();
            reader.onload = (e) => {
                img.onload = () => {
                    imgNaturalW = img.naturalWidth;
                    imgNaturalH = img.naturalHeight;
                    currentZoom = 1.0;
                    if (zoomRange) zoomRange.value = '1';
                    if (zoomLabel) zoomLabel.textContent = '1.0x';
                    dropzone.style.display = 'none';
                    studio.style.display = 'block';
                    centerImage();
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function loadExistingUrl(url) {
            originalFile = null;
            hasNewFile = false;
            img.onload = () => {
                imgNaturalW = img.naturalWidth;
                imgNaturalH = img.naturalHeight;
                currentZoom = 1.0;
                if (zoomRange) zoomRange.value = '1';
                if (zoomLabel) zoomLabel.textContent = '1.0x';
                dropzone.style.display = 'none';
                studio.style.display = 'block';
                centerImage();
            };
            img.src = url;
        }

        function reset() {
            originalFile = null;
            hasNewFile = false;
            fileInput.value = '';
            img.src = '';
            dropzone.style.display = 'flex';
            studio.style.display = 'none';
            currentZoom = 1.0;
            if (zoomRange) zoomRange.value = '1';
            if (zoomLabel) zoomLabel.textContent = '1.0x';
        }

        // File drop & select listeners
        dropzone.addEventListener('click', () => fileInput.click());
        btnChange.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                loadFile(file);
                if (removeCb) removeCb.checked = false;
            }
        });

        // Drag to pan
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

        function endDrag(e) {
            if (isDragging) {
                isDragging = false;
                try { viewport.releasePointerCapture(e.pointerId); } catch(err) {}
            }
        }
        viewport.addEventListener('pointerup', endDrag);
        viewport.addEventListener('pointercancel', endDrag);

        // Zoom slider
        if (zoomRange) {
            zoomRange.addEventListener('input', () => {
                currentZoom = parseFloat(zoomRange.value);
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

        // Generate cropped and compressed image blob
        function getCroppedBlob() {
            return new Promise((resolve, reject) => {
                try {
                    const { scale } = getDimensions();
                    const cropX = -currentOffsetX / scale;
                    const cropY = -currentOffsetY / scale;
                    const cropW = viewportSize / scale;
                    const cropH = viewportSize / scale;

                    const targetSize = Math.min(1200, Math.max(480, Math.round(cropW)));
                    const canvas = document.createElement('canvas');
                    canvas.width = targetSize;
                    canvas.height = targetSize;
                    const ctx = canvas.getContext('2d');

                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, cropX, cropY, cropW, cropH, 0, 0, targetSize, targetSize);

                    const mimeType = (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg';
                    canvas.toBlob((blob) => {
                        if (blob) resolve(blob);
                        else reject(new Error('Canvas export failed'));
                    }, mimeType.includes('png') ? 'image/png' : 'image/jpeg', 0.90);
                } catch (err) {
                    reject(err);
                }
            });
        }

        // Intercept form submit to attach compressed cropped file
        form.addEventListener('submit', async (e) => {
            if (!hasNewFile) {
                return; // Standard submit if no new photo was chosen
            }

            e.preventDefault();
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving...';
            }

            try {
                const blob = await getCroppedBlob();
                const croppedFile = new File([blob], originalFile ? originalFile.name : 'member_photo.jpg', {
                    type: blob.type || 'image/jpeg',
                    lastModified: Date.now()
                });

                if (window.DataTransfer) {
                    const dt = new DataTransfer();
                    dt.items.add(croppedFile);
                    fileInput.files = dt.files;
                    form.submit();
                } else {
                    const formData = new FormData(form);
                    formData.set('photo', croppedFile, croppedFile.name);
                    await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    window.location.reload();
                }
            } catch (err) {
                console.warn('Fallback submit due to:', err);
                form.submit();
            }
        });

        return { loadFile, loadExistingUrl, reset };
    }

    // Initialize Add Member Cropper
    const addCropper = setupCropper({
        dropzone: document.getElementById('add-member-dropzone'),
        fileInput: document.getElementById('add-photo'),
        studio: document.getElementById('add-member-studio'),
        viewport: document.getElementById('add-member-viewport'),
        img: document.getElementById('add-member-preview-img'),
        zoomRange: document.getElementById('add-member-zoom-range'),
        zoomLabel: document.getElementById('add-member-zoom-label'),
        btnReset: document.getElementById('add-member-btn-reset'),
        btnChange: document.getElementById('add-member-btn-change'),
        form: formAdd,
        submitBtn: document.getElementById('btn-submit-add-member')
    });

    // Initialize Edit Member Cropper
    const editCropper = setupCropper({
        dropzone: document.getElementById('edit-member-dropzone'),
        fileInput: document.getElementById('edit-photo'),
        studio: document.getElementById('edit-member-studio'),
        viewport: document.getElementById('edit-member-viewport'),
        img: document.getElementById('edit-member-preview-img'),
        zoomRange: document.getElementById('edit-member-zoom-range'),
        zoomLabel: document.getElementById('edit-member-zoom-label'),
        btnReset: document.getElementById('edit-member-btn-reset'),
        btnChange: document.getElementById('edit-member-btn-change'),
        form: formEdit,
        submitBtn: document.getElementById('btn-submit-edit-member')
    });

    // Open Add Modal
    if (btnOpenAdd && addModal) {
        btnOpenAdd.addEventListener('click', () => {
            addCropper.reset();
            addModal.style.display = 'grid';
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        });
    }

    // Open Edit Modal
    document.querySelectorAll('.btn-edit-member').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const dob = this.getAttribute('data-dob');
            const photo = this.getAttribute('data-photo');

            if (formEdit) {
                formEdit.action = `/admin/members/${id}`;
            }
            if (editName) editName.value = name || '';
            if (editDob) editDob.value = dob || '';
            if (removeCb) removeCb.checked = false;

            if (photo) {
                editCropper.loadExistingUrl(photo);
                if (removeWrap) removeWrap.style.display = 'block';
            } else {
                editCropper.reset();
                if (removeWrap) removeWrap.style.display = 'none';
            }

            if (editModal) {
                editModal.style.display = 'grid';
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Handle remove photo checkbox toggle in Edit Modal
    if (removeCb) {
        removeCb.addEventListener('change', function () {
            if (this.checked) {
                document.getElementById('edit-member-viewport').style.opacity = '0.35';
            } else {
                document.getElementById('edit-member-viewport').style.opacity = '1';
            }
        });
    }

    // Close Modals
    function closeAllModals() {
        if (addModal) addModal.style.display = 'none';
        if (editModal) editModal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    [addModal, editModal].forEach(m => {
        if (m) {
            m.addEventListener('click', (e) => {
                if (e.target === m) closeAllModals();
            });
        }
    });

    // Batch Delete Members Selection & Confirmation
    const selectAllCheckbox = document.getElementById('check-select-all-members');
    const batchCheckboxes = document.querySelectorAll('.member-batch-checkbox');
    const batchCountSpan = document.getElementById('batch-selected-count-members');
    const btnBatchDelete = document.getElementById('btn-batch-delete-members');
    const batchForm = document.getElementById('form-batch-delete-members');

    function updateBatchDeleteState() {
        const checkedBoxes = document.querySelectorAll('.member-batch-checkbox:checked');
        const count = checkedBoxes.length;

        if (batchCountSpan) {
            batchCountSpan.textContent = `(${count} selected)`;
        }

        if (btnBatchDelete) {
            if (count > 0) {
                btnBatchDelete.disabled = false;
                btnBatchDelete.style.opacity = '1';
                btnBatchDelete.style.cursor = 'pointer';
            } else {
                btnBatchDelete.disabled = true;
                btnBatchDelete.style.opacity = '0.5';
                btnBatchDelete.style.cursor = 'not-allowed';
            }
        }

        if (selectAllCheckbox && batchCheckboxes.length > 0) {
            selectAllCheckbox.checked = (count === batchCheckboxes.length);
            selectAllCheckbox.indeterminate = (count > 0 && count < batchCheckboxes.length);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            batchCheckboxes.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
            });
            updateBatchDeleteState();
        });
    }

    batchCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchDeleteState);
    });

    if (btnBatchDelete && batchForm) {
        btnBatchDelete.addEventListener('click', function () {
            const count = document.querySelectorAll('.member-batch-checkbox:checked').length;
            if (count === 0) return;

            if (typeof window.openAdminConfirm === 'function') {
                window.openAdminConfirm({
                    title: 'Delete Selected Members',
                    message: `Are you sure you want to remove ${count} selected member(s) from the community roster? Historical records will be handled safely.`,
                    confirmText: 'Yes, Delete Selected',
                    buttonClass: 'button-danger',
                    onConfirm: function () {
                        batchForm.submit();
                    }
                });
            } else {
                if (confirm(`Are you sure you want to remove ${count} selected member(s)?`)) {
                    batchForm.submit();
                }
            }
        });
    }
});
</script>
@endpush

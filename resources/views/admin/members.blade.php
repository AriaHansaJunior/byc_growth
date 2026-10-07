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
            Add Member
        </button>
    </div>
</div>
@endsection

@push('styles')
<style>
.member-modal-card {
    width: min(840px, 95vw);
    min-height: 590px;
}

.member-modal-body {
    padding: 22px 30px 48px;
    flex: 1;
    min-height: 440px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

@media (min-width: 641px) and (min-height: 620px) {
    .member-modal-body {
        overflow: visible !important;
    }
}

@media (max-width: 640px) {
    .member-modal-card {
        width: min(94vw, 480px) !important;
        min-height: auto !important;
        max-height: calc(100vh - 32px) !important;
    }
    .member-modal-body {
        padding: 16px 18px 36px !important;
        min-height: auto !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        gap: 14px !important;
    }
    .member-form-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
}

.byc-select-trigger:hover {
    border-color: var(--forest) !important;
}

.byc-select-options-list::-webkit-scrollbar {
    width: 6px;
}
.byc-select-options-list::-webkit-scrollbar-track {
    background: transparent;
}
.byc-select-options-list::-webkit-scrollbar-thumb {
    background: var(--line);
    border-radius: 4px;
}
.byc-select-options-list::-webkit-scrollbar-thumb:hover {
    background: var(--muted);
}
</style>
@endpush

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
                <a href="{{ route('admin.members') }}" class="button button-danger button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
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
                    <small style="color: var(--muted); font-size: 12px; margin-bottom: 2px; display: block;">
                        🎂 {{ $member->date_of_birth ? $member->date_of_birth->format('M j, Y') : 'DOB not set' }}
                    </small>

                    <small style="color: var(--forest); font-size: 12px; margin-bottom: 6px; display: block; font-weight: 600;">
                        User Account: {{ $member->user ? ($member->user->email ?: $member->user->username) : 'Not linked' }}
                    </small>

                    @if($member->audit_trail_text)
                        <div class="member-audit-trail" style="font-size: 11px; color: var(--muted); font-style: italic; margin-bottom: 12px; line-height: 1.35; padding: 0 4px; word-break: break-word;">
                            {{ $member->audit_trail_text }}
                        </div>
                    @else
                        <div style="margin-bottom: 12px;"></div>
                    @endif

                    {{-- Administrative Controls (Edit / Delete) --}}
                    <div class="admin-action-group" style="display: flex; gap: 8px; width: 100%; justify-content: center; margin-top: auto;">
                        <button
                            type="button"
                            class="button button-secondary button-sm btn-edit-member"
                            data-id="{{ $member->id }}"
                            data-name="{{ $member->full_name }}"
                            data-dob="{{ $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '' }}"
                            data-photo="{{ $member->photo_url }}"
                            data-user-id="{{ $member->user ? $member->user->id : '' }}"
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

    @include('admin.partials.member-add-modal')
    @include('admin.partials.member-edit-modal')
    @include('admin.partials.member-quick-account-modal')
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

    // Custom Themed Form Validation
    function setFieldError(field, message) {
        if (!field) return;
        clearFieldError(field);

        field.classList.add('input-invalid');
        if (field._bycDatePicker && field._bycDatePicker.displayInput) {
            field._bycDatePicker.displayInput.classList.add('input-invalid');
        }

        const errorEl = document.createElement('div');
        errorEl.className = 'form-field-error';
        const fieldId = field.id || field.name;
        if (fieldId) {
            errorEl.setAttribute('data-for', fieldId);
        }
        errorEl.innerHTML = `<span style="font-size: 13px; line-height: 1;">⚠️</span> <span>${message}</span>`;

        const targetEl = field.closest('.byc-date-wrapper') || field;
        targetEl.insertAdjacentElement('afterend', errorEl);
    }

    function clearFieldError(field) {
        if (!field) return;
        field.classList.remove('input-invalid');
        if (field._bycDatePicker && field._bycDatePicker.displayInput) {
            field._bycDatePicker.displayInput.classList.remove('input-invalid');
        }

        const fieldId = field.id || field.name;
        const parent = field.closest('.form-group') || field.parentElement;
        if (parent) {
            parent.querySelectorAll(`.form-field-error[data-for="${fieldId}"]`).forEach(el => el.remove());
        }
        const targetEl = field.closest('.byc-date-wrapper') || field;
        if (targetEl.nextElementSibling && targetEl.nextElementSibling.classList.contains('form-field-error')) {
            targetEl.nextElementSibling.remove();
        }
    }

    function clearAllErrors(form) {
        if (!form) return;
        form.querySelectorAll('.input-invalid').forEach(el => el.classList.remove('input-invalid'));
        form.querySelectorAll('.form-field-error').forEach(el => el.remove());
        form.querySelectorAll('.byc-date-display').forEach(el => el.classList.remove('input-invalid'));
    }

    function validateMemberForm(form) {
        clearAllErrors(form);
        let hasError = false;

        const nameInput = form.querySelector('input[name="full_name"]');
        const dobInput = form.querySelector('input[name="date_of_birth"]');

        if (!nameInput || !nameInput.value || !nameInput.value.trim()) {
            setFieldError(nameInput, 'Full name is required. Please enter member name.');
            hasError = true;
        } else if (nameInput.value.trim().length < 2) {
            setFieldError(nameInput, 'Full name must be at least 2 characters.');
            hasError = true;
        }

        if (dobInput && dobInput.value) {
            const parts = dobInput.value.split('-');
            if (parts.length === 3) {
                const entered = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10), 23, 59, 59);
                const today = new Date();
                today.setHours(23, 59, 59, 999);
                if (entered > today) {
                    setFieldError(dobInput, 'Date of birth cannot be in the future (maximum date is today).');
                    hasError = true;
                }
            }
        }

        if (hasError) {
            const firstInvalid = form.querySelector('.input-invalid');
            if (firstInvalid) {
                if (firstInvalid._bycDatePicker && firstInvalid._bycDatePicker.displayInput) {
                    firstInvalid._bycDatePicker.displayInput.focus();
                } else {
                    firstInvalid.focus();
                }
            }
            return false;
        }

        return true;
    }

    // Attach real-time clear listeners on input and change
    [formAdd, formEdit].forEach(form => {
        if (!form) return;
        const nameInput = form.querySelector('input[name="full_name"]');
        const dobInput = form.querySelector('input[name="date_of_birth"]');

        if (nameInput) {
            nameInput.addEventListener('input', () => clearFieldError(nameInput));
            nameInput.addEventListener('change', () => clearFieldError(nameInput));
        }
        if (dobInput) {
            dobInput.addEventListener('input', () => clearFieldError(dobInput));
            dobInput.addEventListener('change', () => clearFieldError(dobInput));
        }
    });

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
        const viewportSize = config.viewportSize || 200;
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

        // Intercept form submit to validate and attach compressed cropped file
        form.addEventListener('submit', async (e) => {
            if (!validateMemberForm(form)) {
                e.preventDefault();
                return;
            }

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
        viewportSize: 124,
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
        viewportSize: 124,
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

    // Searchable Select Controller for User Accounts
    function initSearchableSelect(selectId, wrapperId, triggerId, menuId) {
        const select = document.getElementById(selectId);
        const wrapper = document.getElementById(wrapperId);
        const trigger = document.getElementById(triggerId);
        const menu = document.getElementById(menuId);
        if (!select || !wrapper || !trigger || !menu) return null;

        const triggerText = trigger.querySelector('.byc-select-trigger-text');
        const chevron = trigger.querySelector('.byc-select-chevron');
        const searchInput = menu.querySelector('.byc-select-search-input');
        const optionsList = menu.querySelector('.byc-select-options-list');

        function buildOptions() {
            if (!optionsList) return;
            optionsList.innerHTML = '';
            const options = Array.from(select.querySelectorAll('option'));

            options.forEach(opt => {
                const val = opt.value;
                const text = opt.textContent.trim();
                const email = opt.getAttribute('data-email') || '';
                const username = opt.getAttribute('data-username') || '';
                const isLinked = opt.getAttribute('data-linked') === '1' || text.includes('[Linked]');
                const isSelected = String(select.value) === String(val);

                const optItem = document.createElement('div');
                optItem.className = 'byc-select-option' + (isSelected ? ' selected' : '');
                optItem.setAttribute('data-value', val);
                optItem.setAttribute('data-search', `${email} ${username} ${text}`.toLowerCase());
                optItem.setAttribute('title', text);
                optItem.style.cssText = 'padding: 8px 12px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 13px; color: var(--ink); user-select: none; transition: background 0.12s;';

                if (!val) {
                    optItem.innerHTML = `
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; color: var(--muted); font-style: italic;">
                            ${text}
                        </span>
                        <span class="byc-select-check" style="color: var(--forest); font-weight: 800; font-size: 13px; ${isSelected ? '' : 'display: none;'}">✓</span>
                    `;
                } else {
                    optItem.innerHTML = `
                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;">
                            <span style="font-weight: 600; color: var(--ink);">${email || text}</span>
                            ${username ? `<span style="color: var(--muted); font-size: 12px; margin-left: 4px;">(${username})</span>` : ''}
                        </div>
                        ${isLinked ? `<span style="font-size: 10px; font-weight: 700; color: var(--forest); background: rgba(33, 77, 51, 0.08); padding: 2px 6px; border-radius: 4px; flex-shrink: 0;">Linked</span>` : ''}
                        <span class="byc-select-check" style="color: var(--forest); font-weight: 800; font-size: 13px; ${isSelected ? '' : 'display: none;'}">✓</span>
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
            const text = selectedOpt ? selectedOpt.textContent.trim() : 'None (Unlinked)';
            if (triggerText) {
                triggerText.textContent = text;
                triggerText.setAttribute('title', text);
                if (!select.value) {
                    triggerText.style.color = 'var(--muted)';
                    triggerText.style.fontStyle = 'italic';
                } else {
                    triggerText.style.color = 'var(--ink)';
                    triggerText.style.fontStyle = 'normal';
                }
            }
        }

        function setValue(val) {
            select.value = val;
            select.dataset.prevValue = val;
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

        function open() {
            document.querySelectorAll('.byc-select-dropdown').forEach(d => {
                if (d !== menu) d.style.display = 'none';
            });
            document.querySelectorAll('.byc-select-chevron').forEach(c => {
                if (c !== chevron) c.style.transform = 'none';
            });

            menu.style.display = 'flex';
            trigger.setAttribute('aria-expanded', 'true');
            trigger.style.borderColor = 'var(--forest)';
            trigger.style.boxShadow = '0 0 0 3px rgba(33, 77, 51, 0.15)';
            if (chevron) chevron.style.transform = 'rotate(180deg)';
            if (searchInput) {
                searchInput.value = '';
                filterOptions('');
                setTimeout(() => searchInput.focus(), 50);
            }

            if (window.innerWidth <= 640) {
                setTimeout(() => {
                    trigger.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }

        function close() {
            menu.style.display = 'none';
            trigger.setAttribute('aria-expanded', 'false');
            trigger.style.borderColor = 'var(--line)';
            trigger.style.boxShadow = 'none';
            if (chevron) chevron.style.transform = 'none';
        }

        function toggle() {
            if (menu.style.display === 'flex') {
                close();
            } else {
                open();
            }
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
            toggle();
        });

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                filterOptions(e.target.value);
            });
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    close();
                    trigger.focus();
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                close();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && menu.style.display === 'flex') {
                close();
            }
        });

        select.addEventListener('change', () => {
            updateTrigger();
            const curVal = select.value;
            if (optionsList) {
                optionsList.querySelectorAll('.byc-select-option').forEach(el => {
                    const isMatch = el.getAttribute('data-value') === String(curVal);
                    el.classList.toggle('selected', isMatch);
                    el.style.background = isMatch ? 'rgba(33, 77, 51, 0.08)' : 'transparent';
                    const check = el.querySelector('.byc-select-check');
                    if (check) check.style.display = isMatch ? 'inline' : 'none';
                });
            }
        });

        buildOptions();

        return {
            rebuild: buildOptions,
            setValue: setValue,
            close: close
        };
    }

    const searchableAdd = initSearchableSelect('add-user-id', 'searchable-add-user', 'btn-trigger-add-user', 'dropdown-menu-add-user');
    const searchableEdit = initSearchableSelect('edit-user-id', 'searchable-edit-user', 'btn-trigger-edit-user', 'dropdown-menu-edit-user');

    // Quick Create User Account Modal Controller
    let quickAccountTarget = 'add';
    const quickUserModal = document.getElementById('modal-quick-create-user');
    const formQuickUser = document.getElementById('form-quick-create-user');
    const quickUserError = document.getElementById('quick-user-alert-error');
    const btnSubmitQuickUser = document.getElementById('btn-submit-quick-user');
    const addUserSelect = document.getElementById('add-user-id');
    const editUserSelect = document.getElementById('edit-user-id');

    function openQuickCreateUser(target) {
        quickAccountTarget = target;
        const sourceNameInput = target === 'edit'
            ? document.getElementById('edit-full-name')
            : document.getElementById('add-full-name');
        const quickNameInput = document.getElementById('quick-user-name');
        if (sourceNameInput && quickNameInput && !quickNameInput.value) {
            quickNameInput.value = sourceNameInput.value.trim();
        }

        if (quickUserError) {
            quickUserError.style.display = 'none';
            quickUserError.textContent = '';
        }

        if (quickUserModal) {
            quickUserModal.style.display = 'flex';
        }
    }

    function closeQuickCreateUser() {
        if (quickUserModal) {
            quickUserModal.style.display = 'none';
        }
        if (formQuickUser) {
            formQuickUser.reset();
        }
        if (quickUserError) {
            quickUserError.style.display = 'none';
            quickUserError.textContent = '';
        }
    }

    document.querySelectorAll('.btn-close-quick-user').forEach(btn => {
        btn.addEventListener('click', closeQuickCreateUser);
    });

    document.querySelectorAll('.btn-trigger-quick-account').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target') || 'add';
            openQuickCreateUser(target);
        });
    });

    if (formQuickUser) {
        formQuickUser.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (quickUserError) {
                quickUserError.style.display = 'none';
                quickUserError.textContent = '';
            }

            const formData = new FormData(formQuickUser);
            if (btnSubmitQuickUser) {
                btnSubmitQuickUser.disabled = true;
                btnSubmitQuickUser.textContent = 'Creating...';
            }

            try {
                const response = await fetch(formQuickUser.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json().catch(() => null);

                if (!response.ok || !data || !data.success) {
                    let errorMsg = 'Failed to create user account.';
                    if (data && data.errors) {
                        errorMsg = Object.values(data.errors).flat().join(' ');
                    } else if (data && data.message) {
                        errorMsg = data.message;
                    }
                    if (quickUserError) {
                        quickUserError.textContent = errorMsg;
                        quickUserError.style.display = 'block';
                    }
                    if (btnSubmitQuickUser) {
                        btnSubmitQuickUser.disabled = false;
                        btnSubmitQuickUser.textContent = 'Create Account';
                    }
                    return;
                }

                const newUser = data.user;
                const newOptionLabel = `${newUser.email} (${newUser.username})`;

                [addUserSelect, editUserSelect].forEach(sel => {
                    if (!sel) return;
                    let opt = sel.querySelector(`option[value="${newUser.id}"]`);
                    if (!opt) {
                        opt = document.createElement('option');
                        opt.value = newUser.id;
                        opt.textContent = newOptionLabel;
                        opt.setAttribute('data-email', newUser.email);
                        opt.setAttribute('data-username', newUser.username);
                        let existingGroup = sel.querySelector('optgroup[label="Existing Accounts"]');
                        if (existingGroup) {
                            existingGroup.appendChild(opt);
                        } else {
                            sel.appendChild(opt);
                        }
                    }
                });

                if (searchableAdd) searchableAdd.rebuild();
                if (searchableEdit) searchableEdit.rebuild();

                const activeSearchable = quickAccountTarget === 'edit' ? searchableEdit : searchableAdd;
                if (activeSearchable) {
                    activeSearchable.setValue(newUser.id);
                }

                closeQuickCreateUser();

                if (typeof window.showAdminToast === 'function') {
                    window.showAdminToast('Account created and linked successfully!', 'success');
                }
            } catch (err) {
                if (quickUserError) {
                    quickUserError.textContent = 'Network or server error while creating account.';
                    quickUserError.style.display = 'block';
                }
            } finally {
                if (btnSubmitQuickUser) {
                    btnSubmitQuickUser.disabled = false;
                    btnSubmitQuickUser.textContent = 'Create Account';
                }
            }
        });
    }

    // Open Add Modal
    if (btnOpenAdd && addModal) {
        btnOpenAdd.addEventListener('click', () => {
            clearAllErrors(formAdd);
            addCropper.reset();
            if (window.setDatePickerValue) window.setDatePickerValue('add-dob', '');
            if (searchableAdd) {
                searchableAdd.setValue('');
            } else if (addUserSelect) {
                addUserSelect.value = '';
                addUserSelect.dataset.prevValue = '';
            }
            addModal.style.display = 'flex';
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        });
    }

    // Open Edit Modal
    document.querySelectorAll('.btn-edit-member').forEach(btn => {
        btn.addEventListener('click', function () {
            clearAllErrors(formEdit);
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const dob = this.getAttribute('data-dob');
            const photo = this.getAttribute('data-photo');
            const userId = this.getAttribute('data-user-id') || '';

            if (formEdit) {
                formEdit.action = `/admin/members/${id}`;
            }
            if (editName) editName.value = name || '';
            if (editDob) {
                if (window.setDatePickerValue) window.setDatePickerValue(editDob, dob || '');
                else editDob.value = dob || '';
            }
            if (removeCb) removeCb.checked = false;

            if (searchableEdit) {
                searchableEdit.setValue(userId);
            } else if (editUserSelect) {
                editUserSelect.value = userId;
                editUserSelect.dataset.prevValue = userId;
            }

            if (photo) {
                editCropper.loadExistingUrl(photo);
                if (removeWrap) removeWrap.style.display = 'block';
            } else {
                editCropper.reset();
                if (removeWrap) removeWrap.style.display = 'none';
            }

            if (editModal) {
                editModal.style.display = 'flex';
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
        clearAllErrors(formAdd);
        clearAllErrors(formEdit);
        if (searchableAdd) searchableAdd.close();
        if (searchableEdit) searchableEdit.close();
        if (addModal) addModal.style.display = 'none';
        if (editModal) editModal.style.display = 'none';
        closeQuickCreateUser();
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
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

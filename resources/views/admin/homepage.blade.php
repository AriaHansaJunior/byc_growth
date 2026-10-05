@extends('layouts.admin')

@section('title', 'Homepage Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Hero &amp; Slideshow</span>
        <h1>Homepage Management</h1>
        <p>
            Manage the main slideshow images displayed on the homepage.
        </p>
    </div>
</div>
@endsection

@section('content')
    {{-- Main Home Hero Preview --}}
    <main class="home" style="margin-bottom: 32px;">
        <section class="home-hero">
            <div class="home-copy">
                <h1>Growing together in faith,<em>purpose, and community.</em></h1>
                <p>
                    BYC Growth is a fellowship empowering young believers to step out in faith, build authentic lifelong friendships, and walk purposefully together in Christ.
                </p>
            </div>

            {{-- Large Group Photo Area (Dynamic Slideshow Preview) --}}
            <div class="hero-photo-wrap" style="position: relative;">
                <div class="hero-photo-frame" id="hero-photo-slideshow" data-fallback="{{ asset('assets/images/group-photo-dummy.svg') }}">
                    <div class="hero-slides-track">
                        @if($slides->isNotEmpty())
                            @foreach($slides as $index => $slide)
                                <img
                                    @if($index === 0) id="hero-group-photo" @endif
                                    src="{{ $slide->image_url }}"
                                    alt="{{ $slide->title ?: 'BYC Growth Fellowship Slide ' . ($index + 1) }}"
                                    class="hero-group-photo hero-slide {{ $index === 0 ? 'active' : '' }}"
                                    data-slide-index="{{ $index }}"
                                >
                            @endforeach
                        @else
                            <img
                                id="hero-group-photo"
                                src="{{ asset('assets/images/hero-slide-1.jpg') }}"
                                alt="BYC Growth Fellowship Group Photo 1"
                                class="hero-group-photo hero-slide active"
                                data-slide-index="0"
                            >
                        @endif
                    </div>

                    {{-- Interactive Slide Indicators --}}
                    @if($slides->count() > 1)
                        <div class="hero-slide-indicators" aria-label="Slideshow Indicators">
                            @foreach($slides as $index => $slide)
                                <button type="button" class="slide-dot {{ $index === 0 ? 'active' : '' }}" data-slide-to="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>

    {{-- S2 Slideshow Management Section --}}
    <section class="slideshow-management-section" id="slideshow-management" style="background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 32px; box-shadow: var(--shadow);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; display: block; margin-bottom: 4px;">Hero Content Control</span>
                <h2 style="font: 800 22px 'Manrope', sans-serif; color: var(--ink); margin: 0 0 6px;">Slideshow Management</h2>
                <p style="color: var(--muted); font-size: 14px; margin: 0;">
                    Upload new photos, change their order, or remove slideshow images.
                </p>
            </div>
            <div>
                <button type="button" class="button button-primary button-sm btn-trigger-add-slide" id="btn-open-add-slide">
                   Add Photo
                </button>
            </div>
        </div>

        @if($slides->isEmpty())
            <div class="placeholder-card" style="text-align: center; padding: 40px 20px; background: var(--paper); border: 1.5px dashed var(--line); border-radius: 18px;">
                <div style="font-size: 32px; margin-bottom: 10px;">📷</div>
                <h3 style="font: 700 18px 'Manrope', sans-serif; color: var(--ink); margin: 0 0 6px;">No Slideshow Photos Yet</h3>
                <p style="color: var(--muted); font-size: 14px; max-width: 440px; margin: 0 auto 16px;">
                    The homepage currently displays the default image. Click "Add Photo" to upload the first slideshow photo.
                </p>
            </div>
        @else
            <div class="slideshow-table-wrap" style="overflow-x: auto; border: 1px solid var(--line); border-radius: 16px;">
                <table class="admin-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 80px; text-align: center;">Order</th>
                            <th style="width: 120px;">Preview</th>
                            <th>Photo Information</th>
                            <th style="width: 140px; text-align: center;">Sequence</th>
                            <th style="width: 130px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="slideshow-list-tbody">
                        @foreach($slides as $index => $slide)
                            <tr data-slide-id="{{ $slide->id }}">
                                {{-- Order Badge --}}
                                <td style="text-align: center;">
                                    <span class="role-badge" style="background: var(--paper); color: var(--forest-dark); font-weight: 800; font-size: 13px;">
                                        #{{ $index + 1 }}
                                    </span>
                                </td>

                                {{-- Image Preview --}}
                                <td>
                                    <div style="width: 96px; height: 60px; border-radius: 10px; overflow: hidden; background: var(--forest-dark); border: 1px solid var(--line); position: relative;">
                                        <img
                                            src="{{ $slide->image_url }}"
                                            alt="{{ $slide->title ?: 'Slide ' . ($index + 1) }}"
                                            style="width: 100%; height: 100%; object-fit: cover;"
                                            loading="lazy"
                                        >
                                    </div>
                                </td>

                                {{-- Information --}}
                                <td>
                                    <div style="font-weight: 700; color: var(--ink); font-size: 15px; margin-bottom: 4px;">
                                        {{ $slide->title ?: ($slide->media ? $slide->media->original_name : 'Homepage Slide #' . ($index + 1)) }}
                                    </div>
                                    <div style="display: flex; gap: 8px; font-size: 11.5px; color: var(--muted); align-items: center;">
                                        <span>📁 {{ $slide->media ? $slide->media->original_name : 'Embedded Asset' }}</span>
                                        @if($slide->media && $slide->media->file_size)
                                            <span>&bull; {{ number_format($slide->media->file_size / 1024, 1) }} KB</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Reorder Up / Down Controls --}}
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px; align-items: center;">
                                        {{-- Move Up Form --}}
                                        <form method="POST" action="{{ route('admin.homepage.slides.move-up', $slide->id) }}" style="display: inline;">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="button button-ghost button-sm btn-reorder-up"
                                                title="Move Up"
                                                @if($index === 0) disabled style="opacity: 0.35; cursor: not-allowed; padding: 4px 10px; height: 32px;" @else style="padding: 4px 10px; height: 32px;" @endif
                                            >
                                                ↑
                                            </button>
                                        </form>

                                        {{-- Move Down Form --}}
                                        <form method="POST" action="{{ route('admin.homepage.slides.move-down', $slide->id) }}" style="display: inline;">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="button button-ghost button-sm btn-reorder-down"
                                                title="Move Down"
                                                @if($index === $slides->count() - 1) disabled style="opacity: 0.35; cursor: not-allowed; padding: 4px 10px; height: 32px;" @else style="padding: 4px 10px; height: 32px;" @endif
                                            >
                                                ↓
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                {{-- Actions (Delete with Confirmation) --}}
                                <td style="text-align: right;">
                                    <button
                                        type="button"
                                        class="button button-danger button-sm btn-delete-slide"
                                        data-admin-confirm="This photo will be permanently removed from the homepage slideshow."
                                        data-confirm-title="Delete Slideshow Photo?"
                                        data-confirm-btn="Delete Photo"
                                        data-action="{{ route('admin.homepage.slides.destroy', $slide->id) }}"
                                        data-method="DELETE"
                                        style="padding: 4px 12px; height: 32px; font-size: 13px;"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Add Slideshow Photo Modal --}}
    <div id="modal-add-slide" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-add-slide-title">
        <div class="info-modal" style="width: min(480px, 94vw); max-height: calc(100vh - 40px); overflow: hidden; padding: 20px 24px; background: var(--white); border-radius: 20px; position: relative; box-shadow: var(--shadow);">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-add-slide" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 12px; padding-right: 32px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Homepage Slideshow</span>
                <h3 id="modal-add-slide-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Add Slideshow Photo</h3>
            </div>

            <form method="POST" action="{{ route('admin.homepage.slides.store') }}" enctype="multipart/form-data" id="form-add-slide">
                @csrf

                {{-- Photo Picker Area --}}
                <div class="form-group" style="margin-bottom: 10px;">
                    <label class="form-label" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 6px;">
                        Photo Image <span style="color: var(--red);">*</span>
                    </label>

                    {{-- Dropzone / File Picker --}}
                    <div id="crop-dropzone" class="crop-dropzone" style="padding: 24px 16px;">
                        <div class="crop-dropzone-icon">📷</div>
                        <div style="font-weight: 700; color: var(--ink); font-size: 14px; margin-bottom: 4px;">
                            Click to select photo or drag here
                        </div>
                        <small style="color: var(--muted); font-size: 12px; display: block;">
                            Supports JPEG, PNG, WEBP, GIF up to 5MB.
                        </small>
                    </div>

                    <input type="file" id="add-slide-image" name="image" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" required style="display: none;">
                </div>

                {{-- Selected File Info Bar (Hidden until selected) --}}
                <div id="crop-file-info" style="display: none; align-items: center; justify-content: space-between; background: var(--paper); border: 1px solid var(--line); border-radius: 10px; padding: 8px 12px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                        <span>📷</span>
                        <span id="crop-file-name" style="font-weight: 700; font-size: 12.5px; color: var(--ink); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">photo.jpg</span>
                        <span id="crop-file-size" style="font-size: 11.5px; color: var(--muted); white-space: nowrap;">(1.2 MB)</span>
                    </div>
                    <button type="button" id="btn-change-photo" class="button button-ghost button-sm" style="padding: 3px 8px; font-size: 11.5px; height: 26px;">
                        Change
                    </button>
                </div>

                {{-- 1:1 Live Preview & Crop Adjustment Studio (Hidden until selected) --}}
                <div id="crop-studio" class="crop-studio-wrap" style="display: none; padding: 14px; border-radius: 16px;">
                    {{-- 1:1 Viewport Frame --}}
                    <div id="crop-viewport" class="crop-viewport-container">
                        <img id="crop-image-element" class="crop-viewport-image" alt="Crop preview" src="">
                        <div class="crop-grid-overlay">
                            <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                            <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                            <div class="crop-grid-cell"></div><div class="crop-grid-cell"></div><div class="crop-grid-cell"></div>
                        </div>
                    </div>

                    {{-- Zoom Controls --}}
                    <div class="crop-controls-bar">
                        <div class="crop-zoom-bar">
                            <span id="crop-zoom-label" style="white-space: nowrap; font-size: 12px;">Zoom: 1.0x</span>
                            <input type="range" id="crop-zoom-range" min="1" max="2.5" step="0.05" value="1">
                            <button type="button" id="btn-reset-crop" class="crop-preset-btn" style="padding: 3px 8px; font-size: 11px;">Reset</button>
                        </div>
                    </div>
                </div>

                {{-- Modal Action Buttons --}}
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm" id="btn-submit-add-slide">
                        <x-icon name="plus" /> Upload Photo
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-add-slide');
    if (!modal) return;

    const openBtns = document.querySelectorAll('.btn-trigger-add-slide');
    const closeBtns = modal.querySelectorAll('.btn-close-modal');
    const form = document.getElementById('form-add-slide');
    const fileInput = document.getElementById('add-slide-image');
    const dropzone = document.getElementById('crop-dropzone');
    const fileInfo = document.getElementById('crop-file-info');
    const fileNameEl = document.getElementById('crop-file-name');
    const fileSizeEl = document.getElementById('crop-file-size');
    const btnChangePhoto = document.getElementById('btn-change-photo');
    const studio = document.getElementById('crop-studio');
    const viewport = document.getElementById('crop-viewport');
    const cropImg = document.getElementById('crop-image-element');
    const btnSubmit = document.getElementById('btn-submit-add-slide');

    const zoomRange = document.getElementById('crop-zoom-range');
    const zoomLabel = document.getElementById('crop-zoom-label');
    const btnReset = document.getElementById('btn-reset-crop');

    // Cropping State
    let originalFile = null;
    let imgNaturalW = 0;
    let imgNaturalH = 0;
    let currentZoom = 1.0;
    let currentOffsetX = 0;
    let currentOffsetY = 0;
    const viewportW = 320; // px (4:3 aspect ratio)
    const viewportH = 240; // px
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startOffsetX = 0;
    let startOffsetY = 0;

    function openModal() {
        modal.style.display = 'grid';
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        resetCropper();
    }

    function resetCropper() {
        originalFile = null;
        fileInput.value = '';
        dropzone.style.display = 'block';
        fileInfo.style.display = 'none';
        studio.style.display = 'none';
        cropImg.src = '';
        currentZoom = 1.0;
        zoomRange.value = '1';
        zoomLabel.textContent = 'Zoom: 1.0x';
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide-icon lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg> Upload Photo';
    }

    openBtns.forEach(btn => btn.addEventListener('click', openModal));
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') closeModal();
    });

    // Dropzone handlers
    dropzone.addEventListener('click', () => fileInput.click());
    if (btnChangePhoto) btnChangePhoto.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files.length > 0) {
            handleFileSelect(fileInput.files[0]);
        }
    });

    function handleFileSelect(file) {
        if (!file || !file.type.startsWith('image/')) {
            alert('Please select a valid image file (JPEG, PNG, WEBP, or GIF).');
            return;
        }

        originalFile = file;
        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = `(${ (file.size / 1024).toFixed(1) } KB)`;

        const reader = new FileReader();
        reader.onload = (e) => {
            cropImg.onload = () => {
                imgNaturalW = cropImg.naturalWidth;
                imgNaturalH = cropImg.naturalHeight;
                currentZoom = 1.0;
                zoomRange.value = '1';
                zoomLabel.textContent = 'Zoom: 1.0x';

                dropzone.style.display = 'none';
                fileInfo.style.display = 'flex';
                studio.style.display = 'block';

                // Center initially
                alignPosition('center');
            };
            cropImg.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function getDimensions() {
        const baseScale = Math.max(viewportW / imgNaturalW, viewportH / imgNaturalH);
        const scale = baseScale * currentZoom;
        const displayW = imgNaturalW * scale;
        const displayH = imgNaturalH * scale;

        const minOffsetX = viewportW - displayW;
        const maxOffsetX = 0;
        const minOffsetY = viewportH - displayH;
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

    function alignPosition(align) {
        const { displayW, displayH, minOffsetX, minOffsetY } = getDimensions();
        if (align === 'center') {
            currentOffsetX = (viewportW - displayW) / 2;
            currentOffsetY = (viewportH - displayH) / 2;
        } else if (align === 'top') {
            currentOffsetY = 0;
        } else if (align === 'bottom') {
            currentOffsetY = minOffsetY;
        } else if (align === 'left') {
            currentOffsetX = 0;
        } else if (align === 'right') {
            currentOffsetX = minOffsetX;
        }
        clampOffsets();
        updateImageStyle();
    }

    // Preset button click listeners
    modal.querySelectorAll('.crop-preset-btn[data-align]').forEach(btn => {
        btn.addEventListener('click', () => {
            alignPosition(btn.getAttribute('data-align'));
        });
    });

    // Zoom listener
    zoomRange.addEventListener('input', () => {
        currentZoom = parseFloat(zoomRange.value);
        zoomLabel.textContent = `Zoom: ${currentZoom.toFixed(1)}x`;
        clampOffsets();
        updateImageStyle();
    });

    btnReset.addEventListener('click', () => {
        currentZoom = 1.0;
        zoomRange.value = '1';
        zoomLabel.textContent = 'Zoom: 1.0x';
        alignPosition('center');
    });

    // Drag to pan image within 1:1 viewport
    viewport.addEventListener('pointerdown', (e) => {
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        startOffsetX = currentOffsetX;
        startOffsetY = currentOffsetY;
        viewport.setPointerCapture(e.pointerId);
    });

    viewport.addEventListener('pointermove', (e) => {
        if (!isDragging) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        currentOffsetX = startOffsetX + dx;
        currentOffsetY = startOffsetY + dy;
        clampOffsets();
        updateImageStyle();
    });

    function endDrag(e) {
        if (!isDragging) return;
        isDragging = false;
        try { viewport.releasePointerCapture(e.pointerId); } catch (_) {}
    }

    viewport.addEventListener('pointerup', endDrag);
    viewport.addEventListener('pointercancel', endDrag);

    // Canvas cropping helper
    function getCroppedBlob() {
        return new Promise((resolve, reject) => {
            try {
                const { scale } = getDimensions();
                const cropX = -currentOffsetX / scale;
                const cropY = -currentOffsetY / scale;
                const cropW = viewportW / scale;
                const cropH = viewportH / scale;

                const targetW = Math.min(1600, Math.max(800, Math.round(cropW)));
                const targetH = Math.round(targetW * 3 / 4);
                const canvas = document.createElement('canvas');
                canvas.width = targetW;
                canvas.height = targetH;
                const ctx = canvas.getContext('2d');

                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(
                    cropImg,
                    cropX, cropY, cropW, cropH,
                    0, 0, targetW, targetH
                );

                const mimeType = (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg';
                canvas.toBlob((blob) => {
                    if (blob) resolve(blob);
                    else reject(new Error('Canvas export failed'));
                }, mimeType.includes('png') ? 'image/png' : 'image/jpeg', 0.92);
            } catch (err) {
                reject(err);
            }
        });
    }

    // Submit handler
    form.addEventListener('submit', async (e) => {
        if (!originalFile) {
            return;
        }

        e.preventDefault();
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = 'Uploading...';

        try {
            const blob = await getCroppedBlob();
            const croppedFile = new File([blob], originalFile.name, {
                type: blob.type || originalFile.type,
                lastModified: Date.now()
            });

            if (window.DataTransfer) {
                const dt = new DataTransfer();
                dt.items.add(croppedFile);
                fileInput.files = dt.files;
                form.submit();
            } else {
                const formData = new FormData(form);
                formData.set('image', croppedFile, originalFile.name);
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
});
</script>
@endpush

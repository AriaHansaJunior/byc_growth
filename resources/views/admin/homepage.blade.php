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
                                    data-slide-id="{{ $slide->id }}"
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
                    The homepage currently displays the default image. 
                    <br> Click "Add Photo" to upload the first slideshow photo.
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
                            <th style="width: 120px; text-align: center;">Sequence</th>
                            <th style="width: 170px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="slideshow-list-tbody" data-reorder-url="{{ route('admin.homepage.slides.reorder') }}">
                        @foreach($slides as $index => $slide)
                            <tr data-slide-id="{{ $slide->id }}" class="draggable-slide-row" draggable="true">
                                {{-- Order Badge --}}
                                <td style="text-align: center;">
                                    <span class="role-badge slide-order-badge" style="background: var(--paper); color: var(--forest-dark); font-weight: 800; font-size: 13px;">
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

                                {{-- Sequence Controls (Drag to Reorder) --}}
                                <td style="text-align: center;">
                                    <div
                                        class="slide-drag-handle"
                                        title="Click & drag to reorder photos"
                                        style="cursor: grab; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 5px 14px; height: 32px; border-radius: 8px; background: var(--paper); border: 1px solid var(--line); color: var(--ink); user-select: none;"
                                    >
                                        <span style="font-size: 15px; line-height: 1; color: var(--forest); display: inline-block;">⠿</span>
                                        <span style="font-size: 12px; font-weight: 700;">Drag</span>
                                    </div>
                                </td>

                                {{-- Actions (Edit Position & Delete) --}}
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end;">
                                        <button
                                            type="button"
                                            class="button button-sm btn-edit-slide"
                                            data-id="{{ $slide->id }}"
                                            data-title="{{ $slide->title ?: ($slide->media ? $slide->media->original_name : '') }}"
                                            data-image="{{ $slide->image_url }}"
                                            data-filename="{{ $slide->media ? $slide->media->original_name : 'Slide #' . ($index + 1) }}"
                                            data-action="{{ route('admin.homepage.slides.update', $slide->id) }}"
                                            title="Edit Photo Position"
                                            style="padding: 4px 14px; height: 32px; font-size: 13px; font-weight: 700; background: #dbebe0; color: #1a452d; border: 1px solid #b8dac2;"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="button button-danger button-sm btn-delete-slide"
                                            data-confirm-message="This photo will be permanently removed from the homepage slideshow."
                                            data-confirm-title="Delete Slideshow Photo?"
                                            data-confirm-btn="Delete Photo"
                                            data-action="{{ route('admin.homepage.slides.destroy', $slide->id) }}"
                                            style="padding: 4px 12px; height: 32px; font-size: 13px;"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    @include('admin.partials.homepage-add-modal')
    @include('admin.partials.homepage-edit-modal')
@endsection
`
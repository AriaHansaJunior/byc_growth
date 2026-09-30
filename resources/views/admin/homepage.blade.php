@extends('layouts.admin')

@section('title', 'Homepage Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Homepage Architecture</span>
        <h1>Homepage Management</h1>
        <p>
            Manage the hero slideshow, scripture statement, and destination portals directly within the live homepage layout.
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="{{ route('home') }}" class="button button-ghost button-sm" target="_blank">
            <x-icon name="arrow" /> View Public Homepage &rarr;
        </a>
    </div>
</div>
@endsection

@section('content')
    {{-- Admin Controls Summary Strip --}}
    <div class="admin-controls-strip">
        <div class="admin-controls-badge">
            <x-icon name="sparkles" /> Live Homepage God Mode &bull; Real-time Presentation &amp; Management Controls
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="#slideshow-management" class="button button-primary button-sm" id="btn-edit-slideshow">
                <x-icon name="plus" /> Manage Slides ({{ $slides->count() }})
            </a>
        </div>
    </div>

    {{-- Main Home Structure (Identical Visual Foundation to User Homepage + Admin Controls) --}}
    <main class="home" style="margin-bottom: 40px;">
        {{-- Hero / Introduction Section --}}
        <section class="home-hero">
            <div class="home-copy">
                <h1>Growing together in faith,<em>purpose, and community.</em></h1>
                <p>
                    BYC Growth is a fellowship empowering young believers to step out in faith, build authentic lifelong friendships, and walk purposefully together in Christ.
                </p>
                <div style="margin-top: 24px; display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="button button-secondary button-sm">
                        ✎ Edit Hero Copy
                    </button>
                    <small style="color: var(--muted); font-size: 13px;">Live header text active on public site</small>
                </div>
            </div>

            {{-- Large Group Photo Area (Dynamic Slideshow with Admin Controls) --}}
            <div class="hero-photo-wrap" style="position: relative;">
                <div style="position: absolute; top: 12px; right: 12px; z-index: 20; display: flex; gap: 8px;">
                    <span class="role-badge" style="background: rgba(255, 255, 255, 0.95); color: var(--forest); box-shadow: var(--shadow);">
                        📷 {{ $slides->count() }} Hero Slides
                    </span>
                    <button type="button" class="button button-primary button-sm btn-trigger-add-slide" style="box-shadow: var(--shadow);">
                        + Add Photo
                    </button>
                </div>

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

        {{-- S2 Slideshow Management Section --}}
        <section class="slideshow-management-section" id="slideshow-management" style="margin-top: 36px; background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 32px; box-shadow: var(--shadow);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span class="eyebrow" style="color: var(--forest); font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; display: block; margin-bottom: 4px;">Hero Content Control</span>
                    <h2 style="font: 800 22px 'Manrope', sans-serif; color: var(--ink); margin: 0 0 6px;">Slideshow Management</h2>
                    <p style="color: var(--muted); font-size: 14px; margin: 0;">
                        Upload photos, adjust display sequence, or remove slides from the live homepage presentation.
                    </p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="role-badge" style="background: var(--paper); color: var(--forest-dark); border: 1px solid var(--line);">
                        {{ $slides->count() }} Active Photos
                    </span>
                    <button type="button" class="button button-primary button-sm btn-trigger-add-slide" id="btn-open-add-slide">
                        <x-icon name="plus" /> Add Photo
                    </button>
                </div>
            </div>

            @if($slides->isEmpty())
                <div class="placeholder-card" style="text-align: center; padding: 40px 20px; background: var(--paper); border: 1.5px dashed var(--line); border-radius: 18px;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📷</div>
                    <h3 style="font: 700 18px 'Manrope', sans-serif; color: var(--ink); margin: 0 0 6px;">No Slideshow Photos Yet</h3>
                    <p style="color: var(--muted); font-size: 14px; max-width: 440px; margin: 0 auto 16px;">
                        The homepage is currently displaying the fallback graphic. Click "Add Photo" to upload your first slideshow image.
                    </p>
                    <button type="button" class="button button-primary button-sm btn-trigger-add-slide">
                        <x-icon name="plus" /> Add First Photo
                    </button>
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
                                        @if($slide->caption)
                                            <p style="margin: 0 0 6px; font-size: 13px; color: var(--muted); line-height: 1.4;">
                                                {{ $slide->caption }}
                                            </p>
                                        @endif
                                        <div style="display: flex; gap: 8px; font-size: 11.5px; color: var(--muted); align-items: center;">
                                            <span>📁 {{ $slide->media ? $slide->media->original_name : 'Embedded Asset' }}</span>
                                            @if($slide->media && $slide->media->file_size)
                                                <span>&bull; {{ number_format($slide->media->file_size / 1024, 1) }} KB</span>
                                            @endif
                                            <span>&bull;</span>
                                            <span style="color: var(--forest); font-weight: 600;">● Live</span>
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
                                            data-admin-confirm="This image will be removed from the homepage slideshow."
                                            data-confirm-title="Delete Slideshow Image?"
                                            data-confirm-body="This image will be removed from the homepage slideshow."
                                            data-confirm-btn="Delete"
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

        {{-- Scripture / Statement Section (Identical Visual Foundation + Admin Controls) --}}
        <section class="scripture-section" style="position: relative;">
            <div style="position: absolute; top: -14px; right: 24px; z-index: 10;">
                <button type="button" class="button button-secondary button-sm" style="box-shadow: var(--shadow);">
                    ✎ Edit Scripture Card
                </button>
            </div>
            <div class="scripture-card">
                <div class="scripture-content">
                    <span class="scripture-eyebrow">Our Guiding Scripture</span>
                    <blockquote>
                        &ldquo;For we walk by faith, not by sight&rdquo;
                    </blockquote>
                    <div class="scripture-reference">
                        2 Corinthians 5:7
                    </div>
                    <p class="scripture-subtext">
                        Our journey as young believers is anchored in trust, rooted in God's promises, and guided by a vision that transcends what is visible. Together, we grow with boldness and unity.
                    </p>
                </div>
                <div class="scripture-stamp">
                    <strong>Faith<br>&bull;<br>Growth</strong>
                </div>
            </div>
        </section>

        {{-- Destination Section (Identical Visual Foundation + Domain Admin Quick Links) --}}
        <section class="destinations-section" id="destinations" style="margin-top: 48px;">
            <div class="destinations-heading">
                <div>
                    <span class="eyebrow">Explore BYC Growth</span>
                    <h2>Destination Portals</h2>
                </div>
                <p>
                    Manage each functional domain of the website directly from its live destination card.
                </p>
            </div>

            <div class="destinations-grid has-admin">
                {{-- 1. Activity --}}
                <a href="{{ route('admin.activities') }}" class="destination-card dest-activity">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="calendar" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Timeline & Events</span>
                        <h3>Community Activities</h3>
                        <p>Curate event timeline milestones, youth retreats, and photo galleries.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Activities Admin &rarr;</span>
                    </div>
                </a>

                {{-- 2. Member --}}
                <a href="{{ route('admin.members') }}" class="destination-card dest-member">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="users" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Community Roster</span>
                        <h3>Members Directory</h3>
                        <p>Manage member profiles, confidential birthdates, and photo records.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Members Admin &rarr;</span>
                    </div>
                </a>

                {{-- 3. Game Center --}}
                <a href="{{ route('admin.games') }}" class="destination-card dest-game">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="sparkles" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Interactive Gaming</span>
                        <h3>Game Center</h3>
                        <p>Control teams, survey answers, live round points, and cumulative scores.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Games Admin &rarr;</span>
                    </div>
                </a>

                {{-- 4. Cash Management --}}
                <a href="{{ route('admin.cash-management') }}" class="destination-card dest-cash">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="cash" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Financial Stewardship</span>
                        <h3>Cash Management</h3>
                        <p>Record member contributions, verify transfer proofs, and track treasury balance.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Cash Admin &rarr;</span>
                    </div>
                </a>
            </div>
        </section>
    </main>

    {{-- Add Slideshow Photo Modal --}}
    <div id="modal-add-slide" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-add-slide-title">
        <div class="info-modal" style="width: min(520px, 92vw); padding: 32px; background: var(--white); border-radius: 24px; position: relative; box-shadow: var(--shadow);">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-add-slide" style="position: absolute; top: 20px; right: 20px; width: 34px; height: 34px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 20px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Homepage Slideshow</span>
                <h3 id="modal-add-slide-title" style="font: 800 24px 'Manrope', sans-serif; color: var(--ink); margin: 4px 0 0;">Add Slideshow Photo</h3>
                <p style="color: var(--muted); font-size: 13.5px; margin: 4px 0 0;">
                    Upload a high-resolution image to feature in the main hero slideshow.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.homepage.slides.store') }}" enctype="multipart/form-data" id="form-add-slide">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="add-slide-image" style="font-weight: 700; font-size: 13px; display: block; margin-bottom: 6px;">Photo Image <span style="color: var(--red);">*</span></label>
                    <input type="file" id="add-slide-image" name="image" class="form-input" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 12px; font-size: 14px;">
                    <small style="color: var(--muted); font-size: 12px; display: block; margin-top: 4px;">Supported formats: JPEG, PNG, WEBP, GIF. Maximum file size: 5MB.</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="add-slide-title" style="font-weight: 700; font-size: 13px; display: block; margin-bottom: 6px;">Title (Optional)</label>
                    <input type="text" id="add-slide-title" name="title" class="form-input" placeholder="e.g. Growing in Faith & Fellowship" maxlength="255" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; font-size: 14px;">
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add-slide-caption" style="font-weight: 700; font-size: 13px; display: block; margin-bottom: 6px;">Caption (Optional)</label>
                    <textarea id="add-slide-caption" name="caption" class="form-input" rows="3" placeholder="Brief note or spiritual theme description" maxlength="500" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; font-size: 14px; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
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

    function openModal() {
        modal.style.display = 'grid';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    openBtns.forEach(btn => btn.addEventListener('click', openModal));
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') closeModal();
    });
});
</script>
@endpush

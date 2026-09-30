@extends('layouts.app')

@section('title', 'Community Activities — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Back Navigation & Admin Action --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>

        @if(auth()->check() && auth()->user()->isAdmin())
            <button type="button" class="button button-primary button-sm" id="btn-open-add-activity" style="margin-left: auto;">
                <x-icon name="sparkles" /> Add New Activity
            </button>
        @endif
    </nav>

    {{-- Page Header --}}
    <header class="page-header">
        <span class="eyebrow">Milestones & Gatherings</span>
        <h1>Activities & Events</h1>
        <p>
            Celebrating the moments, events, and milestones that have shaped our BYC Growth fellowship since our cell was established.
        </p>
    </header>

    @if(session('success'))
        <div class="alert-success-box" style="margin-bottom: 24px; padding: 14px 20px; background: #eaf3dc; border: 1px solid var(--lime); border-radius: 12px; color: var(--forest-dark); font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger-box" style="margin-bottom: 24px; padding: 14px 20px; background: #fdf0ee; border: 1px solid var(--red); border-radius: 12px; color: var(--red); font-weight: 600;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Activities Timeline / List --}}
    @if($activities->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 48px 24px;">
            <div style="font-size: 36px; margin-bottom: 12px;">🌱</div>
            <h2>No Activities Recorded Yet</h2>
            <p style="max-width: 480px; margin: 0 auto; color: var(--muted);">
                Our journey is just beginning. Check back soon for exciting events, youth retreats, and fellowship highlights.
            </p>
        </div>
    @else
        <div class="activities-list" style="display: flex; flex-direction: column; gap: 32px;">
            @foreach($activities as $activity)
                <article class="activity-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 28px 32px; box-shadow: var(--shadow);">
                    <div class="activity-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
                        <div>
                            <span class="activity-date" style="display: inline-block; font-size: 13px; font-weight: 700; color: var(--forest); background: var(--cream); padding: 4px 12px; border-radius: 20px; margin-bottom: 8px;">
                                {{ $activity->event_date->format('F d, Y') }}
                            </span>
                            <h2 style="font-family: 'Manrope', sans-serif; font-size: 24px; font-weight: 800; color: var(--ink); margin: 0;">
                                {{ $activity->name }}
                            </h2>
                        </div>

                        @if(auth()->check() && auth()->user()->isAdmin())
                            <div class="admin-activity-actions" style="display: flex; gap: 8px;">
                                <button
                                    type="button"
                                    class="button button-secondary button-sm btn-edit-activity"
                                    data-id="{{ $activity->id }}"
                                    data-name="{{ $activity->name }}"
                                    data-date="{{ $activity->event_date->format('Y-m-d') }}"
                                    data-description="{{ $activity->description }}"
                                    data-photos="{{ json_encode($activity->photos->map(fn($p) => ['id' => $p->id, 'url' => $p->getUrl(), 'name' => $p->original_name])) }}"
                                >
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('admin.activities.destroy', $activity->id) }}" onsubmit="return confirm('Are you sure you want to delete this activity?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button button-danger button-sm">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="activity-description" style="color: var(--muted); font-size: 15px; line-height: 1.6; margin-bottom: 24px; white-space: pre-line;">
                        {{ $activity->description }}
                    </div>

                    {{-- Supporting Photos Gallery --}}
                    @if($activity->photos->isNotEmpty())
                        <div class="activity-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 16px;">
                            @foreach($activity->photos as $photo)
                                <a href="{{ $photo->getUrl() }}" target="_blank" rel="noopener noreferrer" class="activity-photo-item" style="display: block; border-radius: 12px; overflow: hidden; border: 1px solid var(--line); aspect-ratio: 4/3; background: var(--cream); position: relative; transition: transform .2s ease;">
                                    <img
                                        src="{{ $photo->getUrl() }}"
                                        alt="{{ $activity->name }} Photo"
                                        style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                        loading="lazy"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>

{{-- Admin Modal: Add Activity --}}
@if(auth()->check() && auth()->user()->isAdmin())
<div class="game-modal-overlay" id="modal-add-activity" style="display: none;">
    <div class="game-modal" style="max-width: 600px; width: 90%;">
        <div class="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif;">Add New Activity</h3>
            <button type="button" class="btn-close-modal" id="btn-close-add-activity" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.activities.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Event Name *</label>
                    <input type="text" name="name" required class="input-field" placeholder="e.g. BYC Fellowship Night 2026" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Event Date *</label>
                    <input type="date" name="event_date" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Description *</label>
                    <textarea name="description" rows="4" required class="input-field" placeholder="Describe the activity, highlights, and fellowship experience..." style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit; resize: vertical;"></textarea>
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Supporting Photos (Multiple Images)</label>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" class="input-field" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                    <small style="color: var(--muted); font-size: 12px;">Only images (JPG, PNG, WEBP, GIF) accepted. Max 5MB each.</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px;">
                    <button type="submit" class="button button-primary">Save Activity</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Admin Modal: Edit Activity --}}
<div class="game-modal-overlay" id="modal-edit-activity" style="display: none;">
    <div class="game-modal" style="max-width: 600px; width: 90%;">
        <div class="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif;">Edit Activity</h3>
            <button type="button" class="btn-close-modal" id="btn-close-edit-activity" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
        </div>

        <form method="POST" id="form-edit-activity" action="" enctype="multipart/form-data">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Event Name *</label>
                    <input type="text" name="name" id="edit-activity-name" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Event Date *</label>
                    <input type="date" name="event_date" id="edit-activity-date" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Description *</label>
                    <textarea name="description" id="edit-activity-description" rows="4" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit; resize: vertical;"></textarea>
                </div>

                {{-- Existing Photos Management --}}
                <div id="edit-activity-photos-container" style="display: none;">
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Current Photos (Check to remove)</label>
                    <div id="edit-photos-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; max-height: 160px; overflow-y: auto; padding: 8px; border: 1px solid var(--line); border-radius: 10px; background: var(--paper);"></div>
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Add More Photos</label>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" class="input-field" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                    <small style="color: var(--muted); font-size: 12px;">Only images (JPG, PNG, WEBP, GIF) accepted. Max 5MB each.</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px;">
                    <button type="submit" class="button button-primary">Update Activity</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('modal-add-activity');
    const openAddBtn = document.getElementById('btn-open-add-activity');
    const closeAddBtn = document.getElementById('btn-close-add-activity');

    if (openAddBtn && addModal) {
        openAddBtn.addEventListener('click', () => {
            addModal.style.display = 'flex';
        });
    }
    if (closeAddBtn && addModal) {
        closeAddBtn.addEventListener('click', () => {
            addModal.style.display = 'none';
        });
    }

    const editModal = document.getElementById('modal-edit-activity');
    const closeEditBtn = document.getElementById('btn-close-edit-activity');
    const formEdit = document.getElementById('form-edit-activity');

    if (closeEditBtn && editModal) {
        closeEditBtn.addEventListener('click', () => {
            editModal.style.display = 'none';
        });
    }

    document.querySelectorAll('.btn-edit-activity').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const date = btn.dataset.date;
            const description = btn.dataset.description;
            const photos = JSON.parse(btn.dataset.photos || '[]');

            formEdit.action = `/admin/activities/${id}`;
            document.getElementById('edit-activity-name').value = name;
            document.getElementById('edit-activity-date').value = date;
            document.getElementById('edit-activity-description').value = description;

            const container = document.getElementById('edit-activity-photos-container');
            const list = document.getElementById('edit-photos-list');
            list.innerHTML = '';

            if (photos.length > 0) {
                container.style.display = 'block';
                photos.forEach(photo => {
                    const item = document.createElement('div');
                    item.style = 'position: relative; border-radius: 8px; overflow: hidden; border: 1px solid var(--line); aspect-ratio: 1;';
                    item.innerHTML = `
                        <img src="${photo.url}" alt="${photo.name}" style="width: 100%; height: 100%; object-fit: cover;">
                        <label style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(189,76,66,0.85); color: #fff; font-size: 11px; padding: 2px 4px; display: flex; align-items: center; justify-content: center; gap: 4px; cursor: pointer;">
                            <input type="checkbox" name="remove_photo_ids[]" value="${photo.id}"> Delete
                        </label>
                    `;
                    list.appendChild(item);
                });
            } else {
                container.style.display = 'none';
            }

            editModal.style.display = 'flex';
        });
    });
});
</script>
@endif
@endsection

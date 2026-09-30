@extends('layouts.admin')

@section('title', 'Activities Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Milestones & Gatherings</span>
        <h1>Activities Management</h1>
        <p>
            Celebrating the moments, events, and milestones that have shaped our BYC Growth fellowship. Manage timeline events and galleries directly on the live activity cards.
        </p>
    </div>
    <div class="admin-header-actions">
        <button type="button" class="button button-primary button-sm" id="btn-open-add-activity">
            <x-icon name="plus" /> Add Activity
        </button>
    </div>
</div>
@endsection

@section('content')
    {{-- Admin Controls Summary Strip --}}
    <div class="admin-controls-strip">
        <div class="admin-controls-badge">
            <x-icon name="calendar" /> Live Timeline God Mode &bull; Total: {{ $totalCount }} Activities ({{ $totalPhotos }} Photos)
        </div>
        <a href="{{ route('activity') }}" class="button button-ghost button-sm" target="_blank" title="View public activities timeline">
            View Public Page &rarr;
        </a>
    </div>

    {{-- Activities List (Identical Visual Foundation to User Page + Admin Controls) --}}
    @if($activities->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 48px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px;">
            <div style="font-size: 36px; margin-bottom: 12px;">🌱</div>
            <h2>No Activities Recorded Yet</h2>
            <p style="max-width: 480px; margin: 0 auto; color: var(--muted);">
                Click "Add Activity" above to document your first fellowship gathering.
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

                        {{-- Administrative Controls (Edit / Delete) --}}
                        <div class="admin-action-group">
                            <button
                                type="button"
                                class="button button-secondary button-sm btn-edit-activity"
                                data-id="{{ $activity->id }}"
                                data-name="{{ $activity->name }}"
                                data-date="{{ $activity->event_date->format('Y-m-d') }}"
                                data-description="{{ $activity->description }}"
                                style="font-size: 12px; padding: 4px 10px; height: 32px;"
                            >
                                ✎ Edit
                            </button>
                            <button
                                type="button"
                                class="button button-danger button-sm"
                                data-admin-confirm="Are you sure you want to remove activity '{{ $activity->name }}'?"
                                data-confirm-title="Delete Activity"
                                data-confirm-body="Are you sure you want to delete the activity '{{ $activity->name }}' and its associated photo records?"
                                data-confirm-btn="Yes, Delete Activity"
                                data-action="{{ route('admin.activities.destroy', $activity->id) }}"
                                data-method="DELETE"
                                style="font-size: 12px; padding: 4px 10px; height: 32px;"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <div class="activity-description" style="color: var(--muted); font-size: 15px; line-height: 1.6; margin-bottom: 24px; white-space: pre-line;">
                        {{ $activity->description }}
                    </div>

                    {{-- Supporting Photos Gallery --}}
                    @if($activity->photos->isNotEmpty())
                        <div class="activity-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
                            @foreach($activity->photos as $photo)
                                <a href="{{ $photo->getUrl() }}" target="_blank" rel="noopener noreferrer" class="activity-photo-item" style="display: block; border-radius: 12px; overflow: hidden; border: 1px solid var(--line); aspect-ratio: 4/3; background: var(--cream); position: relative; transition: transform .2s ease;">
                                    <img
                                        src="{{ $photo->getUrl() }}"
                                        alt="{{ $activity->name }} Photo"
                                        loading="lazy"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    {{-- Add Activity Modal --}}
    <div id="modal-add-activity" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
        <div class="info-modal" style="width: min(580px, 92vw); padding: 32px; background: var(--white); border-radius: 24px; position: relative;">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-add-activity" style="position: absolute; top: 20px; right: 20px; width: 34px; height: 34px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 20px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Activities & Events</span>
                <h3 style="font: 800 24px 'Manrope', sans-serif; color: var(--ink); margin: 4px 0 0;">Add New Activity</h3>
            </div>
            <form method="POST" action="{{ route('admin.activities.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="add-activity-name">Activity / Event Name</label>
                    <input type="text" id="add-activity-name" name="name" class="form-input" required placeholder="e.g. Youth Camp 2026">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="add-activity-date">Event Date</label>
                    <input type="date" id="add-activity-date" name="event_date" class="form-input" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="add-activity-desc">Description & Milestone Notes</label>
                    <textarea id="add-activity-desc" name="description" class="form-input" rows="4" required placeholder="Describe the fellowship gathering, reflections, and milestones..."></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add-activity-photos">Gallery Photos</label>
                    <input type="file" id="add-activity-photos" name="photos[]" class="form-input" multiple accept="image/*">
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm">Save Activity</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Activity Modal --}}
    <div id="modal-edit-activity" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
        <div class="info-modal" style="width: min(580px, 92vw); padding: 32px; background: var(--white); border-radius: 24px; position: relative;">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-edit-activity" style="position: absolute; top: 20px; right: 20px; width: 34px; height: 34px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 20px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Activities & Events</span>
                <h3 style="font: 800 24px 'Manrope', sans-serif; color: var(--ink); margin: 4px 0 0;">Edit Activity</h3>
            </div>
            <form id="form-edit-activity" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="edit-activity-name">Activity Name</label>
                    <input type="text" id="edit-activity-name" name="name" class="form-input" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="edit-activity-date">Event Date</label>
                    <input type="date" id="edit-activity-date" name="event_date" class="form-input" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="edit-activity-desc">Description</label>
                    <textarea id="edit-activity-desc" name="description" class="form-input" rows="4" required></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="edit-activity-photos">Add Photos to Gallery</label>
                    <input type="file" id="edit-activity-photos" name="photos[]" class="form-input" multiple accept="image/*">
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm">Update Activity</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addModal = document.getElementById('modal-add-activity');
        const editModal = document.getElementById('modal-edit-activity');
        const btnOpenAdd = document.getElementById('btn-open-add-activity');
        const editForm = document.getElementById('form-edit-activity');
        const editName = document.getElementById('edit-activity-name');
        const editDate = document.getElementById('edit-activity-date');
        const editDesc = document.getElementById('edit-activity-desc');

        if (btnOpenAdd && addModal) {
            btnOpenAdd.addEventListener('click', () => {
                addModal.style.display = 'grid';
            });
        }

        document.querySelectorAll('.btn-edit-activity').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const date = this.getAttribute('data-date');
                const desc = this.getAttribute('data-description');

                if (editForm) {
                    editForm.action = `/admin/activities/${id}`;
                }
                if (editName) editName.value = name || '';
                if (editDate) editDate.value = date || '';
                if (editDesc) editDesc.value = desc || '';
                if (editModal) editModal.style.display = 'grid';
            });
        });

        document.querySelectorAll('.btn-close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                if (addModal) addModal.style.display = 'none';
                if (editModal) editModal.style.display = 'none';
            });
        });

        [addModal, editModal].forEach(m => {
            if (m) {
                m.addEventListener('click', (e) => {
                    if (e.target === m) m.style.display = 'none';
                });
            }
        });
    });
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Activities Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Community Fellowship</span>
        <h1>Activities Management</h1>
        <p>
            Record fellowship gatherings, manage event dates, and curate activity photo galleries.
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
    {{-- Activities Management Card --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Event History & Recaps</h2>
                <small style="color: var(--muted); font-size: 13px;">Total: {{ $totalCount }} activities recorded ({{ $totalPhotos }} photos uploaded)</small>
            </div>
            <a href="{{ route('activity') }}" class="button button-ghost button-sm" target="_blank" title="Preview public activity timeline">
                View Public Page &rarr;
            </a>
        </div>

        @if($activities->isEmpty())
            <div style="text-align: center; padding: 48px 24px; color: var(--muted);">
                <div style="font-size: 36px; margin-bottom: 8px;">✨</div>
                <h3>No activities recorded</h3>
                <p>Click "Add Activity" to document your first fellowship gathering.</p>
            </div>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Date</th>
                            <th>Activity Name</th>
                            <th>Description</th>
                            <th>Photos</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>
                                    <strong style="color: var(--forest); font-size: 14px;">
                                        {{ $activity->event_date->format('M j, Y') }}
                                    </strong>
                                </td>
                                <td>
                                    <strong style="color: var(--ink); font-size: 15px;">{{ $activity->name }}</strong>
                                </td>
                                <td>
                                    <span style="color: var(--muted); font-size: 13.5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $activity->description }}
                                    </span>
                                </td>
                                <td>
                                    <span class="role-badge" style="background: var(--cream); color: var(--forest);">
                                        📷 {{ $activity->photos->count() }} Photos
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="admin-action-group" style="justify-content: flex-end;">
                                        <button
                                            type="button"
                                            class="button button-secondary button-sm"
                                            style="font-size: 12px; padding: 4px 10px; height: 32px;"
                                        >
                                            ✎ Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="button button-danger button-sm"
                                            data-admin-confirm="Are you sure you want to remove activity '{{ $activity->name }}'?"
                                            data-action="{{ route('admin.activities.destroy', $activity->id) }}"
                                            data-method="DELETE"
                                            style="font-size: 12px; padding: 4px 10px; height: 32px;"
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
    </div>

    {{-- Add Activity Modal --}}
    <div id="modal-add-activity" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
        <div class="info-modal" style="width: min(560px, 92vw); padding: 32px; background: var(--white); border-radius: 24px; position: relative;">
            <button type="button" class="icon-button btn-close-activity-modal" style="position: absolute; top: 20px; right: 20px; width: 34px; height: 34px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 20px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Fellowship Events</span>
                <h3 style="font: 800 24px 'Manrope', sans-serif; color: var(--ink); margin: 4px 0 0;">Add New Activity</h3>
            </div>
            <form method="POST" action="{{ route('admin.activities.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="activity-name">Activity Title</label>
                    <input type="text" id="activity-name" name="name" class="form-input" required placeholder="e.g. Youth Camp 2026">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="activity-date">Event Date</label>
                    <input type="date" id="activity-date" name="event_date" class="form-input" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="activity-desc">Description & Recap</label>
                    <textarea id="activity-desc" name="description" class="form-input" rows="3" required placeholder="Share what made this gathering special..."></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="activity-photos">Gallery Photos</label>
                    <input type="file" id="activity-photos" name="photos[]" class="form-input" multiple accept="image/*">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="button button-ghost button-sm btn-close-activity-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm">Save Activity</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('modal-add-activity');
    const openBtn = document.getElementById('btn-open-add-activity');

    if (openBtn && addModal) {
        openBtn.addEventListener('click', () => {
            addModal.style.display = 'grid';
            document.body.style.overflow = 'hidden';
        });
    }

    document.querySelectorAll('.btn-close-activity-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            if (addModal) addModal.style.display = 'none';
            document.body.style.overflow = '';
        });
    });
});
</script>
@endpush

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
           Add Activity
        </button>
    </div>
</div>
@endsection

@section('content')
    {{-- Search & Filtering Bar --}}
    <div class="admin-card" style="background: var(--cream); border: 1px solid var(--line); border-radius: 16px; padding: 18px 22px; margin-bottom: 24px;">
        <form method="GET" action="{{ route('admin.activities') }}" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Search Activity</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name or description..." class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 150px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Filter by Date</label>
                <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 180px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Sort By</label>
                <select name="sort" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="date_desc" {{ ($filters['sort'] ?? '') === 'date_desc' ? 'selected' : '' }}>Date (Newest First)</option>
                    <option value="date_asc" {{ ($filters['sort'] ?? '') === 'date_asc' ? 'selected' : '' }}>Date (Oldest First)</option>
                    <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Name (A – Z)</option>
                    <option value="name_desc" {{ ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' }}>Name (Z – A)</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="button button-primary button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Filter
                </button>
                <a href="{{ route('admin.activities') }}" class="button button-danger button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Activities List --}}
    @if($activities->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 48px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px;">
            <div style="font-size: 36px; margin-bottom: 12px;">🌱</div>
            <h2>No Activities Recorded Yet</h2>
            <p style="max-width: 480px; margin: 0 auto; color: var(--muted);">
                @if(!empty($filters['search']) || !empty($filters['date']))
                    No activities match your current search filters. Try resetting the filters.
                @else
                    Click "Add Activity" above to document your first fellowship gathering.
                @endif
            </p>
        </div>
    @else
        {{-- Batch Actions Bar --}}
        <form id="form-batch-delete-activities" method="POST" action="{{ route('admin.activities.batch-delete') }}" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; background: var(--white); border: 1px solid var(--line); border-radius: 14px; padding: 12px 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            @csrf
            <div style="display: flex; align-items: center; gap: 12px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--ink); cursor: pointer; user-select: none;">
                    <input type="checkbox" id="check-select-all-activities" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;">
                    <span>Select All</span>
                </label>
                <span id="batch-selected-count-activities" style="font-size: 12px; color: var(--muted); font-weight: 600;">(0 selected)</span>
            </div>
            <div>
                <button type="button" class="button button-danger button-sm" id="btn-batch-delete-activities" disabled style="opacity: 0.5; height: 32px; font-size: 12px;">
                    Delete Selected
                </button>
            </div>
        </form>

        <div class="activities-list" style="display: flex; flex-direction: column; gap: 24px;">
            @foreach($activities as $activity)
                @php
                    $startDateStr = $activity->start_date ? $activity->start_date->format('Y-m-d') : ($activity->event_date ? $activity->event_date->format('Y-m-d') : '');
                    $endDateStr = $activity->end_date ? $activity->end_date->format('Y-m-d') : $startDateStr;
                @endphp
                <article class="activity-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 24px 28px; box-shadow: var(--shadow);">
                    <div class="activity-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 6px; flex-wrap: wrap;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                <input type="checkbox" name="ids[]" value="{{ $activity->id }}" form="form-batch-delete-activities" class="activity-batch-checkbox" style="width: 18px; height: 18px; accent-color: var(--forest); cursor: pointer;" title="Select for batch delete">
                                <span class="activity-date" style="display: inline-block; font-size: 13px; font-weight: 700; color: var(--forest); background: var(--cream); padding: 4px 12px; border-radius: 20px;">
                                    📅 {{ $activity->formatted_date_range }}
                                </span>
                            </div>
                            <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; font-weight: 800; color: var(--ink); margin: 0;">
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
                                data-start-date="{{ $startDateStr }}"
                                data-end-date="{{ $endDateStr }}"
                                data-date="{{ $startDateStr }}"
                                data-description="{{ $activity->description }}"
                                data-photos="{{ json_encode($activity->photos->map(fn($p) => ['id' => $p->id, 'url' => $p->getUrl(), 'name' => $p->original_name])) }}"
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

                    <div class="activity-description" style="color: var(--muted); font-size: 14.5px; line-height: 1.6; margin-top: 4px; margin-bottom: 18px; white-space: pre-line;">{{ trim($activity->description) }}</div>

                    {{-- Supporting Photos Gallery (Preserves Original Aspect Ratio) --}}
                    @if($activity->photos->isNotEmpty())
                        <div class="activity-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; margin-top: 14px;">
                            @foreach($activity->photos as $photo)
                                <a href="{{ $photo->getUrl() }}" target="_blank" rel="noopener noreferrer" class="activity-photo-item" style="display: flex; align-items: center; justify-content: center; border-radius: 12px; overflow: hidden; border: 1px solid var(--line); min-height: 130px; max-height: 180px; background: #141f17; position: relative; transition: transform .2s ease;">
                                    <img
                                        src="{{ $photo->getUrl() }}"
                                        alt="{{ $activity->name }} Photo"
                                        loading="lazy"
                                        style="max-width: 100%; max-height: 180px; width: auto; height: auto; object-fit: contain; display: block;"
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
    <div id="modal-add-activity" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-add-activity-title">
        <div class="info-modal" style="width: min(800px, 95vw); max-height: calc(100vh - 40px); overflow-y: auto; padding: 24px 28px; background: var(--white); border-radius: 22px; position: relative; box-shadow: var(--shadow);">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-add-activity" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 14px; padding-right: 32px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Activities & Events</span>
                <h3 id="modal-add-activity-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Add New Activity</h3>
            </div>

            <form method="POST" action="{{ route('admin.activities.store') }}" enctype="multipart/form-data" id="form-add-activity">
                @csrf
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="add-activity-name" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                        Activity / Event Name <span style="color: var(--red);">*</span>
                    </label>
                    <input type="text" id="add-activity-name" name="name" class="form-input" required placeholder="e.g. Youth Camp 2026" style="height: 38px; font-size: 13.5px;">
                </div>

                {{-- Start Date & End Date (End Date unlocked upon choosing Start Date) --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="add-start-date" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                            Event Start Date <span style="color: var(--red);">*</span>
                        </label>
                        <input type="date" id="add-start-date" name="start_date" class="form-input" required style="height: 38px; font-size: 13.5px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="add-end-date" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                            Event End Date <span style="color: var(--red);">*</span>
                        </label>
                        <input type="date" id="add-end-date" name="end_date" class="form-input" required disabled style="height: 38px; font-size: 13.5px; opacity: 0.6; cursor: not-allowed;">
                        <small id="add-end-date-hint" style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px;">
                            Choose start date first.
                        </small>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="add-activity-desc" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                        Description & Milestone Notes <span style="color: var(--red);">*</span>
                    </label>
                    <textarea id="add-activity-desc" name="description" class="form-input" rows="3" required placeholder="Describe the fellowship gathering, reflections, and milestones..." style="font-size: 13.5px; resize: vertical;"></textarea>
                </div>

                {{-- Gallery Photos (Accepts Any Size, Keeps Original Aspect Ratio) --}}
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                        Gallery Photos (Original Aspect Ratio)
                    </label>
                    
                    <div id="add-photos-dropzone" class="crop-dropzone" style="padding: 14px; text-align: center; border-radius: 12px; cursor: pointer;">
                        <span style="font-size: 20px; display: block; margin-bottom: 2px;">🖼️</span>
                        <div style="font-weight: 700; font-size: 13px; color: var(--ink);">Click to select photos or drag here</div>
                        <small style="color: var(--muted); font-size: 11px; display: block;">Supports any file size. Automatically optimized while maintaining crisp quality.</small>
                    </div>
                    <input type="file" id="add-activity-photos" name="photos[]" multiple accept="image/*" style="display: none;">

                    {{-- Live Selected Photos Preview Strip (Natural Aspect Ratio) --}}
                    <div id="add-photos-preview-strip" style="display: none; margin-top: 10px; max-height: 160px; overflow-x: auto; overflow-y: hidden; display: none; gap: 10px; padding: 10px; border: 1px solid var(--line); border-radius: 12px; background: var(--cream);"></div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 10px; border-top: 1px solid var(--line);">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm" id="btn-submit-add-activity">
                        Save Activity
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Activity Modal --}}
    <div id="modal-edit-activity" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-edit-activity-title">
        <div class="info-modal" style="width: min(800px, 95vw); max-height: calc(100vh - 40px); overflow-y: auto; padding: 24px 28px; background: var(--white); border-radius: 22px; position: relative; box-shadow: var(--shadow);">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-edit-activity" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 14px; padding-right: 32px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Activities & Events</span>
                <h3 id="modal-edit-activity-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">Edit Activity</h3>
            </div>

            <form id="form-edit-activity" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="edit-activity-name" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                        Activity Name <span style="color: var(--red);">*</span>
                    </label>
                    <input type="text" id="edit-activity-name" name="name" class="form-input" required style="height: 38px; font-size: 13.5px;">
                </div>

                {{-- Start Date & End Date (Locked until Start Date is valid) --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="edit-start-date" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                            Event Start Date <span style="color: var(--red);">*</span>
                        </label>
                        <input type="date" id="edit-start-date" name="start_date" class="form-input" required style="height: 38px; font-size: 13.5px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="edit-end-date" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                            Event End Date <span style="color: var(--red);">*</span>
                        </label>
                        <input type="date" id="edit-end-date" name="end_date" class="form-input" required style="height: 38px; font-size: 13.5px;">
                        <small id="edit-end-date-hint" style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px;">
                            Defaults to start date for 1-day events.
                        </small>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="edit-activity-desc" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                        Description <span style="color: var(--red);">*</span>
                    </label>
                    <textarea id="edit-activity-desc" name="description" class="form-input" rows="3" required style="font-size: 13.5px; resize: vertical;"></textarea>
                </div>

                {{-- Existing Photos Management (Natural Aspect Ratio) --}}
                <div id="edit-activity-photos-container" style="display: none; margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 6px;">
                        Current Gallery Photos (Check to remove)
                    </label>
                    <div id="edit-photos-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; max-height: 280px; overflow-y: auto; padding: 12px; border: 1px solid var(--line); border-radius: 12px; background: var(--cream);"></div>
                </div>

                {{-- Add New Photos (Natural Aspect Ratio) --}}
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 700; font-size: 12.5px; display: block; margin-bottom: 4px;">
                        Add More Photos to Gallery (Original Aspect Ratio)
                    </label>
                    <div id="edit-photos-dropzone" class="crop-dropzone" style="padding: 14px; text-align: center; border-radius: 12px; cursor: pointer;">
                        <span style="font-size: 20px; display: block; margin-bottom: 2px;">📷</span>
                        <div style="font-weight: 700; font-size: 13px; color: var(--ink);">Select photos to append to gallery</div>
                    </div>
                    <input type="file" id="edit-activity-photos" name="photos[]" multiple accept="image/*" style="display: none;">
                    <div id="edit-photos-preview-strip" style="display: none; margin-top: 10px; max-height: 160px; overflow-x: auto; overflow-y: hidden; display: none; gap: 10px; padding: 10px; border: 1px solid var(--line); border-radius: 12px; background: var(--cream);"></div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 10px; border-top: 1px solid var(--line);">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm" id="btn-submit-edit-activity">
                        Update Activity
                    </button>
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

    const formAdd = document.getElementById('form-add-activity');
    const formEdit = document.getElementById('form-edit-activity');

    const addStartDate = document.getElementById('add-start-date');
    const addEndDate = document.getElementById('add-end-date');
    const addEndDateHint = document.getElementById('add-end-date-hint');

    const editStartDate = document.getElementById('edit-start-date');
    const editEndDate = document.getElementById('edit-end-date');
    const editName = document.getElementById('edit-activity-name');
    const editDesc = document.getElementById('edit-activity-desc');
    const photosContainer = document.getElementById('edit-activity-photos-container');
    const photosList = document.getElementById('edit-photos-list');

    // 1. Add Activity Date Rules:
    // Initially end_date is disabled until start_date is chosen.
    // Default: end_date = start_date. Cannot select end_date prior to start_date.
    addStartDate.addEventListener('change', function () {
        if (this.value) {
            addEndDate.disabled = false;
            addEndDate.style.opacity = '1';
            addEndDate.style.cursor = 'default';
            addEndDate.min = this.value;
            if (!addEndDate.value || addEndDate.value < this.value) {
                addEndDate.value = this.value;
            }
            if (addEndDateHint) {
                addEndDateHint.textContent = 'Defaults to same day; change if multi-day.';
            }
        } else {
            addEndDate.disabled = true;
            addEndDate.value = '';
            addEndDate.style.opacity = '0.6';
            addEndDate.style.cursor = 'not-allowed';
            if (addEndDateHint) {
                addEndDateHint.textContent = 'Choose start date first.';
            }
        }
    });

    addEndDate.addEventListener('change', function () {
        if (addStartDate.value && this.value < addStartDate.value) {
            this.value = addStartDate.value;
        }
    });

    formAdd.addEventListener('submit', function () {
        addEndDate.disabled = false; // ensure value is sent in request
    });

    // 2. Edit Activity Date Rules:
    editStartDate.addEventListener('change', function () {
        if (this.value) {
            editEndDate.min = this.value;
            if (!editEndDate.value || editEndDate.value < this.value) {
                editEndDate.value = this.value;
            }
        }
    });

    editEndDate.addEventListener('change', function () {
        if (editStartDate.value && this.value < editStartDate.value) {
            this.value = editStartDate.value;
        }
    });

    // 3. Multi-Photo Selection & Accumulation Helper (Supports multiple files, batch selections, and removal)
    function setupMultiPhotoPreview(dropzoneEl, fileInputEl, previewStripEl) {
        let accumulatedFiles = [];

        function syncToFileInput() {
            if (window.DataTransfer) {
                const dt = new DataTransfer();
                accumulatedFiles.forEach(f => dt.items.add(f));
                fileInputEl.files = dt.files;
            }
        }

        function renderPreviews() {
            previewStripEl.innerHTML = '';
            if (accumulatedFiles.length === 0) {
                previewStripEl.style.display = 'none';
                return;
            }

            previewStripEl.style.display = 'flex';
            accumulatedFiles.forEach((file, index) => {
                const card = document.createElement('div');
                card.style = 'flex: 0 0 auto; height: 95px; width: 130px; border-radius: 8px; overflow: hidden; border: 1px solid var(--line); background: #141f17; position: relative; display: flex; align-items: center; justify-content: center;';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.title = 'Remove this photo';
                removeBtn.innerHTML = '&times;';
                removeBtn.style = 'position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; background: rgba(189,76,66,0.92); color: #fff; border: none; border-radius: 50%; font-size: 14px; font-weight: 800; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 5;';
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    accumulatedFiles.splice(index, 1);
                    syncToFileInput();
                    renderPreviews();
                });

                const img = document.createElement('img');
                img.style = 'max-height: 95px; max-width: 130px; width: auto; height: auto; object-fit: contain; display: block;';
                img.alt = file.name;

                const nameLabel = document.createElement('span');
                nameLabel.style = 'position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.72); color: #fff; font-size: 10px; padding: 2px 4px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; text-align: center;';
                nameLabel.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                card.appendChild(img);
                card.appendChild(nameLabel);
                card.appendChild(removeBtn);
                previewStripEl.appendChild(card);
            });
        }

        dropzoneEl.addEventListener('click', () => fileInputEl.click());

        // Drag and drop support
        ['dragenter', 'dragover'].forEach(evt => {
            dropzoneEl.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzoneEl.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(evt => {
            dropzoneEl.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzoneEl.classList.remove('dragover');
            });
        });
        dropzoneEl.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                Array.from(dt.files).forEach(f => {
                    if (f.type.startsWith('image/')) accumulatedFiles.push(f);
                });
                syncToFileInput();
                renderPreviews();
            }
        });

        fileInputEl.addEventListener('change', function () {
            const newFiles = Array.from(this.files || []);
            newFiles.forEach(f => accumulatedFiles.push(f));
            syncToFileInput();
            renderPreviews();
        });

        return {
            clear: () => {
                accumulatedFiles = [];
                fileInputEl.value = '';
                previewStripEl.innerHTML = '';
                previewStripEl.style.display = 'none';
            }
        };
    }

    const addPhotosManager = setupMultiPhotoPreview(
        document.getElementById('add-photos-dropzone'),
        document.getElementById('add-activity-photos'),
        document.getElementById('add-photos-preview-strip')
    );

    const editPhotosManager = setupMultiPhotoPreview(
        document.getElementById('edit-photos-dropzone'),
        document.getElementById('edit-activity-photos'),
        document.getElementById('edit-photos-preview-strip')
    );

    // 4. Modal Open & Close logic
    function openModal(modalEl) {
        modalEl.style.display = 'grid';
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    function closeAllModals() {
        if (addModal) addModal.style.display = 'none';
        if (editModal) editModal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }

    if (btnOpenAdd && addModal) {
        btnOpenAdd.addEventListener('click', () => {
            formAdd.reset();
            if (window.setDatePickerValue) {
                window.setDatePickerValue(addStartDate, '');
                window.setDatePickerValue(addEndDate, '');
            }
            addEndDate.disabled = true;
            addEndDate.value = '';
            addEndDate.style.opacity = '0.6';
            addEndDate.style.cursor = 'not-allowed';
            if (addEndDateHint) addEndDateHint.textContent = 'Choose start date first.';
            addPhotosManager.clear();
            openModal(addModal);
        });
    }

    document.querySelectorAll('.btn-edit-activity').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const startDate = this.getAttribute('data-start-date') || this.getAttribute('data-date') || '';
            const endDate = this.getAttribute('data-end-date') || startDate;
            const desc = this.getAttribute('data-description');
            const photosRaw = this.getAttribute('data-photos');
            const photos = JSON.parse(photosRaw || '[]');

            if (formEdit) {
                formEdit.action = `/admin/activities/${id}`;
            }
            if (editName) editName.value = name || '';
            if (editStartDate) {
                if (window.setDatePickerValue) {
                    window.setDatePickerValue(editStartDate, startDate);
                    if (editEndDate) {
                        editEndDate.disabled = false;
                        editEndDate.min = startDate;
                        window.setDatePickerValue(editEndDate, endDate);
                    }
                } else {
                    editStartDate.value = startDate;
                    if (editEndDate) {
                        editEndDate.disabled = false;
                        editEndDate.min = startDate;
                        editEndDate.value = endDate;
                    }
                }
            }
            if (editDesc) editDesc.value = desc || '';

            editPhotosManager.clear();

            if (photosList) photosList.innerHTML = '';
            if (photos.length > 0 && photosContainer && photosList) {
                photosContainer.style.display = 'block';
                photos.forEach(photo => {
                    const item = document.createElement('div');
                    item.style = 'position: relative; border-radius: 10px; overflow: hidden; border: 1px solid var(--line); background: #141f17; height: 120px; display: flex; align-items: center; justify-content: center;';
                    item.innerHTML = `
                        <img src="${photo.url}" alt="${photo.name}" style="max-height: 120px; max-width: 100%; object-fit: contain; display: block;">
                        <label style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(189,76,66,0.92); color: #fff; font-size: 11px; padding: 4px 6px; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; font-weight: 700;">
                            <input type="checkbox" name="remove_photo_ids[]" value="${photo.id}"> Remove
                        </label>
                    `;
                    photosList.appendChild(item);
                });
            } else if (photosContainer) {
                photosContainer.style.display = 'none';
            }

            if (editModal) openModal(editModal);
        });
    });

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

    // Batch Delete Activities Selection & Confirmation
    const selectAllCheckbox = document.getElementById('check-select-all-activities');
    const batchCheckboxes = document.querySelectorAll('.activity-batch-checkbox');
    const batchCountSpan = document.getElementById('batch-selected-count-activities');
    const btnBatchDelete = document.getElementById('btn-batch-delete-activities');
    const batchForm = document.getElementById('form-batch-delete-activities');

    function updateBatchDeleteState() {
        const checkedBoxes = document.querySelectorAll('.activity-batch-checkbox:checked');
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
            const count = document.querySelectorAll('.activity-batch-checkbox:checked').length;
            if (count === 0) return;

            if (typeof window.openAdminConfirm === 'function') {
                window.openAdminConfirm({
                    title: 'Delete Selected Activities',
                    message: `Are you sure you want to delete ${count} selected activity(ies) and all associated photo records? This action cannot be undone.`,
                    confirmText: 'Yes, Delete Selected',
                    buttonClass: 'button-danger',
                    onConfirm: function () {
                        batchForm.submit();
                    }
                });
            } else {
                if (confirm(`Are you sure you want to delete ${count} selected activity(ies)?`)) {
                    batchForm.submit();
                }
            }
        });
    }
});
</script>
@endpush

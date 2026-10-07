@extends('layouts.admin')

@section('title', 'Birthday Wishes Administration — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Celebration Moderation & Archive</span>
        <h1>Birthday Wishes Administration</h1>
        <p>
            Audit and moderate fellowship birthday wishes across all celebrants and historical years. True sender identities are confidential and revealed only to administrators.
        </p>
    </div>
</div>
@endsection

@section('content')
    {{-- Quick Year Archive Tabs --}}
    <div class="admin-card" id="wishes-archive-years-card" style="padding: 16px 20px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-right: 6px;">
                    Archive Years:
                </span>
                <a href="{{ route('admin.birthday-wishes', array_merge(request()->except(['year', 'page']), ['year' => 'all'])) }}"
                   class="button button-sm wish-year-link {{ $selectedYear === null ? 'button-primary' : 'button-ghost' }}"
                   style="border-radius: 99px; padding: 4px 12px; font-size: 12.5px;">
                    All Years
                </a>
                @foreach($years as $yr)
                    <a href="{{ route('admin.birthday-wishes', array_merge(request()->except(['year', 'page']), ['year' => $yr])) }}"
                       class="button button-sm wish-year-link {{ (string)$selectedYear === (string)$yr ? 'button-primary' : 'button-ghost' }}"
                       style="border-radius: 99px; padding: 4px 12px; font-size: 12.5px;">
                        {{ $yr }} @if($yr === $currentYear)<span style="opacity: 0.8; font-size: 11px;">(Current)</span>@endif
                    </a>
                @endforeach
            </div>

            <div style="font-size: 12.5px; color: var(--muted);">
                Total: <strong style="color: var(--forest);">{{ $totalLetters }}</strong> historical wishes recorded
            </div>
        </div>
    </div>

    {{-- Filtering & Search Controls --}}
    <div class="admin-card" id="wishes-filter-card" style="padding: 18px 24px; margin-bottom: 24px;">
        <form id="form-wishes-filter" method="GET" action="{{ route('admin.birthday-wishes') }}" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="year" value="{{ $selectedYear === null ? 'all' : $selectedYear }}">

            <div style="flex: 1; min-width: 200px;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Filter by Celebrant</label>
                <select name="member_id" id="filter-wishes-member" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="">All Birthday Celebrants</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ (string)$selectedMemberId === (string)$m->id ? 'selected' : '' }}>
                            {{ $m->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="flex: 1; min-width: 200px;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Search Keyword</label>
                <input type="text" name="search" value="{{ $searchKeyword }}" placeholder="Search message, sender, email..." class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 170px;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Sort By</label>
                <select name="sort" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="newest" {{ ($sort ?? '') === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    <option value="recipient_asc" {{ ($sort ?? '') === 'recipient_asc' ? 'selected' : '' }}>Recipient (A – Z)</option>
                    <option value="recipient_desc" {{ ($sort ?? '') === 'recipient_desc' ? 'selected' : '' }}>Recipient (Z – A)</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="button button-primary button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Filter
                </button>
                <a href="{{ route('admin.birthday-wishes') }}" class="button button-danger button-sm wish-reset-link" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Letters Ledger Table --}}
    <div class="admin-card" id="wishes-ledger-card">
        <div class="admin-card-header">
            <div>
                <h2>
                    Birthday Letters Ledger
                    @if($selectedYear)
                        <span style="font-size: 16px; color: var(--forest); font-weight: 700;">({{ $selectedYear }})</span>
                    @else
                        <span style="font-size: 16px; color: var(--forest); font-weight: 700;">(All Historical Years)</span>
                    @endif
                </h2>
                <small style="color: var(--muted); font-size: 13px;">Showing {{ $letters->count() }} of {{ $letters->total() }} matching wishes</small>
            </div>
        </div>

        @if($letters->isEmpty())
            <div style="text-align: center; padding: 48px 24px; color: var(--muted);">
                <div style="font-size: 36px; margin-bottom: 8px;">💌</div>
                <h3>No birthday wishes found</h3>
                <p>Try switching the year tab or clearing your filter criteria.</p>
            </div>
        @else
            {{-- Batch Actions Bar --}}
            <form id="form-batch-delete-wishes" method="POST" action="{{ route('admin.birthday-wishes.batch-delete') }}" style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 10px 16px;">
                @csrf
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span id="batch-selected-count-wishes" style="font-size: 13px; font-weight: 700; color: var(--ink);">0 wishes selected</span>
                </div>
                <div>
                    <button type="button" class="button button-danger button-sm" id="btn-batch-delete-wishes" disabled style="opacity: 0.5; height: 32px; font-size: 12px;">
                        Delete Selected
                    </button>
                </div>
            </form>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="check-select-all-wishes" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;" title="Select all on this page">
                            </th>
                            <th>Celebrant (Recipient)</th>
                            <th>Sender Identity (Admin View)</th>
                            <th>Year</th>
                            <th>Wish Message</th>
                            <th>Sent At</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($letters as $letter)
                            @php
                                $realSenderName = $letter->user
                                    ? ($letter->user->member ? $letter->user->member->full_name : $letter->user->name)
                                    : ($letter->sender_name ?: 'A BYC Friend');
                            @endphp
                            <tr id="letter-row-{{ $letter->id }}">
                                <td style="text-align: center;">
                                    <input type="checkbox" name="ids[]" value="{{ $letter->id }}" form="form-batch-delete-wishes" class="wish-batch-checkbox" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;">
                                </td>
                                <td>
                                    <strong style="color: var(--ink); font-size: 14px;">
                                        {{ $letter->recipient->full_name ?? 'Unknown Celebrant' }}
                                    </strong>
                                </td>
                                <td>
                                    <div style="font-size: 13.5px; font-weight: 600; color: var(--forest);">
                                        {{ $realSenderName }}
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px;">
                                        @if($letter->is_anonymous)
                                            <span class="role-badge" style="background: #fdf0ee; color: var(--red); font-size: 10.5px; padding: 1px 7px;">
                                                Anonymous to Recipient
                                            </span>
                                        @else
                                            <span class="role-badge" style="background: #eaf3dc; color: var(--forest); font-size: 10.5px; padding: 1px 7px;">
                                                Public to Recipient
                                            </span>
                                        @endif
                                        @if($letter->user)
                                            <small style="color: var(--muted); font-size: 11px;">({{ $letter->user->email }})</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge" style="background: var(--paper); color: var(--ink);">
                                        {{ $letter->birthday_year }}
                                    </span>
                                </td>
                                <td>
                                    <span style="color: var(--ink); font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 340px; line-height: 1.5;">
                                        "{{ $letter->message }}"
                                    </span>
                                </td>
                                <td>
                                    <span style="color: var(--muted); font-size: 12.5px;">
                                        {{ $letter->created_at->format('M j, Y H:i') }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="admin-action-group" style="justify-content: flex-end; gap: 6px;">
                                        <button
                                            type="button"
                                            class="button button-ghost button-sm btn-view-wish"
                                            data-id="{{ $letter->id }}"
                                            data-recipient="{{ $letter->recipient->full_name ?? 'Unknown' }}"
                                            data-sender="{{ $realSenderName }}"
                                            data-email="{{ $letter->user ? $letter->user->email : '-' }}"
                                            data-year="{{ $letter->birthday_year }}"
                                            data-anonymous="{{ $letter->is_anonymous ? '1' : '0' }}"
                                            data-message="{{ $letter->message }}"
                                            data-date="{{ $letter->created_at->format('M j, Y H:i:s') }}"
                                            style="font-size: 12px; padding: 4px 8px; height: 30px;"
                                            title="View Details"
                                        >
                                            View
                                        </button>

                                        <button
                                            type="button"
                                            class="button button-ghost button-sm btn-edit-wish"
                                            data-id="{{ $letter->id }}"
                                            data-recipient="{{ $letter->recipient->full_name ?? 'Unknown' }}"
                                            data-message="{{ $letter->message }}"
                                            data-anonymous="{{ $letter->is_anonymous ? '1' : '0' }}"
                                            style="font-size: 12px; padding: 4px 8px; height: 30px;"
                                            title="Edit Wish"
                                        >
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('admin.birthday-wishes.destroy', $letter->id) }}" style="display: inline; margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="button button-danger button-sm"
                                                style="font-size: 12px; padding: 4px 8px; height: 30px;"
                                                data-admin-confirm="Are you sure you want to delete this birthday wish for {{ $letter->recipient->full_name ?? 'Celebrant' }}? This action cannot be undone."
                                                title="Delete Wish"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($letters->hasPages())
                <div style="margin-top: 20px;">
                    {{ $letters->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- View Wish Detail Modal --}}
    <div id="modal-view-wish" class="admin-modal-backdrop modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 20px; overscroll-behavior: contain;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 18px; max-width: 540px; width: 100%; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <div style="padding: 20px 24px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">💌</span>
                    <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 18px; font-weight: 800; color: var(--ink);">
                        Birthday Wish Details
                    </h3>
                </div>
                <button type="button" class="btn-close-modal" data-target="modal-view-wish" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <small style="color: var(--muted); text-transform: uppercase; font-size: 11px; font-weight: 700;">Celebrant</small>
                        <div id="view-recipient" style="font-weight: 700; color: var(--ink); font-size: 15px; margin-top: 2px;"></div>
                    </div>
                    <div>
                        <small style="color: var(--muted); text-transform: uppercase; font-size: 11px; font-weight: 700;">Birthday Year</small>
                        <div id="view-year" style="font-weight: 700; color: var(--forest); font-size: 15px; margin-top: 2px;"></div>
                    </div>
                </div>

                <div style="background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin-bottom: 20px;">
                    <small style="color: var(--muted); text-transform: uppercase; font-size: 11px; font-weight: 700; display: block; margin-bottom: 6px;">Confidential Sender Identity (Admin Only)</small>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <strong id="view-sender" style="color: var(--ink); font-size: 14px;"></strong>
                            <small id="view-email" style="color: var(--muted); display: block; font-size: 12px;"></small>
                        </div>
                        <span id="view-status-badge" class="role-badge"></span>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <small style="color: var(--muted); text-transform: uppercase; font-size: 11px; font-weight: 700; display: block; margin-bottom: 6px;">Wish Message</small>
                    <div id="view-message" style="background: var(--white); border: 1px solid var(--line); border-radius: 12px; padding: 16px; font-size: 14px; line-height: 1.6; color: var(--ink); white-space: pre-wrap; max-height: 220px; overflow-y: auto;"></div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <small id="view-date" style="color: var(--muted); font-size: 12px;"></small>
                    <button type="button" class="button button-ghost button-sm btn-close-modal" data-target="modal-view-wish">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Wish Modal --}}
    <div id="modal-edit-wish" class="admin-modal-backdrop modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 20px; overscroll-behavior: contain;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 18px; max-width: 520px; width: 100%; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <form id="form-edit-wish" method="POST" action="">
                @csrf
                @method('PUT')
                <div style="padding: 20px 24px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">✏️</span>
                        <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 18px; font-weight: 800; color: var(--ink);">
                            Edit Birthday Wish
                        </h3>
                    </div>
                    <button type="button" class="btn-close-modal" data-target="modal-edit-wish" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
                </div>
                <div style="padding: 24px;">
                    <div style="margin-bottom: 16px;">
                        <small style="color: var(--muted); text-transform: uppercase; font-size: 11px; font-weight: 700;">Celebrant</small>
                        <div id="edit-recipient" style="font-weight: 700; color: var(--ink); font-size: 15px; margin-top: 2px;"></div>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="form-label" for="edit-message-input">Letter Message *</label>
                        <textarea
                            id="edit-message-input"
                            name="message"
                            class="form-input"
                            rows="5"
                            required
                            minlength="3"
                            maxlength="3000"
                            style="font-family: inherit; font-size: 14px; line-height: 1.5; resize: vertical;"
                        ></textarea>
                    </div>

                    <div style="margin-bottom: 24px; padding: 12px 14px; background: var(--paper); border: 1px solid var(--line); border-radius: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                            <input type="checkbox" id="edit-anonymous-checkbox" name="is_anonymous" value="1" style="width: 18px; height: 18px; accent-color: var(--forest); cursor: pointer;">
                            <span style="font-size: 13.5px; font-weight: 600; color: var(--ink);">
                                Send as Anonymous to recipient
                            </span>
                        </label>
                        <small style="color: var(--muted); display: block; font-size: 11.5px; margin-top: 4px; padding-left: 28px;">
                            When enabled, recipient will only see "Anonymous". Sender identity remains stored for admin audit.
                        </small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="button button-ghost button-sm btn-close-modal" data-target="modal-edit-wish">
                            Cancel
                        </button>
                        <button type="submit" class="button button-primary button-sm" style="min-width: 120px;">
                            Save Changes
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
    let isWishesLoading = false;

    // Smooth AJAX data loader (Zero page reload, preserves scroll position)
    async function loadWishesAjax(url, pushState = true) {
        if (isWishesLoading) return;
        isWishesLoading = true;

        const ledgerCard = document.getElementById('wishes-ledger-card');
        const yearsCard = document.getElementById('wishes-archive-years-card');
        const filterCard = document.getElementById('wishes-filter-card');

        if (ledgerCard) {
            ledgerCard.style.transition = 'opacity 0.2s ease';
            ledgerCard.style.opacity = '0.45';
            ledgerCard.style.pointerEvents = 'none';
        }

        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('Network error: ' + res.status);

            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Update Archive Years Bar
            const newYearsCard = doc.getElementById('wishes-archive-years-card');
            if (newYearsCard && yearsCard) {
                yearsCard.innerHTML = newYearsCard.innerHTML;
            }

            // 2. Update Filter Bar
            const newFilterCard = doc.getElementById('wishes-filter-card');
            if (newFilterCard && filterCard) {
                filterCard.innerHTML = newFilterCard.innerHTML;
            }

            // 3. Update Ledger Table Card
            const newLedgerCard = doc.getElementById('wishes-ledger-card');
            if (newLedgerCard && ledgerCard) {
                ledgerCard.innerHTML = newLedgerCard.innerHTML;
            }

            if (pushState) {
                window.history.pushState({ wishesAjax: true }, '', url);
            }

            updateBatchDeleteState();
        } catch (err) {
            console.error('Failed to load birthday wishes via AJAX:', err);
            // Fallback to normal navigation if fetch fails
            window.location.href = url;
        } finally {
            if (ledgerCard) {
                ledgerCard.style.opacity = '1';
                ledgerCard.style.pointerEvents = '';
            }
            isWishesLoading = false;
        }
    }

    // Intercept clicks on Year links, Reset link, and Pagination links
    document.addEventListener('click', function (e) {
        const yearLink = e.target.closest('.wish-year-link');
        if (yearLink) {
            e.preventDefault();
            loadWishesAjax(yearLink.href);
            return;
        }

        const resetLink = e.target.closest('.wish-reset-link');
        if (resetLink) {
            e.preventDefault();
            loadWishesAjax(resetLink.href);
            return;
        }

        const pageLink = e.target.closest('#wishes-ledger-card nav a, #wishes-ledger-card .pagination a');
        if (pageLink) {
            e.preventDefault();
            loadWishesAjax(pageLink.href);
            return;
        }

        // View Modal Logic (Delegated)
        const viewBtn = e.target.closest('.btn-view-wish');
        if (viewBtn) {
            const data = viewBtn.dataset;
            document.getElementById('view-recipient').textContent = data.recipient || 'Unknown';
            document.getElementById('view-year').textContent = data.year || '';
            document.getElementById('view-sender').textContent = data.sender || '';
            document.getElementById('view-email').textContent = (data.email && data.email !== '-') ? data.email : '';
            document.getElementById('view-message').textContent = data.message || '';
            document.getElementById('view-date').textContent = 'Sent on ' + (data.date || '');

            const badge = document.getElementById('view-status-badge');
            if (badge) {
                if (data.anonymous === '1') {
                    badge.style.background = '#fdf0ee';
                    badge.style.color = 'var(--red)';
                    badge.textContent = 'Anonymous to Recipient';
                } else {
                    badge.style.background = '#eaf3dc';
                    badge.style.color = 'var(--forest)';
                    badge.textContent = 'Public to Recipient';
                }
            }

            const modal = document.getElementById('modal-view-wish');
            if (modal) {
                modal.style.display = 'flex';
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }
            return;
        }

        // Edit Modal Logic (Delegated)
        const editBtn = e.target.closest('.btn-edit-wish');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const recipient = editBtn.dataset.recipient || 'Unknown';
            const message = editBtn.dataset.message || '';
            const anonymous = editBtn.dataset.anonymous === '1';

            document.getElementById('edit-recipient').textContent = recipient;
            document.getElementById('edit-message-input').value = message;
            document.getElementById('edit-anonymous-checkbox').checked = anonymous;

            const form = document.getElementById('form-edit-wish');
            if (form) form.action = '/admin/birthday-wishes/' + id;

            const modal = document.getElementById('modal-edit-wish');
            if (modal) {
                modal.style.display = 'flex';
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }
            return;
        }

        // Close Modal buttons
        const closeBtn = e.target.closest('.btn-close-modal');
        if (closeBtn) {
            const targetId = closeBtn.dataset.target;
            if (targetId) {
                const targetModal = document.getElementById(targetId);
                if (targetModal) targetModal.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            }
            return;
        }
    });

    // Close Modals on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.admin-modal-backdrop').forEach(modal => {
                if (modal.style.display !== 'none') {
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                }
            });
        }
    });

    // Intercept Filter Form Submit
    document.addEventListener('submit', function (e) {
        if (e.target && e.target.id === 'form-wishes-filter') {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                if (value !== '' && value !== null) {
                    params.append(key, value);
                }
            }
            const queryStr = params.toString();
            const url = form.action + (queryStr ? '?' + queryStr : '');
            loadWishesAjax(url);
        }
    });

    // Trigger filter submit on Celebrant change
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'filter-wishes-member') {
            const form = e.target.form;
            if (form) {
                form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        }
    });

    // Support Browser Back / Forward buttons without full refresh
    window.addEventListener('popstate', function () {
        loadWishesAjax(window.location.href, false);
    });

    // Batch Delete Wishes State
    function updateBatchDeleteState() {
        const selectAllCheckbox = document.getElementById('check-select-all-wishes');
        const batchCheckboxes = document.querySelectorAll('.wish-batch-checkbox');
        const checkedBoxes = document.querySelectorAll('.wish-batch-checkbox:checked');
        const batchCountSpan = document.getElementById('batch-selected-count-wishes');
        const btnBatchDelete = document.getElementById('btn-batch-delete-wishes');
        const count = checkedBoxes.length;

        if (batchCountSpan) {
            batchCountSpan.textContent = `${count} wish${count === 1 ? '' : 'es'} selected`;
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

    // Batch checkbox selection delegation
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'check-select-all-wishes') {
            const checked = e.target.checked;
            document.querySelectorAll('.wish-batch-checkbox').forEach(cb => {
                cb.checked = checked;
            });
            updateBatchDeleteState();
        } else if (e.target && e.target.classList.contains('wish-batch-checkbox')) {
            updateBatchDeleteState();
        }
    });

    // Batch Delete confirmation
    document.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'btn-batch-delete-wishes') {
            const batchForm = document.getElementById('form-batch-delete-wishes');
            const count = document.querySelectorAll('.wish-batch-checkbox:checked').length;
            if (count === 0 || !batchForm) return;

            if (typeof window.openAdminConfirm === 'function') {
                window.openAdminConfirm({
                    title: 'Delete Selected Birthday Wishes',
                    message: `Are you sure you want to delete ${count} selected birthday wish${count === 1 ? '' : 'es'}? This action cannot be undone.`,
                    confirmText: 'Yes, Delete Selected',
                    buttonClass: 'button-danger',
                    onConfirm: function () {
                        batchForm.submit();
                    }
                });
            } else {
                if (confirm(`Are you sure you want to delete ${count} selected birthday wish(es)?`)) {
                    batchForm.submit();
                }
            }
        }
    });
});
</script>
@endpush

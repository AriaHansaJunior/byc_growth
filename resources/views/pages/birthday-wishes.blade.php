@extends('layouts.app')

@section('title', 'Birthday Wishes & Archive — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Back Navigation --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>
        <a href="{{ route('members') }}" class="back-nav-btn" style="margin-left: 12px;">
            <x-icon name="users" /> Members Directory
        </a>
    </nav>

    {{-- Page Header --}}
    <header class="page-header">
        @if($mode === 'admin')
            <span class="eyebrow">Administrative Management • Archive System</span>
            <h1>Birthday Wishes Directory</h1>
            <p>
                Complete administrative record of fellowship birthday letters across all members and years.
            </p>
        @elseif($mode === 'recipient')
            <span class="eyebrow">🎂 Birthday Celebration • Surabaya (Asia/Jakarta)</span>
            <h1>Birthday Wishes for {{ $recipientMember->full_name }}</h1>
            <p>
                Heartfelt blessings, prayers, and encouraging words received from your BYC fellowship family.
            </p>
        @else
            <span class="eyebrow">💌 Sent Birthday Wishes</span>
            <h1>My Birthday Wishes</h1>
            <p>
                Review and manage your heartfelt birthday greetings sent for today's celebration.
            </p>
        @endif
    </header>

    {{-- Filter & Context Bar --}}
    <div class="wishes-controls-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 20px 24px; margin-bottom: 28px; box-shadow: var(--shadow); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
        
        {{-- Left: Mode-Specific Context or Year Pills --}}
        <div>
            @if($mode === 'admin')
                <div style="font-size: 13px; font-weight: 700; color: var(--forest); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                    🛡️ Administrator Full Archive Access
                </div>
                <div style="font-size: 14px; color: var(--muted);">
                    Browsing {{ $wishes->count() }} total {{ \Illuminate\Support\Str::plural('wish', $wishes->count()) }}.
                </div>
            @elseif($mode === 'recipient')
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span style="font-size: 13px; font-weight: 800; color: var(--ink); text-transform: uppercase; letter-spacing: 0.5px;">
                        Select Year:
                    </span>
                    @if($selectedYear < $currentYear)
                        <span style="background: #fdf5d7; border: 1px solid var(--gold); color: #8e680a; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                            📁 Archived Year
                        </span>
                    @endif
                </div>

                {{-- Year Filter Tabs / Pills --}}
                <div class="year-pills" style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach($availableYears as $year)
                        @php
                            $isActive = (int)$year === (int)$selectedYear;
                            $isArchive = (int)$year < $currentYear;
                        @endphp
                        <a href="{{ route('birthday.wishes', ['year' => $year]) }}"
                           class="year-pill-link"
                           style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.2s ease;
                                  {{ $isActive ? 'background: var(--forest); color: var(--white); box-shadow: 0 4px 12px rgba(40, 78, 59, 0.25);' : 'background: var(--paper); border: 1px solid var(--line); color: var(--ink);' }}">
                            {{ $year }} {{ $isArchive ? '(Archive)' : '(Current)' }}
                        </a>
                    @endforeach
                </div>
            @else
                <div style="font-size: 13px; font-weight: 700; color: var(--forest); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                    Celebration Date: {{ $today->format('F d, Y') }}
                </div>
                <div style="font-size: 14px; color: var(--muted);">
                    You can edit your sent wish while the recipient's birthday is still today.
                </div>
            @endif
        </div>

        {{-- Right: Filter Controls (for Admin) --}}
        @if($mode === 'admin')
            <form method="GET" action="{{ route('birthday.wishes') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 0;">
                {{-- Filter by Member --}}
                <select name="member_id" class="input-field" style="padding: 8px 12px; border: 1px solid var(--line); border-radius: 10px; font-size: 13px; font-family: inherit; background: var(--white);">
                    <option value="">All Recipients</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ $selectedMemberId == $m->id ? 'selected' : '' }}>
                            {{ $m->full_name }}
                        </option>
                    @endforeach
                </select>

                {{-- Filter by Year --}}
                <select name="year" class="input-field" style="padding: 8px 12px; border: 1px solid var(--line); border-radius: 10px; font-size: 13px; font-family: inherit; background: var(--white);">
                    <option value="">All Years</option>
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                            {{ $year }} {{ (int)$year < $currentYear ? '(Archive)' : '' }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="button button-primary button-sm" style="height: 38px; padding: 0 16px; font-size: 13px;">
                    Filter
                </button>

                @if($selectedYear || $selectedMemberId)
                    <a href="{{ route('birthday.wishes') }}" class="button button-ghost button-sm" style="height: 38px; padding: 0 12px; font-size: 13px;">
                        Reset
                    </a>
                @endif
            </form>
        @endif
    </div>

    {{-- Main Wishes Grid --}}
    @if($wishes->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 56px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px; box-shadow: var(--shadow);">
            <div style="font-size: 44px; margin-bottom: 12px;">💌</div>
            @if($mode === 'recipient')
                @if($selectedYear < $currentYear)
                    <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; color: var(--ink); margin-bottom: 6px;">
                        No birthday wishes for this year.
                    </h2>
                    <p style="color: var(--muted); font-size: 14px; margin: 0;">
                        No letters were archived for {{ $selectedYear }}. Select another year from the archive tabs above.
                    </p>
                @else
                    <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; color: var(--ink); margin-bottom: 6px;">
                        No birthday wishes yet.
                    </h2>
                    <p style="color: var(--muted); font-size: 14px; margin: 0;">
                        Wishes sent by fellowship friends throughout your birthday will appear here.
                    </p>
                @endif
            @elseif($mode === 'admin')
                <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; color: var(--ink); margin-bottom: 6px;">
                    No birthday wishes found.
                </h2>
                <p style="color: var(--muted); font-size: 14px; margin: 0;">
                    No letters matched the selected filter criteria.
                </p>
            @else
                <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; color: var(--ink); margin-bottom: 6px;">
                    No birthday wishes sent for today's celebrants.
                </h2>
                <p style="color: var(--muted); font-size: 14px; margin: 0;">
                    You have not sent a birthday letter to today's celebrating members yet.
                </p>
            @endif
        </div>
    @else
        <div class="wishes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px;">
            @foreach($wishes as $wish)
                @php
                    $isArchived = $wish->birthday_year < $currentYear;
                    $canEdit = $wish->canBeEditedBy(auth()->user());
                @endphp
                <div class="wish-card" id="wish-card-{{ $wish->id }}" style="background: var(--white); border: {{ $isArchived ? '1px dashed var(--line)' : '1px solid var(--line)' }}; border-radius: 20px; padding: 24px; box-shadow: var(--shadow); display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    
                    {{-- Card Header: Sender Info & Badges --}}
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                            <div>
                                {{-- Sender Display State --}}
                                @if($wish->is_anonymous)
                                    <div style="display: inline-flex; align-items: center; gap: 6px; background: #eee8d3; color: var(--ink); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700;">
                                        <span>🕶️</span> Anonymous
                                    </div>
                                    @if(auth()->check() && auth()->user()->isAdmin())
                                        <div style="font-size: 11px; color: var(--forest); margin-top: 4px; font-weight: 600;">
                                            (Audit: {{ $wish->user ? ($wish->user->member ? $wish->user->member->full_name : $wish->user->name) : 'User #' . $wish->user_id }})
                                        </div>
                                    @endif
                                @else
                                    <div style="font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 700; color: var(--ink);">
                                        {{ $wish->display_name }}
                                    </div>
                                @endif

                                {{-- Recipient Info (Visible for Admin and Sender modes) --}}
                                @if($mode !== 'recipient' && $wish->member)
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                                        To: <strong style="color: var(--forest);">{{ $wish->member->full_name }}</strong>
                                    </div>
                                @endif
                            </div>

                            {{-- Year & Archive Badges --}}
                            <div style="text-align: right;">
                                <span style="background: var(--paper); border: 1px solid var(--line); color: var(--forest); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 800;">
                                    {{ $wish->birthday_year }}
                                </span>
                                @if($isArchived)
                                    <div style="margin-top: 4px;">
                                        <span style="background: #fdf5d7; border: 1px solid var(--gold); color: #8e680a; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; text-transform: uppercase;">
                                            Archive
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Message Content --}}
                        <div class="wish-message-body" style="background: var(--paper); border-left: 3px solid {{ $isArchived ? 'var(--line)' : 'var(--gold)' }}; padding: 14px 16px; border-radius: 0 12px 12px 0; margin-bottom: 16px;">
                            <p id="wish-text-{{ $wish->id }}" style="margin: 0; color: var(--ink); font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $wish->message }}</p>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--line); padding-top: 12px; margin-top: 6px;">
                        <span style="font-size: 11px; color: var(--muted);" title="Sent Timestamp">
                            {{ $wish->created_at->format('M d, Y • H:i') }}
                        </span>

                        @if($canEdit)
                            <button
                                type="button"
                                class="button button-ghost button-sm btn-open-edit-wish"
                                data-id="{{ $wish->id }}"
                                data-message="{{ $wish->message }}"
                                data-anonymous="{{ $wish->is_anonymous ? '1' : '0' }}"
                                style="font-size: 12px; padding: 4px 12px; height: 30px;"
                            >
                                ✏️ Edit Wish
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Edit Wish Modal Dialog --}}
<div class="game-modal-overlay" id="modal-edit-wish" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(32, 48, 41, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="game-modal" style="max-width: 500px; width: 92%; background: var(--white); border: 2px solid var(--gold); border-radius: 24px; padding: 32px 28px; box-shadow: 0 24px 60px rgba(32, 48, 41, 0.35); text-align: left; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 18px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 20px; color: var(--ink);">
                Edit Birthday Wish
            </h3>
            <button type="button" id="btn-close-edit-wish" style="background: none; border: none; font-size: 26px; cursor: pointer; color: var(--muted); line-height: 1;">
                &times;
            </button>
        </div>

        <form id="form-edit-wish">
            @csrf
            <input type="hidden" id="edit-wish-id" value="">

            <div style="margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--ink); cursor: pointer;">
                    <input type="checkbox" id="edit-wish-anonymous" name="is_anonymous" value="1" style="width: 16px; height: 16px; accent-color: var(--forest);">
                    Send Anonymously
                </label>
                <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 4px;">
                    Recipient sees "Anonymous", while administrator preserves audit trail.
                </small>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                    Birthday Message *
                </label>
                <textarea
                    id="edit-wish-message"
                    name="message"
                    required
                    rows="5"
                    class="input-field"
                    style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit; font-size: 14px; resize: vertical;"
                ></textarea>
                <div id="edit-wish-error" style="display: none; color: var(--red); font-size: 12px; margin-top: 6px; font-weight: 600;"></div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" id="btn-cancel-edit-wish" class="button button-ghost button-sm">
                    Cancel
                </button>
                <button type="submit" id="btn-save-edit-wish" class="button button-primary">
                    Update Wish
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('modal-edit-wish');
    const closeBtn = document.getElementById('btn-close-edit-wish');
    const cancelBtn = document.getElementById('btn-cancel-edit-wish');
    const form = document.getElementById('form-edit-wish');
    const errorEl = document.getElementById('edit-wish-error');
    const idInput = document.getElementById('edit-wish-id');
    const messageInput = document.getElementById('edit-wish-message');
    const anonymousInput = document.getElementById('edit-wish-anonymous');

    function hideEditModal() {
        if (editModal) editModal.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
    }

    if (closeBtn) closeBtn.addEventListener('click', hideEditModal);
    if (cancelBtn) cancelBtn.addEventListener('click', hideEditModal);

    if (editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) hideEditModal();
        });
    }

    document.querySelectorAll('.btn-open-edit-wish').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const message = btn.dataset.message;
            const isAnonymous = btn.dataset.anonymous === '1';

            if (idInput) idInput.value = id;
            if (messageInput) messageInput.value = message;
            if (anonymousInput) anonymousInput.checked = isAnonymous;
            if (errorEl) errorEl.style.display = 'none';

            if (editModal) editModal.style.display = 'flex';
        });
    });

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (errorEl) errorEl.style.display = 'none';

            const id = idInput.value;
            const message = messageInput.value.trim();
            const isAnonymous = anonymousInput.checked ? 1 : 0;

            if (!message || message.length < 3) {
                if (errorEl) {
                    errorEl.textContent = 'Please enter at least 3 characters.';
                    errorEl.style.display = 'block';
                }
                return;
            }

            try {
                const response = await fetch(`/birthday/letter/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        message: message,
                        is_anonymous: isAnonymous,
                    }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    hideEditModal();
                    // Update text in DOM immediately
                    const cardText = document.getElementById(`wish-text-${id}`);
                    if (cardText) cardText.textContent = message;
                    // Update button dataset
                    const triggerBtn = document.querySelector(`.btn-open-edit-wish[data-id="${id}"]`);
                    if (triggerBtn) {
                        triggerBtn.dataset.message = message;
                        triggerBtn.dataset.anonymous = isAnonymous ? '1' : '0';
                    }
                    window.location.reload();
                } else {
                    if (errorEl) {
                        errorEl.textContent = data.message || 'Failed to update birthday wish.';
                        errorEl.style.display = 'block';
                    }
                }
            } catch (err) {
                if (errorEl) {
                    errorEl.textContent = 'An unexpected error occurred. Please try again.';
                    errorEl.style.display = 'block';
                }
            }
        });
    }
});
</script>
@endsection

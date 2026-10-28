@extends('layouts.app')

@section('title', 'Sent Birthday Wishes — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Page Header --}}
    <header class="page-header" style="margin-bottom: 24px;">
        <span class="eyebrow">Fellowship Celebrations</span>
        <h1 style="margin: 0 0 8px;">Sent Birthday Wishes</h1>
        <p style="margin: 0; color: var(--muted);">
            Review the birthday greetings you have sent to other members.
        </p>
    </header>

    {{-- Mode Navigation Tabs (Birthday Wishes for Me / Sent Birthday Wishes / My Birthday Resolution) --}}
    <div class="wishes-nav-tabs" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px;">
        @if(auth()->user()->member)
            <a href="{{ route('birthday.wishes.received') }}" style="padding: 10px 22px; border-radius: 999px; font-weight: 700; font-size: 13.5px; text-decoration: none; background: var(--white); border: 1.5px solid var(--line); color: var(--ink); transition: all 0.2s ease;">
                Birthday Wishes for Me
            </a>
        @endif
        <a href="{{ route('birthday.wishes.sent') }}" style="padding: 10px 22px; border-radius: 999px; font-weight: 700; font-size: 13.5px; text-decoration: none; background: var(--forest); color: var(--white); box-shadow: 0 4px 12px rgba(44, 76, 56, 0.25);">
            Sent Birthday Wishes
        </a>
        @if(auth()->user()->member)
            <a href="{{ route('birthday.resolution') }}" style="padding: 10px 22px; border-radius: 999px; font-weight: 700; font-size: 13.5px; text-decoration: none; background: var(--white); border: 1.5px solid var(--line); color: var(--ink); transition: all 0.2s ease;">
                My Birthday Resolution
            </a>
        @endif
    </div>

    {{-- Context Banner --}}
    <div class="wishes-controls-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 18px 24px; margin-bottom: 28px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <div style="font-size: 13px; font-weight: 800; color: var(--forest); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">
                Celebration Year {{ $currentYear }}
            </div>
            <div style="font-size: 13.5px; color: var(--muted);">
                You can edit your sent wishes while the recipient's birthday is still today.
            </div>
        </div>
        <div style="font-size: 13.5px; font-weight: 700; color: var(--muted);">
            <span>{{ $wishes->count() }}</span> {{ \Illuminate\Support\Str::plural('greeting', $wishes->count()) }} sent
        </div>
    </div>

    {{-- Wishes Sent Grid --}}
    @if($wishes->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 56px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px; box-shadow: var(--shadow);">
            <div style="font-size: 44px; margin-bottom: 12px;">💌</div>
            <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; color: var(--ink); margin-bottom: 6px;">
                No birthday greetings sent yet.
            </h2>
            <p style="color: var(--muted); font-size: 14px; margin: 0 0 18px;">
                You have not sent any birthday letters to today's celebrating members.
            </p>
            <a href="{{ route('members') }}" class="button button-primary button-sm" style="text-decoration: none;">
                View Celebrating Members
            </a>
        </div>
    @else
        <div class="wishes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px;">
            @foreach($wishes as $wish)
                @php
                    $isSelf = $wish->isSelfWish();
                    $canEdit = $wish->canBeEditedBy(auth()->user());
                @endphp
                <div class="wish-card" id="wish-card-{{ $wish->id }}" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 24px; box-shadow: var(--shadow); display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                            <div>
                                @if($isSelf)
                                    <div style="display: inline-flex; align-items: center; gap: 6px; background: #fdf5d7; border: 1px solid var(--gold); color: #8e680a; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 800;">
                                        <span>🌟</span> My Personal Resolution
                                    </div>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                                        Written for myself (<strong style="color: var(--forest);">{{ $wish->member ? $wish->member->full_name : 'Self' }}</strong>)
                                    </div>
                                @else
                                    <div style="font-size: 12px; color: var(--muted); margin-bottom: 3px;">
                                        To: <strong style="color: var(--forest); font-size: 15px;">{{ $wish->member ? $wish->member->full_name : 'Member' }}</strong>
                                    </div>
                                    @if($wish->is_anonymous)
                                        <div style="display: inline-flex; align-items: center; gap: 5px; background: #eee8d3; color: var(--ink); padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">
                                            <span>🕶️</span> Sent Anonymously
                                        </div>
                                    @else
                                        <span style="font-size: 12px; color: var(--muted);">Sent as <strong>{{ $wish->display_name }}</strong></span>
                                    @endif
                                @endif
                            </div>

                            <span style="background: var(--paper); border: 1px solid var(--line); color: var(--forest); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 800;">
                                {{ $wish->birthday_year }}
                            </span>
                        </div>

                        {{-- Message Content --}}
                        <div class="wish-message-body" style="background: var(--paper); border-left: 3px solid var(--gold); padding: 14px 16px; border-radius: 0 12px 12px 0; margin-bottom: 16px;">
                            <p id="wish-text-{{ $wish->id }}" style="margin: 0; color: var(--ink); font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $wish->message }}</p>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--line); padding-top: 12px; margin-top: 6px;">
                        <span style="font-size: 11px; color: var(--muted);">
                            {{ $wish->created_at->format('M d, Y • H:i') }}
                        </span>

                        @if($canEdit)
                            <button
                                type="button"
                                class="button button-ghost button-sm btn-open-edit-sent-wish"
                                data-id="{{ $wish->id }}"
                                data-message="{{ $wish->message }}"
                                data-anonymous="{{ $wish->is_anonymous ? '1' : '0' }}"
                                data-is-self="{{ $isSelf ? '1' : '0' }}"
                                style="font-size: 12px; padding: 4px 12px; height: 30px;"
                            >
                                {{ $isSelf ? 'Edit Resolution' : 'Edit Wish' }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Edit Wish Modal Dialog --}}
<div class="game-modal-overlay" id="modal-edit-sent-wish" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(32, 48, 41, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="game-modal" style="max-width: 500px; width: 92%; background: var(--white); border: 2px solid var(--gold); border-radius: 24px; padding: 32px 28px; box-shadow: 0 24px 60px rgba(32, 48, 41, 0.35); text-align: left; position: relative;">
        <div style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 18px;">
            <h3 id="edit-modal-title" style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 20px; color: var(--ink);">
                Edit Birthday Greeting
            </h3>
        </div>

        <form id="form-edit-sent-wish">
            @csrf
            <input type="hidden" id="edit-sent-id" value="">

            <div id="wrap-sent-anonymous" style="margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--ink); cursor: pointer;">
                    <input type="checkbox" id="edit-sent-anonymous" name="is_anonymous" value="1" style="width: 16px; height: 16px; accent-color: var(--forest);">
                    Send Anonymously
                </label>
                <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 4px;">
                    Your identity will be displayed as "Anonymous" to the recipient.
                </small>
            </div>

            <div style="margin-bottom: 18px;">
                <label id="edit-message-label" style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                    Birthday Message *
                </label>
                <textarea
                    id="edit-sent-message"
                    name="message"
                    required
                    rows="5"
                    class="input-field"
                    style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit; font-size: 14px; resize: vertical;"
                ></textarea>
                <div id="edit-sent-error" style="display: none; color: var(--red); font-size: 12px; margin-top: 6px; font-weight: 600;"></div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" id="btn-cancel-edit-sent" class="button button-ghost button-sm">
                    Cancel
                </button>
                <button type="submit" id="btn-save-edit-sent" class="button button-primary">
                    Update Message
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('modal-edit-sent-wish');
    const closeBtn = document.getElementById('btn-close-edit-sent');
    const cancelBtn = document.getElementById('btn-cancel-edit-sent');
    const form = document.getElementById('form-edit-sent-wish');
    const errorEl = document.getElementById('edit-sent-error');
    const idInput = document.getElementById('edit-sent-id');
    const messageInput = document.getElementById('edit-sent-message');
    const anonymousInput = document.getElementById('edit-sent-anonymous');
    const wrapAnonymous = document.getElementById('wrap-sent-anonymous');
    const modalTitle = document.getElementById('edit-modal-title');
    const messageLabel = document.getElementById('edit-message-label');

    function hideModal() {
        if (editModal) editModal.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
    }

    if (closeBtn) closeBtn.addEventListener('click', hideModal);
    if (cancelBtn) cancelBtn.addEventListener('click', hideModal);

    document.querySelectorAll('.btn-open-edit-sent-wish').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const message = btn.dataset.message;
            const isAnonymous = btn.dataset.anonymous === '1';
            const isSelf = btn.dataset.isSelf === '1';

            if (idInput) idInput.value = id;
            if (messageInput) messageInput.value = message;
            if (anonymousInput) anonymousInput.checked = isAnonymous;
            if (errorEl) errorEl.style.display = 'none';

            if (isSelf) {
                if (modalTitle) modalTitle.textContent = 'Edit Birthday Resolution';
                if (messageLabel) messageLabel.textContent = 'My Birthday Resolution & Prayer *';
                if (wrapAnonymous) wrapAnonymous.style.display = 'none';
            } else {
                if (modalTitle) modalTitle.textContent = 'Edit Birthday Greeting';
                if (messageLabel) messageLabel.textContent = 'Birthday Message *';
                if (wrapAnonymous) wrapAnonymous.style.display = 'block';
            }

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
                    hideModal();
                    window.location.reload();
                } else {
                    if (errorEl) {
                        errorEl.textContent = data.message || 'Failed to update birthday greeting.';
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

@extends('layouts.app')

@section('title', 'My Birthday Resolution — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Page Header --}}
    <header class="page-header" style="margin-bottom: 24px;">
        <span class="eyebrow">Personal Reflection • {{ $recipientMember->full_name }}</span>
        <h1 style="margin: 0 0 8px;">My Birthday Resolution</h1>
        <p style="margin: 0; color: var(--muted);">
            Write your personal hopes and intentions for the year ahead.
        </p>
    </header>

    {{-- Mode Navigation Tabs (Birthday Wishes for Me / Sent Birthday Wishes / My Birthday Resolution) --}}
    <div class="wishes-nav-tabs" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px;">
        <a href="{{ route('birthday.wishes.received') }}" style="padding: 10px 22px; border-radius: 999px; font-weight: 700; font-size: 13.5px; text-decoration: none; background: var(--white); border: 1.5px solid var(--line); color: var(--ink); transition: all 0.2s ease;">
            Birthday Wishes for Me
        </a>
        <a href="{{ route('birthday.wishes.sent') }}" style="padding: 10px 22px; border-radius: 999px; font-weight: 700; font-size: 13.5px; text-decoration: none; background: var(--white); border: 1.5px solid var(--line); color: var(--ink); transition: all 0.2s ease;">
            Sent Birthday Wishes
        </a>
        <a href="{{ route('birthday.resolution') }}" style="padding: 10px 22px; border-radius: 999px; font-weight: 700; font-size: 13.5px; text-decoration: none; background: var(--forest); color: var(--white); box-shadow: 0 4px 12px rgba(44, 76, 56, 0.25);">
            My Birthday Resolution
        </a>
    </div>

    {{-- Context Banner --}}
    <div class="wishes-controls-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 18px 24px; margin-bottom: 28px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <div style="font-size: 13px; font-weight: 800; color: var(--forest); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">
                Personal Resolution • {{ $currentYear }}
            </div>
            <div style="font-size: 13.5px; color: var(--muted);">
                @if($canWriteOrEditToday)
                    Happy Birthday! You can write or update your personal resolution for {{ $currentYear }} today.
                @else
                    Your personal resolutions are private to your account. You can write or edit a resolution on your birthday.
                @endif
            </div>
        </div>
        <div style="font-size: 13.5px; font-weight: 700; color: var(--muted);">
            <span>{{ $resolutions->count() }}</span> {{ \Illuminate\Support\Str::plural('resolution', $resolutions->count()) }} saved
        </div>
    </div>

    {{-- Compose New Resolution Card (Available on birthday when no resolution exists for current year) --}}
    @if($canWriteOrEditToday && !$currentResolution)
        <div class="resolution-composer-card" style="background: var(--white); border: 2px solid var(--gold); border-radius: 20px; padding: 28px; margin-bottom: 28px; box-shadow: var(--shadow);">
            <h2 style="font-family: 'Manrope', sans-serif; font-size: 20px; color: var(--ink); margin: 0 0 6px;">
                My Birthday Resolution ({{ $currentYear }})
            </h2>
            <p style="color: var(--muted); font-size: 14px; margin: 0 0 18px;">
                Write your hopes, prayers, and intentions for the year ahead.
            </p>

            <form id="form-create-resolution" novalidate>
                @csrf
                <input type="hidden" name="member_id" value="{{ $recipientMember->id }}">
                <input type="hidden" name="is_self_wish" value="1">

                <div style="margin-bottom: 16px;">
                    <label for="new-resolution-message" style="display: block; font-size: 12.5px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        My Resolution *
                    </label>
                    <textarea
                        id="new-resolution-message"
                        name="message"
                        required
                        rows="5"
                        placeholder="Write your personal hopes, prayers, and intentions for the coming year..."
                        class="input-field"
                        style="width: 100%; padding: 12px 14px; border: 1px solid var(--line); border-radius: 12px; font-family: inherit; font-size: 14px; resize: vertical;"
                    ></textarea>
                    <div id="new-resolution-error" style="display: none; color: var(--red); font-size: 12px; margin-top: 6px; font-weight: 600;"></div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" id="btn-save-new-resolution" class="button button-primary">
                        Save Resolution
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Saved Resolutions List --}}
    @if($resolutions->isEmpty())
        @if(!$canWriteOrEditToday)
            <div class="placeholder-card" style="text-align: center; padding: 56px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px; box-shadow: var(--shadow);">
                <h2 style="font-family: 'Manrope', sans-serif; font-size: 22px; color: var(--ink); margin-bottom: 6px;">
                    No birthday resolution recorded yet.
                </h2>
                <p style="color: var(--muted); font-size: 14px; margin: 0;">
                    When your birthday arrives, you can write your personal resolution, prayers, and intentions for the year ahead here.
                </p>
            </div>
        @endif
    @else
        <div class="wishes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px;">
            @foreach($resolutions as $resolution)
                @php
                    $isArchived = $resolution->birthday_year < $currentYear;
                    $canEdit = $resolution->canBeEditedBy(auth()->user());
                @endphp
                <div class="wish-card" id="resolution-card-{{ $resolution->id }}" style="background: var(--white); border: {{ $isArchived ? '1px dashed var(--line)' : '1.5px solid var(--gold)' }}; border-radius: 20px; padding: 24px; box-shadow: var(--shadow); display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                            <div>
                                <div style="display: inline-flex; align-items: center; gap: 6px; background: #fdf5d7; border: 1px solid var(--gold); color: #8e680a; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 800;">
                                    My Birthday Resolution
                                </div>
                                <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                                    Personal reflection for <strong style="color: var(--forest);">{{ $resolution->birthday_year }}</strong>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <span style="background: var(--paper); border: 1px solid var(--line); color: var(--forest); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 800;">
                                    {{ $resolution->birthday_year }}
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

                        <div class="wish-message-body" style="background: var(--paper); border-left: 3px solid var(--gold); padding: 14px 16px; border-radius: 0 12px 12px 0; margin-bottom: 16px;">
                            <p id="resolution-text-{{ $resolution->id }}" style="margin: 0; color: var(--ink); font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $resolution->message }}</p>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--line); padding-top: 12px; margin-top: 6px;">
                        <span style="font-size: 11px; color: var(--muted);">
                            {{ $resolution->created_at->format('M d, Y • H:i') }}
                        </span>

                        @if($canEdit)
                            <button
                                type="button"
                                class="button button-ghost button-sm btn-open-edit-resolution"
                                data-id="{{ $resolution->id }}"
                                data-message="{{ $resolution->message }}"
                                style="font-size: 12px; padding: 4px 12px; height: 30px;"
                            >
                                Edit Resolution
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Edit Resolution Modal Dialog --}}
<div class="game-modal-overlay" id="modal-edit-resolution" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(32, 48, 41, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="game-modal" style="max-width: 500px; width: 92%; background: var(--white); border: 2px solid var(--gold); border-radius: 24px; padding: 32px 28px; box-shadow: 0 24px 60px rgba(32, 48, 41, 0.35); text-align: left; position: relative;">
        <div style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 18px;">
            <h3 style="margin: 0 0 4px; font-family: 'Manrope', sans-serif; font-size: 20px; color: var(--ink);">
                Edit My Birthday Resolution
            </h3>
            <small style="color: var(--muted);">
                Write your hopes, prayers, and intentions for the year ahead.
            </small>
        </div>

        <form id="form-edit-resolution">
            @csrf
            <input type="hidden" id="edit-resolution-id" value="">

            <div style="margin-bottom: 18px;">
                <label for="edit-resolution-message" style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                    My Resolution *
                </label>
                <textarea
                    id="edit-resolution-message"
                    name="message"
                    required
                    rows="5"
                    class="input-field"
                    style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit; font-size: 14px; resize: vertical;"
                ></textarea>
                <div id="edit-resolution-error" style="display: none; color: var(--red); font-size: 12px; margin-top: 6px; font-weight: 600;"></div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" id="btn-cancel-edit-resolution" class="button button-ghost button-sm">
                    Cancel
                </button>
                <button type="submit" id="btn-save-edit-resolution" class="button button-primary">
                    Save Resolution
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const createForm = document.getElementById('form-create-resolution');
    const createError = document.getElementById('new-resolution-error');
    const createMessage = document.getElementById('new-resolution-message');
    const createBtn = document.getElementById('btn-save-new-resolution');

    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (createError) createError.style.display = 'none';

            const message = (createMessage ? createMessage.value : '').trim();
            if (!message || message.length < 3) {
                if (createError) {
                    createError.textContent = 'Please enter at least 3 characters in your birthday resolution.';
                    createError.style.display = 'block';
                }
                return;
            }

            if (createBtn) {
                createBtn.disabled = true;
                createBtn.textContent = 'Saving...';
            }

            try {
                const formData = new FormData(createForm);
                const response = await fetch("{{ route('birthday.letter') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: formData,
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    window.location.reload();
                } else if (createError) {
                    createError.textContent = data.message || 'Failed to save your birthday resolution.';
                    createError.style.display = 'block';
                }
            } catch (err) {
                if (createError) {
                    createError.textContent = 'An unexpected error occurred. Please try again.';
                    createError.style.display = 'block';
                }
            } finally {
                if (createBtn) {
                    createBtn.disabled = false;
                    createBtn.textContent = 'Save Resolution';
                }
            }
        });
    }

    const editModal = document.getElementById('modal-edit-resolution');
    const cancelBtn = document.getElementById('btn-cancel-edit-resolution');
    const editForm = document.getElementById('form-edit-resolution');
    const errorEl = document.getElementById('edit-resolution-error');
    const idInput = document.getElementById('edit-resolution-id');
    const messageInput = document.getElementById('edit-resolution-message');

    function hideModal() {
        if (editModal) editModal.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
    }

    if (cancelBtn) cancelBtn.addEventListener('click', hideModal);

    document.querySelectorAll('.btn-open-edit-resolution').forEach(btn => {
        btn.addEventListener('click', () => {
            if (idInput) idInput.value = btn.dataset.id || '';
            if (messageInput) messageInput.value = btn.dataset.message || '';
            if (errorEl) errorEl.style.display = 'none';
            if (editModal) editModal.style.display = 'flex';
        });
    });

    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (errorEl) errorEl.style.display = 'none';

            const id = idInput.value;
            const message = messageInput.value.trim();

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
                        is_anonymous: 0,
                    }),
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    hideModal();
                    window.location.reload();
                } else if (errorEl) {
                    errorEl.textContent = data.message || 'Failed to update birthday resolution.';
                    errorEl.style.display = 'block';
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


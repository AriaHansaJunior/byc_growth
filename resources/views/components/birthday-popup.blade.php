@if(isset($birthdayMembers) && $birthdayMembers->isNotEmpty())
<div id="modal-birthday-popup" class="game-modal-overlay" style="display: flex; z-index: 99999; background: rgba(32, 48, 41, 0.75); backdrop-filter: blur(4px);">
    <div class="game-modal birthday-modal-card" style="max-width: 520px; width: 92%; background: var(--white); border: 2px solid var(--gold); border-radius: 24px; padding: 36px 32px; text-align: center; box-shadow: 0 24px 60px rgba(32, 48, 41, 0.35); position: relative; animation: birthdayPopIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        
        {{-- Celebratory Banner Badge --}}
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #fdf5d7; border: 1px solid var(--gold); color: #8e680a; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px;">
            🎂 Special Birthday Greeting! 🎂
        </div>

        {{-- Multi-Member Navigation (if > 1 birthday) --}}
        @if($birthdayMembers->count() > 1)
            <div style="display: flex; justify-content: center; align-items: center; gap: 16px; margin-bottom: 16px;">
                <button type="button" id="btn-birthday-prev" class="button button-ghost button-sm" style="padding: 4px 10px; height: 30px; font-size: 12px;">
                    &larr; Prev
                </button>
                <span id="birthday-counter-badge" style="font-size: 12px; font-weight: 700; color: var(--muted);">
                    1 of {{ $birthdayMembers->count() }} celebrating today
                </span>
                <button type="button" id="btn-birthday-next" class="button button-ghost button-sm" style="padding: 4px 10px; height: 30px; font-size: 12px;">
                    Next &rarr;
                </button>
            </div>
        @endif

        {{-- Main Greeting View --}}
        <div id="birthday-view-greeting">
            {{-- Member Photo Container --}}
            <div style="position: relative; width: 150px; height: 150px; margin: 0 auto 20px; border-radius: 50%; padding: 5px; background: linear-gradient(135deg, var(--gold), var(--lime), var(--forest)); box-shadow: 0 10px 25px rgba(231, 189, 82, 0.35);">
                <div style="width: 100%; height: 100%; border-radius: 50%; overflow: hidden; background: var(--cream); display: flex; align-items: center; justify-content: center;">
                    <img
                        id="birthday-member-img"
                        src="{{ $birthdayMembers[0]->photo_url ?: asset('assets/images/group-photo-dummy.svg') }}"
                        alt="{{ $birthdayMembers[0]->full_name }}"
                        style="width: 100%; height: 100%; object-fit: cover;"
                    >
                </div>
                <div style="position: absolute; bottom: 2px; right: 2px; font-size: 26px;">
                    🎉
                </div>
            </div>

            {{-- Member Name --}}
            <h2 id="birthday-member-name" style="font-family: 'Manrope', sans-serif; font-size: 26px; font-weight: 800; color: var(--ink); margin: 0 0 10px;">
                {{ $birthdayMembers[0]->full_name }}
            </h2>

            {{-- Birthday Blessing Message --}}
            <p style="color: var(--muted); font-size: 15px; line-height: 1.6; margin: 0 0 24px; padding: 0 12px;">
                Happy Birthday! Wishing you abundant grace, vibrant health, and God's richest blessings in this new chapter of your life. May you continue to grow in faith and fellowship with BYC!
            </p>

            {{-- 10-Second Lock Status --}}
            <div id="birthday-lock-state" style="padding: 12px 18px; background: var(--paper); border: 1px dashed var(--line); border-radius: 14px; display: inline-flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: var(--muted);">
                <span class="lock-icon" style="font-size: 16px;">⏳</span>
                <span>Please wait... (<span id="birthday-countdown" style="color: var(--forest); font-size: 16px;">10</span>s)</span>
            </div>

            {{-- Action Controls (Hidden during initial 10-second lock) --}}
            <div id="birthday-action-buttons" style="display: none; justify-content: center; gap: 14px; margin-top: 8px;">
                <button type="button" id="btn-birthday-write-letter" class="button button-primary">
                    <x-icon name="sparkles" /> Write a Letter
                </button>
                <button type="button" id="btn-birthday-close" class="button button-secondary">
                    Close
                </button>
            </div>
        </div>

        {{-- Write a Letter Form View (Revealed when clicking Write a Letter) --}}
        <div id="birthday-view-letter" style="display: none; text-align: left;">
            <div style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 style="font-family: 'Manrope', sans-serif; font-size: 19px; margin: 0 0 4px; color: var(--ink);">
                    Send Birthday Letter
                </h3>
                <small style="color: var(--muted);">
                    To: <strong id="birthday-letter-recipient" style="color: var(--forest);">{{ $birthdayMembers[0]->full_name }}</strong>
                </small>
            </div>

            <form id="form-birthday-letter">
                @csrf
                <input type="hidden" name="member_id" id="letter-member-id" value="{{ $birthdayMembers[0]->id }}">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 4px;">
                        Your Name (Optional)
                    </label>
                    <input type="text" name="sender_name" id="letter-sender-name" placeholder="A BYC Friend" class="input-field" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 4px;">
                        Your Birthday Wishes *
                    </label>
                    <textarea name="message" id="letter-message" required rows="4" placeholder="Write your heartfelt blessing and encouraging words..." class="input-field" style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit; resize: vertical;"></textarea>
                    <div id="letter-error" style="display: none; color: var(--red); font-size: 12px; margin-top: 4px; font-weight: 600;"></div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" id="btn-birthday-back" class="button button-ghost button-sm">
                        &larr; Back
                    </button>
                    <button type="submit" id="btn-submit-letter" class="button button-primary">
                        Send Letter 💌
                    </button>
                </div>
            </form>
        </div>

        {{-- Submission Success View --}}
        <div id="birthday-view-success" style="display: none; text-align: center; padding: 16px 0;">
            <div style="font-size: 48px; margin-bottom: 12px;">💌</div>
            <h3 style="font-family: 'Manrope', sans-serif; font-size: 20px; color: var(--forest); margin: 0 0 8px;">
                Letter Sent with Love!
            </h3>
            <p style="color: var(--muted); font-size: 14px; margin-bottom: 24px;">
                Your birthday wishes have been recorded for <strong id="success-member-name"></strong>. Thank you for sharing joy!
            </p>
            <button type="button" id="btn-success-close" class="button button-primary">
                Done & Close
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-birthday-popup');
    if (!modal) return;

    @php
        $popupData = $birthdayMembers->map(function ($m) {
            return [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'photo_url' => $m->photo_url ?: asset('assets/images/group-photo-dummy.svg'),
            ];
        })->values();
    @endphp
    const members = {!! json_encode($popupData) !!};


    let currentIndex = 0;
    let isLocked = true;
    let remainingSeconds = 10;

    const memberImg = document.getElementById('birthday-member-img');
    const memberName = document.getElementById('birthday-member-name');
    const recipientName = document.getElementById('birthday-letter-recipient');
    const recipientIdInput = document.getElementById('letter-member-id');
    const counterBadge = document.getElementById('birthday-counter-badge');

    function updateMemberDisplay(index) {
        currentIndex = index;
        const current = members[currentIndex];
        if (memberImg) memberImg.src = current.photo_url;
        if (memberName) memberName.textContent = current.full_name;
        if (recipientName) recipientName.textContent = current.full_name;
        if (recipientIdInput) recipientIdInput.value = current.id;
        if (counterBadge) {
            counterBadge.textContent = `${currentIndex + 1} of ${members.length} celebrating today`;
        }
    }

    // Previous / Next controls
    const prevBtn = document.getElementById('btn-birthday-prev');
    const nextBtn = document.getElementById('btn-birthday-next');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            const nextIdx = (currentIndex - 1 + members.length) % members.length;
            updateMemberDisplay(nextIdx);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const nextIdx = (currentIndex + 1) % members.length;
            updateMemberDisplay(nextIdx);
        });
    }

    // 10-Second Countdown Lock
    const countdownEl = document.getElementById('birthday-countdown');
    const lockStateEl = document.getElementById('birthday-lock-state');
    const actionButtonsEl = document.getElementById('birthday-action-buttons');

    const countdownTimer = setInterval(() => {
        remainingSeconds--;
        if (countdownEl) countdownEl.textContent = remainingSeconds;

        if (remainingSeconds <= 0) {
            clearInterval(countdownTimer);
            isLocked = false;
            if (lockStateEl) lockStateEl.style.display = 'none';
            if (actionButtonsEl) actionButtonsEl.style.display = 'flex';
        }
    }, 1000);

    // Close actions
    function closeModal() {
        if (isLocked) return;
        modal.style.display = 'none';
    }

    const closeBtn = document.getElementById('btn-birthday-close');
    const successCloseBtn = document.getElementById('btn-success-close');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (successCloseBtn) successCloseBtn.addEventListener('click', closeModal);

    // Prevent dismiss during lock
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal();
        }
    });

    // Write a Letter View Switching
    const viewGreeting = document.getElementById('birthday-view-greeting');
    const viewLetter = document.getElementById('birthday-view-letter');
    const viewSuccess = document.getElementById('birthday-view-success');
    const btnWriteLetter = document.getElementById('btn-birthday-write-letter');
    const btnBack = document.getElementById('btn-birthday-back');

    if (btnWriteLetter) {
        btnWriteLetter.addEventListener('click', () => {
            viewGreeting.style.display = 'none';
            viewLetter.style.display = 'block';
        });
    }

    if (btnBack) {
        btnBack.addEventListener('click', () => {
            viewLetter.style.display = 'none';
            viewGreeting.style.display = 'block';
        });
    }

    // Letter Form Submission via AJAX
    const form = document.getElementById('form-birthday-letter');
    const letterError = document.getElementById('letter-error');
    const successName = document.getElementById('success-member-name');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            letterError.style.display = 'none';

            const message = document.getElementById('letter-message').value.trim();
            if (!message || message.length < 3) {
                letterError.textContent = 'Please enter at least 3 characters in your birthday message.';
                letterError.style.display = 'block';
                return;
            }

            const formData = new FormData(form);

            try {
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
                    viewLetter.style.display = 'none';
                    if (successName) successName.textContent = members[currentIndex].full_name;
                    viewSuccess.style.display = 'block';
                } else {
                    letterError.textContent = data.message || 'Failed to submit letter. Please check your message.';
                    letterError.style.display = 'block';
                }
            } catch (err) {
                letterError.textContent = 'An error occurred. Please try again.';
                letterError.style.display = 'block';
            }
        });
    }
});
</script>
<style>
@keyframes birthdayPopIn {
    0% { transform: scale(0.85); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
@endif

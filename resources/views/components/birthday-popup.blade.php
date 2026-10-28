@if(isset($birthdayMembers) && $birthdayMembers->isNotEmpty())
@php
    $celebrantsCount = $birthdayMembers->count();
    $lockDurationSeconds = $celebrantsCount * 5;
    $authPopupUser = auth('web')->user() ?? auth('admin')->user() ?? auth()->user();
    $popupUserMemberId = ($authPopupUser && !$authPopupUser->isAdmin() && $authPopupUser->member_id)
        ? (int) $authPopupUser->member_id
        : null;
    $popupData = $birthdayMembers->map(function ($m) {
        return [
            'id' => $m->id,
            'full_name' => $m->full_name,
            'photo_url' => $m->photo_url ?: asset('assets/images/group-photo-dummy.svg'),
            'full_photo_url' => $m->full_photo_url ?: ($m->photo_url ?: asset('assets/images/group-photo-dummy.svg')),
        ];
    })->values();
@endphp

<div id="modal-birthday-popup"
     class="game-modal-overlay birthday-popup-overlay"
     role="dialog"
     aria-modal="true"
     aria-labelledby="birthday-member-name"
     data-celebrants-count="{{ $celebrantsCount }}"
     data-lock-duration="{{ $lockDurationSeconds }}"
     style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(32, 48, 41, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    
    <div class="game-modal birthday-modal-card" style="max-width: 520px; width: 92%; background: var(--white); border: 2px solid var(--gold); border-radius: 24px; padding: 36px 32px; text-align: center; box-shadow: 0 24px 60px rgba(32, 48, 41, 0.35); position: relative; animation: birthdayPopIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);">

        {{-- Celebratory Banner Badge --}}
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #fdf5d7; border: 1px solid var(--gold); color: #8e680a; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px;">
            🎂 Special Birthday Greeting! 🎂
        </div>

        {{-- Multi-Member Navigation Controls (rendered when > 1 celebrant) --}}
        @if($celebrantsCount > 1)
            <div id="birthday-nav-controls" style="display: flex; justify-content: center; align-items: center; gap: 16px; margin-bottom: 16px;">
                <button type="button" id="btn-birthday-prev" class="button button-ghost button-sm" aria-label="Previous Birthday Member" disabled style="padding: 4px 10px; height: 30px; font-size: 12px; opacity: 0.5; cursor: not-allowed;">
                    &larr; Prev
                </button>
                <span id="birthday-counter-badge" style="font-size: 12px; font-weight: 700; color: var(--muted);">
                    1 of {{ $celebrantsCount }} celebrating today
                </span>
                <button type="button" id="btn-birthday-next" class="button button-ghost button-sm" aria-label="Next Birthday Member" disabled style="padding: 4px 10px; height: 30px; font-size: 12px; opacity: 0.5; cursor: not-allowed;">
                    Next &rarr;
                </button>
            </div>
        @endif

        {{-- Main Greeting View --}}
        <div id="birthday-view-greeting">
            {{-- Slide Transition Wrapper --}}
            <div id="birthday-slide-container" style="transition: opacity 0.25s ease, transform 0.25s ease;">
                {{-- Member Photo Container --}}
                <div style="position: relative; width: 150px; height: 150px; margin: 0 auto 20px; border-radius: 50%; padding: 5px; background: linear-gradient(135deg, var(--gold), var(--lime), var(--forest)); box-shadow: 0 10px 25px rgba(231, 189, 82, 0.35);">
                    <div style="width: 100%; height: 100%; border-radius: 50%; overflow: hidden; background: var(--cream); display: flex; align-items: center; justify-content: center;">
                        <a id="birthday-member-img-link" href="{{ $popupData[0]['full_photo_url'] ?? $popupData[0]['photo_url'] }}" target="_blank" rel="noopener noreferrer" title="Click to view full size photo in new tab" style="display: block; width: 100%; height: 100%; cursor: pointer;">
                            <img
                                id="birthday-member-img"
                                src="{{ $popupData[0]['photo_url'] }}"
                                alt="{{ $popupData[0]['full_name'] }}"
                                style="width: 100%; height: 100%; object-fit: cover;"
                            >
                        </a>
                    </div>
                    <div style="position: absolute; bottom: 2px; right: 2px; font-size: 26px;">
                        🎉
                    </div>
                </div>

                {{-- Member Name --}}
                <h2 id="birthday-member-name" style="font-family: 'Manrope', sans-serif; font-size: 26px; font-weight: 800; color: var(--ink); margin: 0 0 10px;">
                    {{ $popupData[0]['full_name'] }}
                </h2>

                {{-- Birthday Blessing Message --}}
                <p style="color: var(--muted); font-size: 15px; line-height: 1.6; margin: 0 0 24px; padding: 0 12px;">
                    Happy Birthday! Wishing you abundant grace, vibrant health, and God's richest blessings in this new chapter of your life. May you continue to grow in faith and fellowship with BYC!
                </p>
            </div>

            {{-- Close Lock Status (Countdown Duration = N × 5 seconds) --}}
            <div id="birthday-lock-state" style="padding: 12px 18px; background: var(--paper); border: 1px dashed var(--line); border-radius: 14px; display: inline-flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: var(--muted);">
                <span class="lock-icon" style="font-size: 16px;">⏳</span>
                <span>Please wait... (<span id="birthday-countdown" style="color: var(--forest); font-size: 16px;">{{ $lockDurationSeconds }}</span>s)</span>
            </div>

            {{-- Action Controls (Hidden during initial lock sequence) --}}
            <div id="birthday-action-buttons" style="display: none; justify-content: center; align-items: center; gap: 14px; margin-top: 8px;">
                <button type="button" id="btn-birthday-write-letter" class="button button-primary">
                    <span id="btn-birthday-write-text">Write a Letter</span>
                </button>
                @if(auth()->check() && auth()->user()->member)
                    <a href="{{ route('birthday.wishes') }}" class="button button-ghost" style="border-color: var(--gold); color: #8e680a; font-weight: 700;">
                        My Birthday Wishes
                    </a>
                @endif
                <button type="button" id="btn-birthday-close" class="button button-secondary">
                    Close
                </button>
            </div>
        </div>

        {{-- Write / Edit a Letter or Resolution Form View --}}
        <div id="birthday-view-letter" style="display: none; text-align: left;">
            <div style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 id="birthday-letter-title" style="font-family: 'Manrope', sans-serif; font-size: 19px; margin: 0 0 4px; color: var(--ink);">
                    Send Birthday Letter
                </h3>
                <small id="birthday-letter-subtitle" style="color: var(--muted); display: block;">
                    To: <strong id="birthday-letter-recipient" style="color: var(--forest);">{{ $popupData[0]['full_name'] }}</strong>
                </small>
            </div>

            <form id="form-birthday-letter">
                @csrf
                <input type="hidden" name="member_id" id="letter-member-id" value="{{ $popupData[0]['id'] }}">
                <input type="hidden" name="letter_id" id="letter-id" value="">

                <div id="wrap-letter-anonymous" style="margin-bottom: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--ink); cursor: pointer;">
                        <input type="checkbox" name="is_anonymous" id="letter-is-anonymous" value="1" style="width: 16px; height: 16px; accent-color: var(--forest);">
                        Send Anonymously
                    </label>
                    <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 2px;">
                        The recipient will see "Anonymous".
                    </small>
                </div>

                <div style="margin-bottom: 16px;">
                    <label id="letter-message-label" for="letter-message" style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 4px;">
                        Your Birthday Wishes *
                    </label>
                    <textarea name="message" id="letter-message" required rows="4" placeholder="Write your heartfelt blessing and encouraging words..." class="input-field" style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit; resize: vertical;"></textarea>
                    <div id="letter-error" style="display: none; color: var(--red); font-size: 12px; margin-top: 4px; font-weight: 600;"></div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" id="btn-birthday-back" class="button button-ghost button-sm">
                        Back
                    </button>
                    <button type="submit" id="btn-submit-letter" class="button button-primary">
                        Send Letter
                    </button>
                </div>
            </form>
        </div>

        {{-- Submission Success View --}}
        <div id="birthday-view-success" style="display: none; text-align: center; padding: 16px 0;">
            <div style="font-size: 48px; margin-bottom: 12px;">💌</div>
            <h3 id="success-title" style="font-family: 'Manrope', sans-serif; font-size: 20px; color: var(--forest); margin: 0 0 8px;">
                Letter Sent with Love!
            </h3>
            <p id="success-desc" style="color: var(--muted); font-size: 14px; margin-bottom: 24px;">
                Your birthday wishes have been recorded for <strong id="success-member-name"></strong>. Thank you for sharing joy!
            </p>
            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
                <button type="button" id="btn-success-close" class="button button-primary">
                    Done & Close
                </button>
                <a href="{{ route('birthday.wishes.sent') }}" id="btn-success-review-link" class="button button-secondary">
                    Sent Birthday Wishes
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-birthday-popup');
    if (!modal) return;

    const members = @json($popupData);
    const celebrantsCount = members.length;
    const lockDurationSeconds = celebrantsCount * 5;
    const isAuthenticated = {{ $authPopupUser ? 'true' : 'false' }};
    const currentUserMemberId = @json($popupUserMemberId);

    // Preloaded letters by member id for the current user
    const lettersByMember = @json(isset($userBirthdayLetters) ? $userBirthdayLetters : []);

    // Cooldown & Eligibility Storage
    const STORAGE_KEY = 'byc_birthday_popup_last_shown';
    const COOLDOWN_MS = 3 * 60 * 60 * 1000; // 3 hours

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function setCooldownState(ts) {
        try {
            localStorage.setItem(STORAGE_KEY, ts.toString());
        } catch(e) {}
        document.cookie = STORAGE_KEY + '=' + ts + '; path=/; max-age=' + (3 * 3600);
    }

    let currentIndex = 0;
    let isLocked = true;
    let remainingSeconds = lockDurationSeconds;
    let autoplayTimer = null;
    let countdownInterval = null;
    let openedFromGreeting = false;

    const slideContainer = document.getElementById('birthday-slide-container');
    const memberImg = document.getElementById('birthday-member-img');
    const memberName = document.getElementById('birthday-member-name');
    const recipientName = document.getElementById('birthday-letter-recipient');
    const recipientIdInput = document.getElementById('letter-member-id');
    const letterIdInput = document.getElementById('letter-id');
    const letterTitle = document.getElementById('birthday-letter-title');
    const letterSubtitle = document.getElementById('birthday-letter-subtitle');
    const letterMessage = document.getElementById('letter-message');
    const letterMessageLabel = document.getElementById('letter-message-label');
    const letterIsAnonymous = document.getElementById('letter-is-anonymous');
    const wrapLetterAnonymous = document.getElementById('wrap-letter-anonymous');
    const btnSubmitLetter = document.getElementById('btn-submit-letter');
    const counterBadge = document.getElementById('birthday-counter-badge');

    const prevBtn = document.getElementById('btn-birthday-prev');
    const nextBtn = document.getElementById('btn-birthday-next');
    const countdownEl = document.getElementById('birthday-countdown');
    const lockStateEl = document.getElementById('birthday-lock-state');
    const actionButtonsEl = document.getElementById('birthday-action-buttons');
    const closeBtn = document.getElementById('btn-birthday-close');
    const successCloseBtn = document.getElementById('btn-success-close');
    const successReviewLink = document.getElementById('btn-success-review-link');

    const viewGreeting = document.getElementById('birthday-view-greeting');
    const viewLetter = document.getElementById('birthday-view-letter');
    const viewSuccess = document.getElementById('birthday-view-success');
    const btnWriteLetter = document.getElementById('btn-birthday-write-letter');
    const btnWriteText = document.getElementById('btn-birthday-write-text');
    const btnBack = document.getElementById('btn-birthday-back');

    const form = document.getElementById('form-birthday-letter');
    const letterError = document.getElementById('letter-error');
    const successName = document.getElementById('success-member-name');
    const successTitle = document.getElementById('success-title');
    const successDesc = document.getElementById('success-desc');

    function isSelfMember(memberId) {
        return currentUserMemberId !== null && parseInt(memberId, 10) === parseInt(currentUserMemberId, 10);
    }

    function updateGreetingWriteButton(memberId) {
        if (!btnWriteText) return;
        const existing = lettersByMember[memberId];
        if (isSelfMember(memberId)) {
            btnWriteText.textContent = existing ? 'Edit My Resolution' : 'My Birthday Resolution';
        } else {
            btnWriteText.textContent = existing ? 'Edit Birthday Wish' : 'Write a Letter';
        }
    }

    function updateMemberDisplay(index, animated = false) {
        currentIndex = index;
        const current = members[currentIndex];

        if (animated && slideContainer) {
            slideContainer.style.opacity = '0';
            slideContainer.style.transform = 'scale(0.96)';
            setTimeout(() => {
                applyMemberData(current);
                slideContainer.style.opacity = '1';
                slideContainer.style.transform = 'scale(1)';
            }, 200);
        } else {
            applyMemberData(current);
        }
    }

    function applyMemberData(current) {
        if (memberImg) memberImg.src = current.photo_url;
        const memberImgLink = document.getElementById('birthday-member-img-link');
        if (memberImgLink) memberImgLink.href = current.full_photo_url || current.photo_url;
        if (memberName) memberName.textContent = current.full_name;
        if (recipientName) recipientName.textContent = current.full_name;
        if (recipientIdInput) recipientIdInput.value = current.id;
        if (counterBadge) {
            counterBadge.textContent = `${currentIndex + 1} of ${celebrantsCount} celebrating today`;
        }
        updateGreetingWriteButton(current.id);
    }

    function unlockControls() {
        isLocked = false;
        if (autoplayTimer) {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }

        if (lockStateEl) lockStateEl.style.display = 'none';
        if (actionButtonsEl) actionButtonsEl.style.display = 'flex';

        if (prevBtn) {
            prevBtn.disabled = false;
            prevBtn.style.opacity = '1';
            prevBtn.style.cursor = 'pointer';
        }
        if (nextBtn) {
            nextBtn.disabled = false;
            nextBtn.style.opacity = '1';
            nextBtn.style.cursor = 'pointer';
        }
    }

    function closeModal() {
        if (isLocked) return;
        modal.style.display = 'none';
        if (letterError) letterError.style.display = 'none';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (successCloseBtn) successCloseBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none' && !isLocked) {
            closeModal();
        }
    });

    // Function to populate and open letter or resolution form (Mode A vs Mode B)
    function openLetterForm(memberId, memberNameText, explicitLetterData, fromGreeting = false) {
        const idInt = parseInt(memberId, 10);
        const idx = members.findIndex(m => m.id === idInt);
        const selfMode = isSelfMember(idInt);

        openedFromGreeting = fromGreeting;

        if (!isAuthenticated) {
            const redirectPath = window.location.pathname + '?birthday_letter=1&member_id=' + memberId;
            window.location.href = "{{ route('login') }}?redirect=" + encodeURIComponent(redirectPath);
            return;
        }

        modal.style.display = 'flex';
        unlockControls();

        let targetName = memberNameText || 'Birthday Celebrant';
        if (idx !== -1) {
            updateMemberDisplay(idx, false);
            targetName = members[idx].full_name;
        } else {
            if (recipientIdInput) recipientIdInput.value = idInt;
        }

        // Determine letter data (explicit or preloaded)
        const letter = explicitLetterData || lettersByMember[idInt];

        if (letterError) letterError.style.display = 'none';
        if (letterIdInput) letterIdInput.value = letter && letter.id ? letter.id : '';
        if (letterMessage) letterMessage.value = letter && letter.message ? letter.message : '';

        if (selfMode) {
            // Mode B — Writing a resolution for oneself
            if (letterTitle) letterTitle.textContent = letter ? 'Edit My Birthday Resolution' : 'My Birthday Resolution';
            if (letterSubtitle) letterSubtitle.textContent = 'Write your hopes, prayers, and intentions for the year ahead.';
            if (wrapLetterAnonymous) wrapLetterAnonymous.style.display = 'none';
            if (letterIsAnonymous) letterIsAnonymous.checked = false;
            if (letterMessageLabel) letterMessageLabel.textContent = 'My Resolution *';
            if (letterMessage) letterMessage.placeholder = 'Write your personal hopes, prayers, and intentions for the coming year...';
            if (btnSubmitLetter) btnSubmitLetter.textContent = letter ? 'Update Resolution' : 'Save Resolution';
        } else {
            // Mode A — Sending wishes to another member
            if (letterTitle) letterTitle.textContent = letter ? 'Edit Birthday Letter' : 'Send Birthday Letter';
            if (letterSubtitle) {
                letterSubtitle.innerHTML = 'To: <strong id="birthday-letter-recipient" style="color: var(--forest);"></strong>';
                const recStrong = document.getElementById('birthday-letter-recipient');
                if (recStrong) recStrong.textContent = targetName;
            }
            if (wrapLetterAnonymous) wrapLetterAnonymous.style.display = 'block';
            if (letterIsAnonymous) letterIsAnonymous.checked = Boolean(letter && letter.is_anonymous);
            if (letterMessageLabel) letterMessageLabel.textContent = 'Your Birthday Wishes *';
            if (letterMessage) letterMessage.placeholder = 'Write your heartfelt blessing and encouraging words...';
            if (btnSubmitLetter) btnSubmitLetter.textContent = letter ? 'Update Letter' : 'Send Letter';
        }

        viewGreeting.style.display = 'none';
        viewSuccess.style.display = 'none';
        viewLetter.style.display = 'block';

        if (letterMessage) {
            letterMessage.focus();
        }
    }

    // ALWAYS register window.openBirthdayLetter globally
    window.openBirthdayLetter = function(memberId, memberNameText, explicitLetterData) {
        openLetterForm(memberId, memberNameText, explicitLetterData, false);
    };

    // Write a Letter button on Greeting View
    if (btnWriteLetter) {
        btnWriteLetter.addEventListener('click', () => {
            if (isLocked) return;
            const current = members[currentIndex];
            if (current) {
                openLetterForm(current.id, current.full_name, null, true);
            }
        });
    }

    // Back button in Letter Form
    if (btnBack) {
        btnBack.addEventListener('click', () => {
            if (openedFromGreeting) {
                viewLetter.style.display = 'none';
                viewGreeting.style.display = 'block';
            } else {
                closeModal();
            }
        });
    }

    // Form submission via AJAX
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (letterError) letterError.style.display = 'none';

            const currentMemberId = parseInt(recipientIdInput ? recipientIdInput.value : 0, 10);
            const selfMode = isSelfMember(currentMemberId);
            const message = (letterMessage ? letterMessage.value : '').trim();
            if (!message || message.length < 3) {
                if (letterError) {
                    letterError.textContent = selfMode
                        ? 'Please enter at least 3 characters in your birthday resolution.'
                        : 'Please enter at least 3 characters in your birthday message.';
                    letterError.style.display = 'block';
                }
                return;
            }

            const letterId = letterIdInput ? letterIdInput.value : '';
            const isEditing = Boolean(letterId);

            const formData = new FormData(form);
            const submitUrl = isEditing
                ? `/birthday/letter/${letterId}`
                : "{{ route('birthday.letter') }}";

            if (btnSubmitLetter) {
                btnSubmitLetter.disabled = true;
                btnSubmitLetter.textContent = 'Saving...';
            }

            try {
                const response = await fetch(submitUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: formData,
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    const savedLetter = data.letter || {
                        id: data.letter_id || letterId,
                        message: message,
                        is_anonymous: selfMode ? false : (letterIsAnonymous ? letterIsAnonymous.checked : false),
                        is_self_wish: selfMode,
                        member_id: currentMemberId
                    };

                    // Update local letters map
                    lettersByMember[currentMemberId] = savedLetter;

                    // Update card button on Members page if present
                    const memberCardBtn = document.querySelector(`.btn-member-send-wishes[data-id="${currentMemberId}"]`);
                    if (memberCardBtn) {
                        memberCardBtn.textContent = selfMode ? 'Edit My Resolution' : 'Edit Wish';
                        memberCardBtn.dataset.hasLetter = '1';
                        memberCardBtn.dataset.letterId = savedLetter.id;
                        memberCardBtn.dataset.letterMessage = savedLetter.message;
                        memberCardBtn.dataset.letterAnonymous = savedLetter.is_anonymous ? '1' : '0';
                    }

                    updateGreetingWriteButton(currentMemberId);

                    // Show success screen
                    viewLetter.style.display = 'none';
                    const targetMember = members.find(m => m.id === currentMemberId);
                    const targetMemberName = targetMember ? targetMember.full_name : 'the celebrant';

                    if (selfMode) {
                        if (successTitle) {
                            successTitle.textContent = isEditing ? 'Resolution Updated!' : 'Resolution Saved!';
                        }
                        if (successDesc) {
                            successDesc.textContent = isEditing
                                ? 'Your personal birthday resolution for the year ahead has been updated.'
                                : 'Your personal birthday resolution for the year ahead has been saved.';
                        }
                        if (successReviewLink) {
                            successReviewLink.href = "{{ route('birthday.resolution') }}";
                            successReviewLink.textContent = 'My Birthday Resolution';
                        }
                    } else {
                        if (successTitle) {
                            successTitle.textContent = isEditing ? 'Letter Updated!' : 'Letter Sent with Love!';
                        }
                        if (successDesc) {
                            successDesc.innerHTML = isEditing
                                ? `Your birthday wishes for <strong id="success-member-name"></strong> have been updated.`
                                : `Your birthday wishes have been recorded for <strong id="success-member-name"></strong>. Thank you for sharing joy!`;
                            const nameSpan = document.getElementById('success-member-name');
                            if (nameSpan) nameSpan.textContent = targetMemberName;
                        }
                        if (successReviewLink) {
                            successReviewLink.href = "{{ route('birthday.wishes.sent') }}";
                            successReviewLink.textContent = 'Sent Birthday Wishes';
                        }
                    }

                    viewSuccess.style.display = 'block';
                } else {
                    if (letterError) {
                        letterError.textContent = data.message || 'Failed to save your message. Please check your input.';
                        letterError.style.display = 'block';
                    }
                }
            } catch (err) {
                if (letterError) {
                    letterError.textContent = 'An error occurred. Please try again.';
                    letterError.style.display = 'block';
                }
            } finally {
                if (btnSubmitLetter) {
                    btnSubmitLetter.disabled = false;
                    if (selfMode) {
                        btnSubmitLetter.textContent = isEditing ? 'Update Resolution' : 'Save Resolution';
                    } else {
                        btnSubmitLetter.textContent = isEditing ? 'Update Letter' : 'Send Letter';
                    }
                }
            }
        });
    }

    // Manual navigation controls
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (isLocked) return;
            const nextIdx = (currentIndex - 1 + celebrantsCount) % celebrantsCount;
            updateMemberDisplay(nextIdx, true);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (isLocked) return;
            const nextIdx = (currentIndex + 1) % celebrantsCount;
            updateMemberDisplay(nextIdx, true);
        });
    }

    // Inspect URL query parameters for direct link
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('birthday_letter')) {
        const reqMemberId = urlParams.get('member_id');
        openLetterForm(reqMemberId, '', null, false);
        return;
    }

    // Inspect cooldown eligibility for AUTO-SHOWING modal
    const hasExplicitPopupParam = urlParams.has('birthday_popup');
    
    let isReload = false;
    try {
        const navEntries = performance.getEntriesByType('navigation');
        if (navEntries && navEntries.length > 0) {
            isReload = navEntries[0].type === 'reload';
        }
    } catch(e) {}

    const localVal = localStorage.getItem(STORAGE_KEY);
    const cookieVal = getCookie(STORAGE_KEY);
    const lastShown = localVal ? parseInt(localVal, 10) : (cookieVal ? parseInt(cookieVal, 10) : 0);
    const now = Date.now();
    const timeSinceLast = now - lastShown;
    const isCooldownExpired = !lastShown || timeSinceLast >= COOLDOWN_MS;

    const shouldShow = hasExplicitPopupParam || isReload || isCooldownExpired;

    if (!shouldShow) {
        modal.style.display = 'none';
        return;
    }

    // Auto-show modal and countdown sequence
    modal.style.display = 'flex';
    setCooldownState(now);
    updateGreetingWriteButton(members[0].id);

    // Countdown interval (1s tick)
    countdownInterval = setInterval(() => {
        remainingSeconds--;
        if (countdownEl) countdownEl.textContent = Math.max(0, remainingSeconds);

        if (remainingSeconds <= 0) {
            unlockControls();
        }
    }, 1000);

    // Auto-slide sequence when > 1 celebrant
    if (celebrantsCount > 1) {
        let slideSteps = 0;
        autoplayTimer = setInterval(() => {
            slideSteps++;
            if (slideSteps < celebrantsCount) {
                updateMemberDisplay(slideSteps, true);
            } else if (slideSteps === celebrantsCount) {
                updateMemberDisplay(0, true);
                unlockControls();
            }
        }, 5000);
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

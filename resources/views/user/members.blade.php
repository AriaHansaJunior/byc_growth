@extends('layouts.app')

@section('title', 'Members Directory — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Back Navigation & Admin Action --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>

        @if(auth()->check() && auth()->user()->isAdmin())
            <button type="button" class="button button-primary button-sm" id="btn-open-add-member" style="margin-left: auto;">
                <x-icon name="users" /> Add New Member
            </button>
        @elseif(auth()->check() && auth()->user()->member && auth()->user()->member->isBirthdayToday())
            <a href="{{ route('birthday.wishes') }}" class="button button-primary button-sm" style="margin-left: auto;">
                🎁 View My Birthday Wishes
            </a>
        @endif
    </nav>

    {{-- Page Header --}}
    <header class="page-header">
        <span class="eyebrow">Community & Fellowship</span>
        <h1>Members Directory</h1>
        <p>
            Connected in fellowship, growing together in faith, love, and unity across BYC Growth.
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

    {{-- Public Members Roster (Equal Cards: Photo + Name ONLY) --}}
    @if($members->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 48px 24px;">
            <div style="font-size: 36px; margin-bottom: 12px;">👥</div>
            <h2>No members found.</h2>
            <p style="max-width: 480px; margin: 0 auto; color: var(--muted);">
                Our community members will be showcased here.
            </p>
        </div>
    @else
        <div class="members-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 24px;">
            @foreach($members as $member)
                @php
                    $isBirthday = $member->isBirthdayToday();
                @endphp
                <div class="member-card {{ $isBirthday ? 'member-card-birthday' : '' }}" data-birthday="{{ $isBirthday ? '1' : '0' }}" style="background: var(--white); border: {{ $isBirthday ? '2px solid var(--gold)' : '1px solid var(--line)' }}; border-radius: 20px; padding: 20px; text-align: center; box-shadow: {{ $isBirthday ? '0 12px 30px rgba(231, 189, 82, 0.2)' : 'var(--shadow)' }}; position: relative; display: flex; flex-direction: column; align-items: center; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    {{-- Photo Frame (With celebratory decoration for today's birthday celebrants) --}}
                    <div class="member-photo-frame" style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; background: var(--cream); border: 3px solid {{ $isBirthday ? 'var(--gold)' : 'var(--line)' }}; {{ $isBirthday ? 'box-shadow: 0 0 0 3px var(--gold), 0 8px 24px rgba(231, 189, 82, 0.45);' : '' }} margin-bottom: 16px; display: flex; align-items: center; justify-content: center; position: relative;">
                        @if($member->photo_url)
                            <img
                                src="{{ $member->photo_url }}"
                                alt="{{ $member->full_name }}"
                                class="member-photo-img"
                                style="width: 100%; height: 100%; object-fit: cover;"
                                loading="lazy"
                                onerror="this.style.display='none'; document.getElementById('fallback-{{ $member->id }}').style.display='flex';"
                            >
                            <div id="fallback-{{ $member->id }}" class="member-photo-fallback" style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 40px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                                {{ strtoupper(substr($member->full_name, 0, 1)) }}
                            </div>
                        @else
                            {{-- Default Silhouette / Monogram --}}
                            <div class="member-photo-fallback" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                                {{ strtoupper(substr($member->full_name, 0, 1)) }}
                            </div>
                        @endif

                        @if($isBirthday)
                            <div class="birthday-badge-pin" title="Celebrating Birthday Today!" style="position: absolute; bottom: 2px; right: 2px; width: 32px; height: 32px; background: #fdf5d7; border: 2px solid var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 15px; box-shadow: 0 3px 8px rgba(0,0,0,0.18);">
                                🎂
                            </div>
                        @endif
                    </div>

                    {{-- Member Full Name ONLY (No position, no role, no hierarchy, no birthday) --}}
                    <h2 class="member-name" style="font-family: 'Manrope', sans-serif; font-size: 17px; font-weight: 700; color: var(--ink); margin: 0; line-height: 1.3;">
                        {{ $member->full_name }}
                    </h2>

                    {{-- Birthday Interaction Action (Available on birthday date, even after popup is closed) --}}
                    @if($isBirthday)
                        <button
                            type="button"
                            class="button button-sm btn-member-send-wishes"
                            data-id="{{ $member->id }}"
                            data-name="{{ $member->full_name }}"
                            style="margin-top: 10px; background: #fdf5d7; border: 1px solid var(--gold); color: #8e680a; font-weight: 700; font-size: 12px; border-radius: 20px; padding: 4px 14px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 6px rgba(231, 189, 82, 0.2);"
                        >
                            🎂 Send Wishes
                        </button>
                    @endif

                    {{-- Admin Controls (Only visible to authenticated administrators) --}}
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <div class="admin-member-actions" style="margin-top: 14px; display: flex; gap: 8px; width: 100%; justify-content: center;">
                            <button
                                type="button"
                                class="button button-secondary button-sm btn-edit-member"
                                data-id="{{ $member->id }}"
                                data-name="{{ $member->full_name }}"
                                data-dob="{{ $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '' }}"
                                data-photo="{{ $member->photo_url }}"
                                style="font-size: 12px; padding: 4px 10px; height: 32px;"
                            >
                                Edit
                            </button>
                            <form method="POST" action="{{ route('admin.members.destroy', $member->id) }}" onsubmit="return confirm('Are you sure you want to remove this member?');" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button button-danger button-sm" style="font-size: 12px; padding: 4px 10px; height: 32px;">
                                    Delete
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Admin Modal: Add Member --}}
@if(auth()->check() && auth()->user()->isAdmin())
<div class="game-modal-overlay" id="modal-add-member" style="display: none;">
    <div class="game-modal" style="max-width: 500px; width: 90%;">
        <div class="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif;">Add New Member</h3>
            <button type="button" class="btn-close-modal" id="btn-close-add-member" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Full Name *</label>
                    <input type="text" name="full_name" required class="input-field" placeholder="e.g. Jonathan Alexander" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px; color: var(--ink);">
                        Date of Birth
                        <span style="font-size: 11px; font-weight: 500; color: var(--muted);">(Internal application data for Birthday Popup)</span>
                    </label>
                    <input type="date" name="date_of_birth" class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Member Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="input-field" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                    <small style="color: var(--muted); font-size: 12px;">Only images (JPG, PNG, WEBP) accepted. Max 5MB.</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px;">
                    <button type="submit" class="button button-primary">Save Member</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Admin Modal: Edit Member --}}
<div class="game-modal-overlay" id="modal-edit-member" style="display: none;">
    <div class="game-modal" style="max-width: 500px; width: 90%;">
        <div class="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif;">Edit Member</h3>
            <button type="button" class="btn-close-modal" id="btn-close-edit-member" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
        </div>

        <form method="POST" id="form-edit-member" action="" enctype="multipart/form-data">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Full Name *</label>
                    <input type="text" name="full_name" id="edit-member-name" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px; color: var(--ink);">
                        Date of Birth
                        <span style="font-size: 11px; font-weight: 500; color: var(--muted);">(Internal application data for Birthday Popup)</span>
                    </label>
                    <input type="date" name="date_of_birth" id="edit-member-dob" class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Upload / Replace Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="input-field" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                    <small style="color: var(--muted); font-size: 12px;">Only images (JPG, PNG, WEBP) accepted. Max 5MB.</small>
                </div>

                <div id="edit-member-remove-photo-wrap" style="display: none;">
                    <label style="font-size: 13px; color: var(--red); display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="remove_photo" value="1">
                        Remove current photo
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px;">
                    <button type="submit" class="button button-primary">Update Member</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('modal-add-member');
    const openAddBtn = document.getElementById('btn-open-add-member');
    const closeAddBtn = document.getElementById('btn-close-add-member');

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

    const editModal = document.getElementById('modal-edit-member');
    const closeEditBtn = document.getElementById('btn-close-edit-member');
    const formEdit = document.getElementById('form-edit-member');

    if (closeEditBtn && editModal) {
        closeEditBtn.addEventListener('click', () => {
            editModal.style.display = 'none';
        });
    }

    document.querySelectorAll('.btn-edit-member').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const dob = btn.dataset.dob;
            const photo = btn.dataset.photo;

            formEdit.action = `/admin/members/${id}`;
            document.getElementById('edit-member-name').value = name;
            document.getElementById('edit-member-dob').value = dob;

            const removeWrap = document.getElementById('edit-member-remove-photo-wrap');
            if (photo) {
                removeWrap.style.display = 'block';
            } else {
                removeWrap.style.display = 'none';
            }

            editModal.style.display = 'flex';
        });
    });
});
</script>
@endif

@if($members->contains(fn($m) => $m->isBirthdayToday()))
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-member-send-wishes').forEach(btn => {
        btn.addEventListener('click', () => {
            const memberId = btn.dataset.id;
            const memberName = btn.dataset.name;

            if (typeof window.openBirthdayLetter === 'function') {
                window.openBirthdayLetter(memberId, memberName);
            } else {
                @auth
                    window.location.href = '/members?birthday_letter=1&member_id=' + memberId;
                @else
                    window.location.href = "{{ route('login') }}?redirect=" + encodeURIComponent('/members?birthday_letter=1&member_id=' + memberId);
                @endauth
            }
        });
    });
});
</script>
@endif
@endsection

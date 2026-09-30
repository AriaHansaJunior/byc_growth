@extends('layouts.admin')

@section('title', 'Members Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Community & Fellowship</span>
        <h1>Members Management</h1>
        <p>
            Connected in fellowship, growing together in faith, love, and unity across BYC Growth. Manage community member records directly on the live roster cards.
        </p>
    </div>
    <div class="admin-header-actions">
        <button type="button" class="button button-primary button-sm" id="btn-open-add-member">
            <x-icon name="plus" /> Add Member
        </button>
    </div>
</div>
@endsection

@section('content')
    {{-- Admin Controls Summary Strip --}}
    <div class="admin-controls-strip">
        <div class="admin-controls-badge">
            <x-icon name="users" /> Live Roster God Mode &bull; Total: {{ $totalCount }} Members ({{ $activeCount }} Active)
        </div>
        <a href="{{ route('members') }}" class="button button-ghost button-sm" target="_blank" title="View public members directory">
            View Public Page &rarr;
        </a>
    </div>

    {{-- Members Grid (Identical Visual Foundation to User Page + Admin Controls) --}}
    @if($members->isEmpty())
        <div class="placeholder-card" style="text-align: center; padding: 48px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px;">
            <div style="font-size: 36px; margin-bottom: 12px;">👥</div>
            <h2>No members found.</h2>
            <p style="max-width: 480px; margin: 0 auto; color: var(--muted);">
                Click "Add Member" above to create the first community member profile.
            </p>
        </div>
    @else
        <div class="members-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 24px;">
            @foreach($members as $member)
                @php
                    $isBirthday = $member->isBirthdayToday();
                @endphp
                <div class="member-card {{ $isBirthday ? 'member-card-birthday' : '' }}" data-birthday="{{ $isBirthday ? '1' : '0' }}" style="background: var(--white); border: {{ $isBirthday ? '2px solid var(--gold)' : '1px solid var(--line)' }}; border-radius: 20px; padding: 20px; text-align: center; box-shadow: {{ $isBirthday ? '0 12px 30px rgba(231, 189, 82, 0.2)' : 'var(--shadow)' }}; position: relative; display: flex; flex-direction: column; align-items: center; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    
                    {{-- Photo Frame --}}
                    <div class="member-photo-frame" style="width: 130px; height: 130px; border-radius: 50%; overflow: hidden; background: var(--cream); border: 3px solid {{ $isBirthday ? 'var(--gold)' : 'var(--line)' }}; {{ $isBirthday ? 'box-shadow: 0 0 0 3px var(--gold), 0 8px 24px rgba(231, 189, 82, 0.45);' : '' }} margin-bottom: 14px; display: flex; align-items: center; justify-content: center; position: relative;">
                        @if($member->photo_url)
                            <img
                                src="{{ $member->photo_url }}"
                                alt="{{ $member->full_name }}"
                                class="member-photo-img"
                                style="width: 100%; height: 100%; object-fit: cover;"
                                loading="lazy"
                                onerror="this.style.display='none'; document.getElementById('fallback-{{ $member->id }}').style.display='flex';"
                            >
                            <div id="fallback-{{ $member->id }}" class="member-photo-fallback" style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 38px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                                {{ strtoupper(substr($member->full_name, 0, 1)) }}
                            </div>
                        @else
                            <div class="member-photo-fallback" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 38px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                                {{ strtoupper(substr($member->full_name, 0, 1)) }}
                            </div>
                        @endif

                        @if($isBirthday)
                            <div class="birthday-badge-pin" title="Celebrating Birthday Today!" style="position: absolute; bottom: 2px; right: 2px; width: 30px; height: 30px; background: #fdf5d7; border: 2px solid var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 3px 8px rgba(0,0,0,0.18);">
                                🎂
                            </div>
                        @endif
                    </div>

                    {{-- Member Full Name --}}
                    <h2 class="member-name" style="font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 700; color: var(--ink); margin: 0 0 4px; line-height: 1.3;">
                        {{ $member->full_name }}
                    </h2>

                    {{-- Admin-Only Birthdate & Status Context --}}
                    <small style="color: var(--muted); font-size: 12px; margin-bottom: 12px; display: block;">
                        🎂 {{ $member->date_of_birth ? $member->date_of_birth->format('M j, Y') : 'DOB not set' }}
                    </small>

                    {{-- Administrative Controls (Edit / Delete) --}}
                    <div class="admin-action-group" style="display: flex; gap: 8px; width: 100%; justify-content: center; margin-top: auto;">
                        <button
                            type="button"
                            class="button button-secondary button-sm btn-edit-member"
                            data-id="{{ $member->id }}"
                            data-name="{{ $member->full_name }}"
                            data-dob="{{ $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '' }}"
                            data-photo="{{ $member->photo_url }}"
                            style="font-size: 12px; padding: 4px 10px; height: 32px;"
                        >
                            ✎ Edit
                        </button>
                        <button
                            type="button"
                            class="button button-danger button-sm"
                            data-admin-confirm="Are you sure you want to remove {{ $member->full_name }}?"
                            data-confirm-title="Delete Member"
                            data-confirm-body="Are you sure you want to remove {{ $member->full_name }} from the community roster? Historical records will be handled safely."
                            data-confirm-btn="Yes, Delete Member"
                            data-action="{{ route('admin.members.destroy', $member->id) }}"
                            data-method="DELETE"
                            style="font-size: 12px; padding: 4px 10px; height: 32px;"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Member Modal --}}
    <div id="modal-add-member" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
        <div class="info-modal" style="width: min(540px, 92vw); padding: 32px; background: var(--white); border-radius: 24px; position: relative;">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-add-member" style="position: absolute; top: 20px; right: 20px; width: 34px; height: 34px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 20px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Community Directory</span>
                <h3 style="font: 800 24px 'Manrope', sans-serif; color: var(--ink); margin: 4px 0 0;">Add New Member</h3>
            </div>
            <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="add-full-name">Full Name</label>
                    <input type="text" id="add-full-name" name="full_name" class="form-input" required placeholder="Full Name">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="add-dob">Date of Birth</label>
                    <input type="date" id="add-dob" name="date_of_birth" class="form-input">
                    <small style="color: var(--muted); font-size: 12px;">Used exclusively for birthday celebrations.</small>
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add-photo">Profile Photo</label>
                    <input type="file" id="add-photo" name="photo" class="form-input" accept="image/*">
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm">Save Member</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Member Modal --}}
    <div id="modal-edit-member" class="modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
        <div class="info-modal" style="width: min(540px, 92vw); padding: 32px; background: var(--white); border-radius: 24px; position: relative;">
            <button type="button" class="icon-button btn-close-modal" id="btn-close-edit-member" style="position: absolute; top: 20px; right: 20px; width: 34px; height: 34px;">
                <x-icon name="x" />
            </button>
            <div style="margin-bottom: 20px;">
                <span class="eyebrow" style="color: var(--forest); font-size: 11px; display: block; text-transform: uppercase;">Community Directory</span>
                <h3 style="font: 800 24px 'Manrope', sans-serif; color: var(--ink); margin: 4px 0 0;">Edit Member</h3>
            </div>
            <form id="form-edit-member" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="edit-full-name">Full Name</label>
                    <input type="text" id="edit-full-name" name="full_name" class="form-input" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="edit-dob">Date of Birth</label>
                    <input type="date" id="edit-dob" name="date_of_birth" class="form-input">
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="edit-photo">Replace Photo (Optional)</label>
                    <input type="file" id="edit-photo" name="photo" class="form-input" accept="image/*">
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm">Update Member</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addModal = document.getElementById('modal-add-member');
        const editModal = document.getElementById('modal-edit-member');
        const btnOpenAdd = document.getElementById('btn-open-add-member');
        const editForm = document.getElementById('form-edit-member');
        const editName = document.getElementById('edit-full-name');
        const editDob = document.getElementById('edit-dob');

        if (btnOpenAdd && addModal) {
            btnOpenAdd.addEventListener('click', () => {
                addModal.style.display = 'grid';
            });
        }

        document.querySelectorAll('.btn-edit-member').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const dob = this.getAttribute('data-dob');

                if (editForm) {
                    editForm.action = `/admin/members/${id}`;
                }
                if (editName) editName.value = name || '';
                if (editDob) editDob.value = dob || '';
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

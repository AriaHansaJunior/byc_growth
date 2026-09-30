@extends('layouts.admin')

@section('title', 'Members Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Community Roster</span>
        <h1>Members Management</h1>
        <p>
            Add, update, and manage community member profiles, confidential birthdates, and photo records.
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
    {{-- Members Management Card & Table --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Community Members Directory</h2>
                <small style="color: var(--muted); font-size: 13px;">Total: {{ $totalCount }} members ({{ $activeCount }} active)</small>
            </div>
            <a href="{{ route('members') }}" class="button button-ghost button-sm" target="_blank" title="Preview public member roster">
                View Public Page &rarr;
            </a>
        </div>

        @if($members->isEmpty())
            <div style="text-align: center; padding: 48px 24px; color: var(--muted);">
                <div style="font-size: 36px; margin-bottom: 8px;">👥</div>
                <h3>No members found</h3>
                <p>Click "Add Member" to create your first community profile.</p>
            </div>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Photo</th>
                            <th>Full Name</th>
                            <th>Date of Birth</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $member)
                            <tr>
                                <td>
                                    <div style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; background: var(--cream); border: 1px solid var(--line); display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--forest);">
                                        @if($member->photo_url)
                                            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            {{ strtoupper(substr($member->full_name, 0, 1)) }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: var(--ink); font-size: 15px;">{{ $member->full_name }}</strong>
                                </td>
                                <td>
                                    <span style="color: var(--muted); font-size: 13.5px;">
                                        {{ $member->date_of_birth ? $member->date_of_birth->format('F j, Y') : 'Not Set' }}
                                    </span>
                                </td>
                                <td>
                                    @if($member->is_active)
                                        <span class="role-badge" style="background: #eaf3dc; color: var(--forest-dark);">Active</span>
                                    @else
                                        <span class="role-badge" style="background: #fdf0ee; color: var(--red);">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div class="admin-action-group" style="justify-content: flex-end;">
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
                                            data-action="{{ route('admin.members.destroy', $member->id) }}"
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
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add-photo">Profile Photo</label>
                    <input type="file" id="add-photo" name="photo" class="form-input" accept="image/*">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
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
                    <label class="form-label" for="edit-photo">Update Profile Photo</label>
                    <input type="file" id="edit-photo" name="photo" class="form-input" accept="image/*">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal">Cancel</button>
                    <button type="submit" class="button button-primary button-sm">Update Member</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('modal-add-member');
    const editModal = document.getElementById('modal-edit-member');
    const openAddBtn = document.getElementById('btn-open-add-member');
    const editForm = document.getElementById('form-edit-member');
    const editName = document.getElementById('edit-full-name');
    const editDob = document.getElementById('edit-dob');

    if (openAddBtn && addModal) {
        openAddBtn.addEventListener('click', () => {
            addModal.style.display = 'grid';
            document.body.style.overflow = 'hidden';
        });
    }

    document.querySelectorAll('.btn-edit-member').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const dob = btn.getAttribute('data-dob');

            if (editForm) editForm.action = `/admin/members/${id}`;
            if (editName) editName.value = name || '';
            if (editDob) editDob.value = dob || '';

            if (editModal) {
                editModal.style.display = 'grid';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            if (addModal) addModal.style.display = 'none';
            if (editModal) editModal.style.display = 'none';
            document.body.style.overflow = '';
        });
    });
});
</script>
@endpush

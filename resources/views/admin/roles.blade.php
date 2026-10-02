@extends('layouts.admin')

@section('title', 'Role & Account Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Security & Access Governance</span>
        <h1>Role & Account Management</h1>
        <p>
            Manage application credentials, allocate administrator privileges, link accounts to fellowship member profiles, and secure access permissions.
        </p>
    </div>
    <div class="admin-header-actions">
        <button type="button" class="button button-primary button-sm" id="btn-open-add-user">
            <x-icon name="users" /> Create Account
        </button>
    </div>
</div>
@endsection

@section('content')
    {{-- Summary Stats Strip --}}
    <div class="admin-card" style="padding: 16px 24px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--muted); letter-spacing: .08em; display: block;">Total Accounts</span>
                    <strong style="font-size: 20px; color: var(--ink); font-family: 'Manrope', sans-serif;">{{ $totalUsersCount }}</strong>
                </div>
                <div style="width: 1px; height: 28px; background: var(--line);"></div>
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--muted); letter-spacing: .08em; display: block;">Administrators</span>
                    <strong style="font-size: 20px; color: var(--forest); font-family: 'Manrope', sans-serif;">{{ $totalAdminsCount }}</strong>
                </div>
                <div style="width: 1px; height: 28px; background: var(--line);"></div>
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--muted); letter-spacing: .08em; display: block;">Standard Users</span>
                    <strong style="font-size: 20px; color: #315e89; font-family: 'Manrope', sans-serif;">{{ $totalStandardUsersCount }}</strong>
                </div>
            </div>

            <div>
                <span class="role-badge" style="background: var(--cream); color: var(--forest);">
                    Active Session: <strong>{{ $currentUser->username ?? $currentUser->name }}</strong>
                </span>
            </div>
        </div>
    </div>

    {{-- Search & Filtering Controls --}}
    <div class="admin-card" style="background: var(--cream); border: 1px solid var(--line); border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;">
        <form method="GET" action="{{ route('admin.roles') }}" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Search Accounts</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by name, username, or email..." class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 150px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Role</label>
                <select name="role" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="all" {{ ($filters['role'] ?? 'all') === 'all' ? 'selected' : '' }}>All Roles</option>
                    <option value="admin" {{ ($filters['role'] ?? '') === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="user" {{ ($filters['role'] ?? '') === 'user' ? 'selected' : '' }}>Standard User</option>
                </select>
            </div>

            <div style="min-width: 170px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Sort By</label>
                <select name="sort" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest Registered</option>
                    <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest Registered</option>
                    <option value="username_asc" {{ ($filters['sort'] ?? '') === 'username_asc' ? 'selected' : '' }}>Username (A - Z)</option>
                    <option value="username_desc" {{ ($filters['sort'] ?? '') === 'username_desc' ? 'selected' : '' }}>Username (Z - A)</option>
                    <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Name (A - Z)</option>
                    <option value="name_desc" {{ ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' }}>Name (Z - A)</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="button button-primary button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Filter
                </button>
                <a href="{{ route('admin.roles') }}" class="button button-ghost button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Accounts Ledger Table --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Registered Accounts</h2>
                <small style="color: var(--muted); font-size: 13px;">Overview of all application credentials and privilege levels</small>
            </div>
            <span class="role-badge" style="background: var(--paper); color: var(--ink);">
                Access Control
            </span>
        </div>

        @if($users->isEmpty())
            <div style="text-align: center; padding: 48px 24px; color: var(--muted);">
                <div style="font-size: 36px; margin-bottom: 8px;">👤</div>
                <h3>No accounts found</h3>
                <p>Adjust your search query or reset the filter.</p>
            </div>
        @else
            {{-- Batch Actions Toolbar --}}
            <form id="form-batch-delete-roles" method="POST" action="{{ route('admin.roles.batch-delete') }}" style="display: flex; justify-content: space-between; align-items: center; background: var(--cream); border: 1px solid var(--line); border-radius: 10px; padding: 10px 16px; margin-bottom: 16px;">
                @csrf
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span id="batch-selected-count-roles" style="font-weight: 700; font-size: 13px; color: var(--forest);">
                        0 accounts selected
                    </span>
                </div>
                <div>
                    <button type="button" class="button button-danger button-sm" id="btn-batch-delete-roles" disabled style="opacity: 0.5; height: 32px; font-size: 12px;">
                        Delete Selected
                    </button>
                </div>
            </form>

            <div class="admin-table-wrap">
                <table class="admin-table" id="accounts-table">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="check-select-all-roles" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;" title="Select all selectable accounts on this page">
                            </th>
                            <th>Username & Name</th>
                            <th>Email Address</th>
                            <th>Access Role</th>
                            <th>Linked Member</th>
                            <th>Created At</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr id="user-row-{{ $user->id }}">
                                <td style="text-align: center;">
                                    @if($user->id === $currentUser->id)
                                        <input type="checkbox" disabled title="Cannot select your own active administrator account" style="width: 17px; height: 17px; opacity: 0.35; cursor: not-allowed;">
                                    @else
                                        <input type="checkbox" name="ids[]" value="{{ $user->id }}" form="form-batch-delete-roles" class="role-batch-checkbox" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;">
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <strong style="color: var(--ink); font-size: 14.5px;">
                                            {{ '@' . $user->username }}
                                        </strong>
                                        @if($user->id === $currentUser->id)
                                            <span class="role-badge" style="background: var(--cream); color: var(--forest); font-size: 10.5px; padding: 2px 7px;">
                                                You
                                            </span>
                                        @endif
                                    </div>
                                @if($user->name && $user->name !== $user->username)
                                    <small style="color: var(--muted); display: block; font-size: 12px; margin-top: 2px;">
                                        {{ $user->name }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span style="color: var(--ink); font-size: 13.5px;">
                                    {{ $user->email }}
                                </span>
                            </td>
                            <td>
                                @if($user->role === 'admin')
                                    <span class="role-badge" style="background: #eaf3dc; color: var(--forest); font-weight: 700;">
                                        Admin
                                    </span>
                                @else
                                    <span class="role-badge" style="background: #dfe9f2; color: #315e89; font-weight: 700;">
                                        User
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($user->member)
                                    <span style="font-size: 13px; color: var(--forest); font-weight: 600;">
                                        👤 {{ $user->member->full_name }}
                                    </span>
                                @else
                                    <span style="font-size: 12.5px; color: var(--muted); font-style: italic;">
                                        Unlinked
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span style="color: var(--muted); font-size: 12.5px;">
                                    {{ $user->created_at ? $user->created_at->format('M j, Y') : '-' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="admin-action-group" style="justify-content: flex-end; gap: 6px;">
                                    <button
                                        type="button"
                                        class="button button-ghost button-sm btn-edit-user"
                                        data-id="{{ $user->id }}"
                                        data-username="{{ $user->username }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->role }}"
                                        data-member-id="{{ $user->member_id ?? '' }}"
                                        style="font-size: 12px; padding: 4px 10px; height: 32px;"
                                        title="Edit User"
                                    >
                                        Edit
                                    </button>

                                    @if($user->id !== $currentUser->id)
                                        <form method="POST" action="{{ route('admin.roles.destroy', $user->id) }}" style="display: inline; margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="button button-danger button-sm"
                                                style="font-size: 12px; padding: 4px 10px; height: 32px;"
                                                data-admin-confirm="Are you sure you want to delete account '{{ '@' . $user->username }}' ({{ $user->email }})? This action cannot be undone."
                                                title="Delete Account"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        <button
                                            type="button"
                                            class="button button-ghost button-sm"
                                            disabled
                                            title="Cannot delete your own active administrator account"
                                            style="padding: 4px 10px; height: 32px; font-size: 12px; opacity: 0.45; cursor: not-allowed;"
                                        >
                                            Self
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Modal: Create Account --}}
    <div class="admin-modal-backdrop" id="modal-add-user" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.65); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 18px; max-width: 500px; width: 100%; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <div style="padding: 20px 24px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">👤</span>
                    <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 18px; font-weight: 800; color: var(--ink);">
                        Create New User Account
                    </h3>
                </div>
                <button type="button" class="btn-close-modal" data-target="modal-add-user" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.roles.store') }}" style="padding: 24px;">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="add-user-username">Username *</label>
                        <input
                            type="text"
                            name="username"
                            id="add-user-username"
                            required
                            class="form-input"
                            placeholder="e.g. john_doe"
                            minlength="3"
                            maxlength="60"
                            pattern="^[a-zA-Z0-9_]+$"
                            title="Only letters, numbers, and underscores allowed"
                        >
                        <small style="color: var(--muted); font-size: 11px;">Alphanumeric and underscores only (used for login & identity).</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add-user-name">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            id="add-user-name"
                            class="form-input"
                            placeholder="e.g. John Doe (optional, defaults to username)"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add-user-email">Email Address *</label>
                        <input
                            type="email"
                            name="email"
                            id="add-user-email"
                            required
                            class="form-input"
                            placeholder="e.g. user@bycgrowth.org"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add-user-password">Password *</label>
                        <input
                            type="password"
                            name="password"
                            id="add-user-password"
                            required
                            minlength="6"
                            class="form-input"
                            placeholder="Minimum 6 characters"
                        >
                        <small style="color: var(--muted); font-size: 11px;">Securely encrypted using bcrypt before database storage.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add-user-role">Access Role *</label>
                        <select name="role" id="add-user-role" required class="form-input">
                            <option value="user">User (Standard Fellowship Member)</option>
                            <option value="admin">Admin (Full Administrator)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add-user-member-id">Linked Fellowship Member</label>
                        <select name="member_id" id="add-user-member-id" class="form-input">
                            <option value="">-- No Linked Member Profile --</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}">{{ $m->full_name }}</option>
                            @endforeach
                        </select>
                        <small style="color: var(--muted); font-size: 11px;">Links this authentication credential to their public fellowship directory profile.</small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                        <button type="button" class="button button-ghost button-sm btn-close-modal" data-target="modal-add-user">
                            Cancel
                        </button>
                        <button type="submit" class="button button-primary button-sm" style="min-width: 140px;">
                            Create Account
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Edit Account --}}
    <div class="admin-modal-backdrop" id="modal-edit-user" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.65); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 18px; max-width: 500px; width: 100%; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <div style="padding: 20px 24px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">✏️</span>
                    <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 18px; font-weight: 800; color: var(--ink);">
                        Edit User Account
                    </h3>
                </div>
                <button type="button" class="btn-close-modal" data-target="modal-edit-user" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>

            <form method="POST" id="form-edit-user" action="" style="padding: 24px;">
                @csrf
                @method('PUT')
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="edit-user-username">Username *</label>
                        <input
                            type="text"
                            name="username"
                            id="edit-user-username"
                            required
                            class="form-input"
                            minlength="3"
                            maxlength="60"
                            pattern="^[a-zA-Z0-9_]+$"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit-user-name">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            id="edit-user-name"
                            class="form-input"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit-user-email">Email Address *</label>
                        <input
                            type="email"
                            name="email"
                            id="edit-user-email"
                            required
                            class="form-input"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit-user-password">
                            Change Password
                            <small style="color: var(--muted); font-weight: normal;">(Leave empty to preserve existing password)</small>
                        </label>
                        <input
                            type="password"
                            name="password"
                            id="edit-user-password"
                            minlength="6"
                            class="form-input"
                            placeholder="Leave blank to keep current password"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit-user-role">Access Role *</label>
                        <select name="role" id="edit-user-role" required class="form-input">
                            <option value="user">User (Standard Fellowship Member)</option>
                            <option value="admin">Admin (Full Administrator)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit-user-member-id">Linked Fellowship Member</label>
                        <select name="member_id" id="edit-user-member-id" class="form-input">
                            <option value="">-- No Linked Member Profile --</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}">{{ $m->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                        <button type="button" class="button button-ghost button-sm btn-close-modal" data-target="modal-edit-user">
                            Cancel
                        </button>
                        <button type="submit" class="button button-primary button-sm" style="min-width: 140px;">
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
    const addModal = document.getElementById('modal-add-user');
    const openAddBtn = document.getElementById('btn-open-add-user');

    if (openAddBtn && addModal) {
        openAddBtn.addEventListener('click', function () {
            addModal.style.display = 'flex';
        });
    }

    const editModal = document.getElementById('modal-edit-user');
    const formEdit = document.getElementById('form-edit-user');

    document.querySelectorAll('.btn-edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const data = this.dataset;
            formEdit.action = '/admin/roles/' + data.id;

            document.getElementById('edit-user-username').value = data.username || '';
            document.getElementById('edit-user-name').value = data.name || '';
            document.getElementById('edit-user-email').value = data.email || '';
            document.getElementById('edit-user-password').value = '';
            document.getElementById('edit-user-role').value = data.role || 'user';

            const memberSelect = document.getElementById('edit-user-member-id');
            if (memberSelect) {
                memberSelect.value = data.memberId || '';
            }

            editModal.style.display = 'flex';
        });
    });

    // Close Modals handler
    document.querySelectorAll('.btn-close-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            if (targetId) {
                document.getElementById(targetId).style.display = 'none';
            }
        });
    });

    // Close on backdrop click
    document.querySelectorAll('.admin-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // Batch Selection & Deletion
    const selectAllCheckbox = document.getElementById('check-select-all-roles');
    const batchCheckboxes = document.querySelectorAll('.role-batch-checkbox');
    const batchCountSpan = document.getElementById('batch-selected-count-roles');
    const btnBatchDelete = document.getElementById('btn-batch-delete-roles');
    const batchForm = document.getElementById('form-batch-delete-roles');

    function updateBatchDeleteState() {
        const checkedBoxes = document.querySelectorAll('.role-batch-checkbox:checked');
        const count = checkedBoxes.length;

        if (batchCountSpan) {
            batchCountSpan.textContent = `${count} account${count === 1 ? '' : 's'} selected`;
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
            const count = document.querySelectorAll('.role-batch-checkbox:checked').length;
            if (count === 0) return;

            if (typeof window.openAdminConfirm === 'function') {
                window.openAdminConfirm({
                    title: 'Delete Selected Accounts',
                    message: `Are you sure you want to delete ${count} selected account${count === 1 ? '' : 's'}? This action cannot be undone.`,
                    confirmText: 'Yes, Delete Selected',
                    buttonClass: 'button-danger',
                    onConfirm: function () {
                        batchForm.submit();
                    }
                });
            } else {
                if (confirm(`Are you sure you want to delete ${count} selected account(s)?`)) {
                    batchForm.submit();
                }
            }
        });
    }
});
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Role & Account Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Security & Access Governance</span>
        <h1>Role & Account Management</h1>
        <p>
            Manage application credentials, allocate administrator privileges, link accounts to fellowship member profiles, and ensure safe God Mode governance.
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
                    <strong style="font-size: 20px; color: var(--ink); font-family: 'Manrope', sans-serif;">{{ $users->count() }}</strong>
                </div>
                <div style="width: 1px; height: 28px; background: var(--line);"></div>
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--muted); letter-spacing: .08em; display: block;">Administrators</span>
                    <strong style="font-size: 20px; color: var(--forest); font-family: 'Manrope', sans-serif;">{{ $users->where('role', 'admin')->count() }}</strong>
                </div>
                <div style="width: 1px; height: 28px; background: var(--line);"></div>
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--muted); letter-spacing: .08em; display: block;">Standard Users</span>
                    <strong style="font-size: 20px; color: #315e89; font-family: 'Manrope', sans-serif;">{{ $users->where('role', 'user')->count() }}</strong>
                </div>
            </div>

            <div>
                <span class="role-badge" style="background: var(--cream); color: var(--forest);">
                    Active Session: <strong>{{ $currentUser->username ?? $currentUser->name }}</strong>
                </span>
            </div>
        </div>
    </div>

    {{-- Accounts Ledger Table --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Registered Accounts</h2>
                <small style="color: var(--muted); font-size: 13px;">Overview of all application credentials and privilege levels</small>
            </div>
            <span class="role-badge" style="background: var(--paper); color: var(--ink);">
                God Mode Security
            </span>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table" id="accounts-table">
                <thead>
                    <tr>
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
                            <option value="admin">Admin (Full Administrator God Mode)</option>
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
                            <option value="admin">Admin (Full Administrator God Mode)</option>
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
});
</script>
@endpush

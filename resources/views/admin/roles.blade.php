@extends('layouts.admin')

@section('title', 'Role & Account Management — BYC GROWTH')

@push('styles')
<style>
/* Discord-Style Permissions Toggle Card */
.discord-perm-box {
    background: #f7f9fa;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px;
    margin-top: 12px;
}
.discord-perm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--line);
}
.discord-perm-header-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--muted);
}
.discord-perm-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.discord-perm-action-link {
    background: none;
    border: none;
    padding: 0;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--forest);
    cursor: pointer;
    text-decoration: underline;
    transition: opacity 0.15s ease;
}
.discord-perm-action-link:hover {
    opacity: 0.8;
}
.discord-perm-action-link.btn-clear {
    color: #da373c;
}
.discord-perm-action-link.btn-clear:hover {
    color: #a1282c;
}
.discord-perm-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.discord-perm-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.discord-perm-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.discord-perm-info {
    flex: 1;
    min-width: 0;
}
.discord-perm-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 2px 0;
    display: block;
    cursor: pointer;
}
.discord-perm-desc {
    font-size: 11.5px;
    color: var(--muted);
    line-height: 1.35;
    margin: 0;
}
/* Discord Switch */
.discord-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 28px;
    flex-shrink: 0;
    cursor: pointer;
    margin-top: 2px;
}
.discord-switch input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}
.discord-switch-track {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #80848e;
    transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 28px;
}
.discord-switch-track::before {
    position: absolute;
    content: "✕";
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 900;
    line-height: 1;
    color: #80848e;
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: #ffffff;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
}
.discord-switch input:checked + .discord-switch-track {
    background-color: #23a55a;
}
.discord-switch input:checked + .discord-switch-track::before {
    content: "✓";
    transform: translateX(20px);
    color: #23a55a;
    font-size: 12px;
}
.discord-switch input:focus-visible + .discord-switch-track {
    outline: 2px solid var(--forest);
    outline-offset: 2px;
}
</style>
@endpush

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Security & Access Governance</span>
        <h1>Role & Account Management</h1>
        <p>
            Manage application credentials, allocate administrator privileges, and secure access permissions.
        </p>
    </div>
    <div class="admin-header-actions">
        <button type="button" class="button button-primary button-sm" id="btn-open-add-user">
         Create Account
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
                <a href="{{ route('admin.roles') }}" class="button button-danger button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
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
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                        <span class="role-badge" style="background: #eaf3dc; color: var(--forest); font-weight: 700;">
                                            Admin
                                        </span>
                                        @php
                                            $userPerms = is_array($user->permissions) ? $user->permissions : [];
                                            $permCount = count($userPerms);
                                        @endphp
                                        <small style="color: var(--muted); font-size: 11px; line-height: 1.2;">
                                            @if($permCount === 7)
                                                Full Access (7/7)
                                            @elseif($permCount > 0)
                                                {{ $permCount }} module{{ $permCount === 1 ? '' : 's' }}
                                            @else
                                                <span style="color: var(--danger); font-weight: 600;">No active modules</span>
                                            @endif
                                        </small>
                                    </div>
                                @else
                                    <span class="role-badge" style="background: #dfe9f2; color: #315e89; font-weight: 700;">
                                        User
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
                                        data-permissions='@json($user->permissions ?? [])'
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
    <div class="admin-modal-backdrop modal-backdrop" id="modal-add-user" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 16px; overscroll-behavior: contain;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 16px; max-width: 450px; width: 100%; max-height: calc(100vh - 48px); display: flex; flex-direction: column; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <div style="padding: 12px 18px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">👤</span>
                    <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 800; color: var(--ink);">
                        Create New User Account
                    </h3>
                </div>
                <button type="button" class="btn-close-modal" data-target="modal-add-user" aria-label="Close dialog" style="width: 32px; height: 32px; border-radius: 50%; background: var(--white); border: 1px solid var(--line); display: grid; place-items: center; font-size: 18px; font-weight: 700; color: var(--muted); cursor: pointer; flex-shrink: 0; line-height: 1;">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.roles.store') }}" id="form-add-user" novalidate style="display: flex; flex-direction: column; overflow: hidden; margin: 0; flex: 1; min-height: 0;">
                @csrf
                <div style="padding: 12px 18px; overflow-y: auto; flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 8px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="add-user-username" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Username *</label>
                        <input
                            type="text"
                            name="username"
                            id="add-user-username"
                            class="form-input @error('username') input-invalid @enderror"
                            value="{{ old('username') }}"
                            placeholder="e.g. john_doe"
                            maxlength="60"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                        @error('username')
                            <div class="form-field-error" data-for="add-user-username" style="font-size: 10.5px; margin-top: 1px;">{{ $message }}</div>
                        @enderror
                        <small style="color: var(--muted); font-size: 10px; line-height: 1.25; display: block; margin-top: 1px;">Alphanumeric and underscores only.</small>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="add-user-name" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            id="add-user-name"
                            value="{{ old('name') }}"
                            class="form-input"
                            placeholder="e.g. John Doe (optional)"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="add-user-email" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Email Address *</label>
                        <input
                            type="email"
                            name="email"
                            id="add-user-email"
                            value="{{ old('email') }}"
                            class="form-input @error('email') input-invalid @enderror"
                            placeholder="e.g. user@bycgrowth.org"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                        @error('email')
                            <div class="form-field-error" data-for="add-user-email" style="font-size: 10.5px; margin-top: 1px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="add-user-password" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Password *</label>
                        <input
                            type="password"
                            name="password"
                            id="add-user-password"
                            class="form-input @error('password') input-invalid @enderror"
                            placeholder="Minimum 6 characters"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                        @error('password')
                            <div class="form-field-error" data-for="add-user-password" style="font-size: 10.5px; margin-top: 1px;">{{ $message }}</div>
                        @enderror
                        <small style="color: var(--muted); font-size: 10px; line-height: 1.25; display: block; margin-top: 1px;">Securely encrypted using bcrypt.</small>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="add-user-role" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Access Role *</label>
                        <select name="role" id="add-user-role" class="form-input @error('role') input-invalid @enderror" style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;">
                            <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User (Standard Fellowship Member)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin (Full Administrator)</option>
                        </select>
                        @error('role')
                            <div class="form-field-error" data-for="add-user-role" style="font-size: 10.5px; margin-top: 1px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="padding: 10px 18px; background: var(--paper); border-top: 1px solid var(--line); display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-shrink: 0;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal" data-target="modal-add-user" style="padding: 4px 12px; font-size: 12px;">
                        Cancel
                    </button>
                    <button type="submit" class="button button-primary button-sm" style="min-width: 120px; padding: 4px 14px; font-size: 12px;">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Edit Account --}}
    <div class="admin-modal-backdrop modal-backdrop" id="modal-edit-user" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 16px; overscroll-behavior: contain;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 16px; max-width: 580px; width: 100%; max-height: calc(100vh - 48px); display: flex; flex-direction: column; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <div style="padding: 12px 18px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">✏️</span>
                    <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 800; color: var(--ink);">
                        Edit User Account
                    </h3>
                </div>
                <button type="button" class="btn-close-modal" data-target="modal-edit-user" aria-label="Close dialog" style="width: 32px; height: 32px; border-radius: 50%; background: var(--white); border: 1px solid var(--line); display: grid; place-items: center; font-size: 18px; font-weight: 700; color: var(--muted); cursor: pointer; flex-shrink: 0; line-height: 1;">&times;</button>
            </div>

            <form method="POST" id="form-edit-user" action="" novalidate style="display: flex; flex-direction: column; overflow: hidden; margin: 0; flex: 1; min-height: 0;">
                @csrf
                @method('PUT')
                <div style="padding: 12px 18px; overflow-y: auto; flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 8px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="edit-user-username" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Username *</label>
                        <input
                            type="text"
                            name="username"
                            id="edit-user-username"
                            class="form-input"
                            maxlength="60"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="edit-user-name" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            id="edit-user-name"
                            class="form-input"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="edit-user-email" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Email Address *</label>
                        <input
                            type="email"
                            name="email"
                            id="edit-user-email"
                            class="form-input"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="edit-user-password" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">
                            Change Password
                            <small style="color: var(--muted); font-weight: normal; font-size: 10px;">(Leave empty to preserve current)</small>
                        </label>
                        <input
                            type="password"
                            name="password"
                            id="edit-user-password"
                            class="form-input"
                            placeholder="Leave blank to keep current password"
                            style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;"
                        >
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="edit-user-role" style="display: block; font-weight: 700; font-size: 11px; color: var(--ink); margin-bottom: 2px;">Access Role *</label>
                        <select name="role" id="edit-user-role" class="form-input" style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 7px; width: 100%; box-sizing: border-box;">
                            <option value="user">User (Standard Fellowship Member)</option>
                            <option value="admin">Admin (Full Administrator)</option>
                        </select>
                    </div>

                    {{-- Administrator Module Permissions (Discord-style Switches) --}}
                    <div id="edit-permissions-section" class="discord-perm-box" style="display: none;">
                        <div class="discord-perm-header">
                            <div>
                                <span class="discord-perm-header-title">Module Access Control</span>
                                <small style="display: block; font-size: 11px; color: var(--muted); margin-top: 2px;">
                                    Configure which management panels this administrator can view and access.
                                </small>
                            </div>
                            <div class="discord-perm-header-actions">
                                <button type="button" class="discord-perm-action-link" id="btn-perm-select-all">Select all</button>
                                <span style="color: var(--line);">&bull;</span>
                                <button type="button" class="discord-perm-action-link btn-clear" id="btn-perm-clear-all">Clear permissions</button>
                            </div>
                        </div>

                        <div class="discord-perm-list">
                            @foreach($availablePermissions as $permKey => $permData)
                                <div class="discord-perm-item">
                                    <div class="discord-perm-info">
                                        <label class="discord-perm-label" for="perm-switch-{{ $permKey }}">{{ $permData['name'] }}</label>
                                        <p class="discord-perm-desc">{{ $permData['description'] }}</p>
                                    </div>
                                    <label class="discord-switch" for="perm-switch-{{ $permKey }}" title="Toggle {{ $permData['name'] }}">
                                        <input
                                            type="checkbox"
                                            id="perm-switch-{{ $permKey }}"
                                            name="permissions[]"
                                            value="{{ $permKey }}"
                                            class="discord-switch-input"
                                        >
                                        <span class="discord-switch-track"></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div style="padding: 10px 18px; background: var(--paper); border-top: 1px solid var(--line); display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-shrink: 0;">
                    <button type="button" class="button button-ghost button-sm btn-close-modal" data-target="modal-edit-user" style="padding: 4px 12px; font-size: 12px;">
                        Cancel
                    </button>
                    <button type="submit" class="button button-primary button-sm" style="min-width: 120px; padding: 4px 14px; font-size: 12px;">
                        Save Changes
                    </button>
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

    function openModal(modalEl) {
        if (!modalEl) return;
        modalEl.style.display = 'flex';
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.style.display = 'none';
        const anyOpen = document.querySelectorAll('.admin-modal-backdrop[style*="display: flex"], .admin-modal-backdrop[style*="display: grid"], .modal-backdrop[style*="display: flex"], .modal-backdrop[style*="display: grid"], .modal-backdrop[style*="display: block"]');
        if (anyOpen.length === 0) {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }
    }

    // Custom Form Validation & BYC Growth Error Presentation
    function setFieldError(field, message) {
        if (!field) return;
        clearFieldError(field);
        field.classList.add('input-invalid');
        const errorEl = document.createElement('div');
        errorEl.className = 'form-field-error';
        const fieldId = field.id || field.name;
        if (fieldId) {
            errorEl.setAttribute('data-for', fieldId);
        }
        errorEl.textContent = message;
        field.insertAdjacentElement('afterend', errorEl);
    }

    function clearFieldError(field) {
        if (!field) return;
        field.classList.remove('input-invalid');
        const fieldId = field.id || field.name;
        const parent = field.closest('.form-group') || field.parentElement;
        if (parent) {
            parent.querySelectorAll(`.form-field-error[data-for="${fieldId}"]`).forEach(el => el.remove());
        }
        if (field.nextElementSibling && field.nextElementSibling.classList.contains('form-field-error')) {
            field.nextElementSibling.remove();
        }
    }

    function clearAllErrors(form) {
        if (!form) return;
        form.querySelectorAll('.input-invalid').forEach(el => el.classList.remove('input-invalid'));
        form.querySelectorAll('.form-field-error').forEach(el => el.remove());
    }

    const formAddUser = document.getElementById('form-add-user');
    if (formAddUser) {
        const addUsername = document.getElementById('add-user-username');
        const addEmail = document.getElementById('add-user-email');
        const addPassword = document.getElementById('add-user-password');
        const addRole = document.getElementById('add-user-role');

        [addUsername, addEmail, addPassword, addRole].forEach(input => {
            if (!input) return;
            input.addEventListener('input', () => clearFieldError(input));
            input.addEventListener('change', () => clearFieldError(input));
        });

        formAddUser.addEventListener('reset', () => {
            clearAllErrors(formAddUser);
        });

        formAddUser.addEventListener('submit', function (e) {
            clearAllErrors(formAddUser);
            let hasError = false;

            const uVal = addUsername ? addUsername.value.trim() : '';
            if (!uVal) {
                setFieldError(addUsername, 'Please enter a username.');
                hasError = true;
            } else if (uVal.length < 3) {
                setFieldError(addUsername, 'Username must be at least 3 characters.');
                hasError = true;
            } else if (!/^[a-zA-Z0-9_]+$/.test(uVal)) {
                setFieldError(addUsername, 'Username may only contain letters, numbers, and underscores.');
                hasError = true;
            }

            const eVal = addEmail ? addEmail.value.trim() : '';
            if (!eVal) {
                setFieldError(addEmail, 'Please enter an email address.');
                hasError = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(eVal)) {
                setFieldError(addEmail, 'Please enter a valid email address.');
                hasError = true;
            }

            const pVal = addPassword ? addPassword.value : '';
            if (!pVal) {
                setFieldError(addPassword, 'Please enter a password.');
                hasError = true;
            } else if (pVal.length < 6) {
                setFieldError(addPassword, 'Password must be at least 6 characters.');
                hasError = true;
            }

            const rVal = addRole ? addRole.value : '';
            if (!rVal) {
                setFieldError(addRole, 'Please select an access role.');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                const firstInvalid = formAddUser.querySelector('.input-invalid');
                if (firstInvalid) firstInvalid.focus();
                return false;
            }
        });
    }

    const editModal = document.getElementById('modal-edit-user');
    const formEdit = document.getElementById('form-edit-user');
    const editPermSection = document.getElementById('edit-permissions-section');
    const permInputs = document.querySelectorAll('.discord-switch-input');
    const btnPermSelectAll = document.getElementById('btn-perm-select-all');
    const btnPermClearAll = document.getElementById('btn-perm-clear-all');

    function updatePermissionsUi(role, permissionsList) {
        if (!editPermSection) return;
        const perms = Array.isArray(permissionsList) ? permissionsList : [];
        if (role === 'admin') {
            editPermSection.style.display = 'block';
            permInputs.forEach(input => {
                input.checked = perms.includes(input.value);
            });
        } else {
            editPermSection.style.display = 'none';
            permInputs.forEach(input => {
                input.checked = false;
            });
        }
    }

    if (btnPermSelectAll) {
        btnPermSelectAll.addEventListener('click', function () {
            permInputs.forEach(input => {
                input.checked = true;
            });
        });
    }

    if (btnPermClearAll) {
        btnPermClearAll.addEventListener('click', function () {
            permInputs.forEach(input => {
                input.checked = false;
            });
        });
    }

    if (formEdit) {
        const editUsername = document.getElementById('edit-user-username');
        const editEmail = document.getElementById('edit-user-email');
        const editPassword = document.getElementById('edit-user-password');
        const editRole = document.getElementById('edit-user-role');

        [editUsername, editEmail, editPassword, editRole].forEach(input => {
            if (!input) return;
            input.addEventListener('input', () => clearFieldError(input));
            input.addEventListener('change', () => clearFieldError(input));
        });

        if (editRole) {
            editRole.addEventListener('change', function () {
                const currentChecked = Array.from(permInputs).filter(i => i.checked).map(i => i.value);
                updatePermissionsUi(this.value, currentChecked);
            });
        }

        formEdit.addEventListener('submit', function (e) {
            clearAllErrors(formEdit);
            let hasError = false;

            const uVal = editUsername ? editUsername.value.trim() : '';
            if (!uVal) {
                setFieldError(editUsername, 'Please enter a username.');
                hasError = true;
            } else if (uVal.length < 3) {
                setFieldError(editUsername, 'Username must be at least 3 characters.');
                hasError = true;
            } else if (!/^[a-zA-Z0-9_]+$/.test(uVal)) {
                setFieldError(editUsername, 'Username may only contain letters, numbers, and underscores.');
                hasError = true;
            }

            const eVal = editEmail ? editEmail.value.trim() : '';
            if (!eVal) {
                setFieldError(editEmail, 'Please enter an email address.');
                hasError = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(eVal)) {
                setFieldError(editEmail, 'Please enter a valid email address.');
                hasError = true;
            }

            const pVal = editPassword ? editPassword.value : '';
            if (pVal && pVal.length < 6) {
                setFieldError(editPassword, 'Password must be at least 6 characters.');
                hasError = true;
            }

            const rVal = editRole ? editRole.value : '';
            if (!rVal) {
                setFieldError(editRole, 'Please select an access role.');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                const firstInvalid = formEdit.querySelector('.input-invalid');
                if (firstInvalid) firstInvalid.focus();
                return false;
            }
        });
    }

    @if($errors->hasAny(['username', 'email', 'password', 'role']))
        if (addModal) {
            openModal(addModal);
        }
    @endif

    if (openAddBtn && addModal) {
        openAddBtn.addEventListener('click', function () {
            clearAllErrors(formAddUser);
            openModal(addModal);
        });
    }

    document.querySelectorAll('.btn-edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const data = this.dataset;
            clearAllErrors(formEdit);
            formEdit.action = '/admin/roles/' + data.id;

            document.getElementById('edit-user-username').value = data.username || '';
            document.getElementById('edit-user-name').value = data.name || '';
            document.getElementById('edit-user-email').value = data.email || '';
            document.getElementById('edit-user-password').value = '';
            document.getElementById('edit-user-role').value = data.role || 'user';

            let userPerms = [];
            try {
                userPerms = JSON.parse(data.permissions || '[]');
            } catch (e) {
                userPerms = [];
            }
            updatePermissionsUi(data.role || 'user', userPerms);

            const memberSelect = document.getElementById('edit-user-member-id');
            if (memberSelect) {
                memberSelect.value = data.memberId || '';
            }

            openModal(editModal);
        });
    });

    // Close Modals handler
    document.querySelectorAll('.btn-close-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            if (targetId) {
                closeModal(document.getElementById(targetId));
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.admin-modal-backdrop').forEach(function (modal) {
                if (modal.style.display !== 'none') {
                    closeModal(modal);
                }
            });
        }
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

    // Auto-open Edit User Modal if ?edit=ID is in URL (e.g. from header "Edit Your Account")
    const urlParams = new URLSearchParams(window.location.search);
    const editUserId = urlParams.get('edit');
    if (editUserId) {
        const targetBtn = document.querySelector(`.btn-edit-user[data-id="${editUserId}"]`);
        if (targetBtn) {
            targetBtn.click();
        } else {
            fetch('/admin/roles/' + encodeURIComponent(editUserId))
                .then(res => res.json())
                .then(data => {
                    if (data && data.id && formEdit) {
                        clearAllErrors(formEdit);
                        formEdit.action = '/admin/roles/' + data.id;
                        document.getElementById('edit-user-username').value = data.username || '';
                        document.getElementById('edit-user-name').value = data.name || '';
                        document.getElementById('edit-user-email').value = data.email || '';
                        document.getElementById('edit-user-password').value = '';
                        document.getElementById('edit-user-role').value = data.role || 'admin';
                        updatePermissionsUi(data.role || 'admin', data.permissions || []);
                        openModal(editModal);
                    }
                }).catch(() => {});
        }
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('edit');
        window.history.replaceState({}, document.title, cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : ''));
    }
});
</script>
@endpush

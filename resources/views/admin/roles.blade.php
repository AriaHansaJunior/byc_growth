@extends('layouts.app')

@section('title', 'Role & Account Management — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Back Navigation & Admin Action --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Dashboard
        </a>

        <button type="button" class="button button-primary button-sm" id="btn-open-add-user" style="margin-left: auto;">
            <x-icon name="users" /> Create Account
        </button>
    </nav>

    {{-- Page Header --}}
    <header class="page-header">
        <span class="eyebrow">Access Control</span>
        <h1>Role & Account Management</h1>
        <p>
            Manage user accounts, assign administrative privileges, and maintain authorized access credentials.
        </p>
    </header>

    @if(session('success'))
        <div class="alert-success-box" style="margin-bottom: 24px; padding: 14px 20px; background: #eaf3dc; border: 1px solid var(--lime); border-radius: 12px; color: var(--forest-dark); font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-danger-box" style="margin-bottom: 24px; padding: 14px 20px; background: #fdf0ee; border: 1px solid var(--red); border-radius: 12px; color: var(--red); font-weight: 600;">
            {{ session('error') }}
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

    {{-- Accounts Table Card --}}
    <div style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 28px; box-shadow: var(--shadow); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--line);">
                    <th style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase;">User</th>
                    <th style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Email</th>
                    <th style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Role</th>
                    <th style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Created At</th>
                    <th style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr style="border-bottom: 1px solid var(--line);">
                        <td style="padding: 16px; font-weight: 700; color: var(--ink);">
                            {{ $user->name }}
                            @if($user->id === $currentUser->id)
                                <span style="font-size: 11px; background: var(--cream); color: var(--forest); padding: 2px 8px; border-radius: 12px; margin-left: 6px;">You</span>
                            @endif
                        </td>
                        <td style="padding: 16px; color: var(--muted);">
                            {{ $user->email }}
                        </td>
                        <td style="padding: 16px;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; {{ $user->role === 'admin' ? 'background: #eaf3dc; color: var(--forest);' : 'background: #dfe9f2; color: #315e89;' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td style="padding: 16px; font-size: 13px; color: var(--muted);">
                            {{ $user->created_at ? $user->created_at->format('M d, Y') : '-' }}
                        </td>
                        <td style="padding: 16px; text-align: right;">
                            <div style="display: inline-flex; gap: 8px;">
                                <button
                                    type="button"
                                    class="button button-secondary button-sm btn-edit-user"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    style="padding: 4px 10px; height: 32px; font-size: 12px;"
                                >
                                    Edit
                                </button>

                                @if($user->id !== $currentUser->id)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this account?');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button button-danger button-sm" style="padding: 4px 10px; height: 32px; font-size: 12px;">
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="button button-ghost button-sm" disabled title="Cannot delete current active session" style="padding: 4px 10px; height: 32px; font-size: 12px; opacity: 0.4; cursor: not-allowed;">
                                        Current
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
<div class="game-modal-overlay" id="modal-add-user" style="display: none;">
    <div class="game-modal" style="max-width: 480px; width: 90%;">
        <div class="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif;">Create User Account</h3>
            <button type="button" class="btn-close-modal" id="btn-close-add-user" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Full Name *</label>
                    <input type="text" name="name" required class="input-field" placeholder="e.g. Samuel Christopher" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Email Address *</label>
                    <input type="email" name="email" required class="input-field" placeholder="e.g. samuel@bycgrowth.org" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Password *</label>
                    <input type="password" name="password" required class="input-field" placeholder="Minimum 6 characters" minlength="6" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Access Role *</label>
                    <select name="role" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                        <option value="user">User (Standard Member)</option>
                        <option value="admin">Admin (Full Administrative Privileges)</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px;">
                    <button type="submit" class="button button-primary">Create Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Edit Account --}}
<div class="game-modal-overlay" id="modal-edit-user" style="display: none;">
    <div class="game-modal" style="max-width: 480px; width: 90%;">
        <div class="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 20px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif;">Edit User Account</h3>
            <button type="button" class="btn-close-modal" id="btn-close-edit-user" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
        </div>

        <form method="POST" id="form-edit-user" action="">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Full Name *</label>
                    <input type="text" name="name" id="edit-user-name" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Email Address *</label>
                    <input type="email" name="email" id="edit-user-email" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">
                        Change Password
                        <span style="font-weight: 400; font-size: 12px; color: var(--muted);">(Leave blank to keep current)</span>
                    </label>
                    <input type="password" name="password" minlength="6" class="input-field" placeholder="Leave empty to preserve password" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                </div>

                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Access Role *</label>
                    <select name="role" id="edit-user-role" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                        <option value="user">User (Standard Member)</option>
                        <option value="admin">Admin (Full Administrative Privileges)</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px;">
                    <button type="submit" class="button button-primary">Update Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('modal-add-user');
    const openAddBtn = document.getElementById('btn-open-add-user');
    const closeAddBtn = document.getElementById('btn-close-add-user');

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

    const editModal = document.getElementById('modal-edit-user');
    const closeEditBtn = document.getElementById('btn-close-edit-user');
    const formEdit = document.getElementById('form-edit-user');

    if (closeEditBtn && editModal) {
        closeEditBtn.addEventListener('click', () => {
            editModal.style.display = 'none';
        });
    }

    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const email = btn.dataset.email;
            const role = btn.dataset.role;

            formEdit.action = `/admin/roles/${id}`;
            document.getElementById('edit-user-name').value = name;
            document.getElementById('edit-user-email').value = email;
            document.getElementById('edit-user-role').value = role;

            editModal.style.display = 'flex';
        });
    });
});
</script>
@endsection

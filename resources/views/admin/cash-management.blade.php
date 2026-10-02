@extends('layouts.admin')

@section('title', 'Cash Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Financial Stewardship & Treasury</span>
        <h1>Cash Management Ledger</h1>
        <p>
            Record member contributions, verify transfer proofs, monitor treasury inflow, and maintain transparent fellowship accounting.
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="#record-tx-section" class="button button-primary button-sm" id="btn-quick-record">
            <x-icon name="plus" /> Record Transaction
        </a>
    </div>
</div>
@endsection

@section('content')
    {{-- Treasury Summary Card --}}
    <div class="admin-card" style="background: linear-gradient(135deg, var(--forest) 0%, var(--forest-dark) 100%); color: #fff; margin-bottom: 28px; padding: 28px 32px; border-radius: 20px; box-shadow: var(--shadow);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <span style="color: var(--lime); font-size: 11px; text-transform: uppercase; letter-spacing: .12em; font-weight: 800; display: block; margin-bottom: 4px;">Treasury Balance (Filtered Total)</span>
                <div style="font: 800 clamp(32px, 3.5vw, 44px) 'Manrope', sans-serif; letter-spacing: -.03em; color: #fff;">
                    Rp {{ number_format($totalCash, 0, ',', '.') }}
                </div>
                <small style="color: #b8c5bf; font-size: 13.5px;">Accurate total across all matching transactions in the fellowship treasury</small>
            </div>
        </div>
    </div>

    {{-- Member Quick Shortcuts --}}
    @if($members->isNotEmpty())
        <div class="admin-card" style="padding: 18px 24px; margin-bottom: 24px;">
            <div style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <strong style="font-size: 12.5px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted);">Member Quick-Fill Shortcuts</strong>
                <small style="color: var(--muted); font-size: 12px;">Click a member to auto-fill contributor name, account type, and last amount</small>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @foreach($members->take(12) as $m)
                    <button
                        type="button"
                        class="button button-ghost button-sm btn-member-shortcut"
                        data-id="{{ $m->id }}"
                        data-name="{{ $m->full_name }}"
                        data-account="{{ $shortcuts[$m->id]['account_type'] ?? '' }}"
                        data-amount="{{ $shortcuts[$m->id]['amount'] ?? '' }}"
                        style="font-size: 12px; padding: 4px 12px; border-radius: 99px; height: 32px;"
                    >
                        + {{ $m->full_name }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Search & Filtering Controls --}}
    <div class="admin-card" style="background: var(--cream); border: 1px solid var(--line); border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;">
        <form method="GET" action="{{ route('admin.cash-management') }}" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Search Contributor</label>
                <input type="text" name="name" value="{{ $filters['name'] }}" placeholder="Search name..." class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 150px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Account Type</label>
                <select name="account_type" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="">All Accounts</option>
                    @foreach($accountTypes as $type)
                        <option value="{{ $type }}" {{ $filters['account_type'] === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 130px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Amount</label>
                <input type="number" name="amount" value="{{ $filters['amount'] }}" placeholder="Exact amount..." class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 140px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Input Date</label>
                <input type="date" name="date" value="{{ $filters['date'] }}" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
            </div>

            <div style="min-width: 170px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Sort By</label>
                <select name="sort" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="date_desc" {{ ($filters['sort'] ?? 'date_desc') === 'date_desc' ? 'selected' : '' }}>Date (Newest First)</option>
                    <option value="date_asc" {{ ($filters['sort'] ?? '') === 'date_asc' ? 'selected' : '' }}>Date (Oldest First)</option>
                    <option value="amount_desc" {{ ($filters['sort'] ?? '') === 'amount_desc' ? 'selected' : '' }}>Amount (Highest First)</option>
                    <option value="amount_asc" {{ ($filters['sort'] ?? '') === 'amount_asc' ? 'selected' : '' }}>Amount (Lowest First)</option>
                    <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Contributor (A - Z)</option>
                    <option value="name_desc" {{ ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' }}>Contributor (Z - A)</option>
                </select>
            </div>

            <div style="min-width: 90px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Per Page</label>
                <select name="per_page" class="form-input" style="height: 42px; font-size: 13.5px; padding: 8px 12px;">
                    <option value="5" {{ $currentPerPage == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ $currentPerPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $currentPerPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $currentPerPage == 50 ? 'selected' : '' }}>50</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="button button-primary button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Filter
                </button>
                <a href="{{ route('admin.cash-management') }}" class="button button-ghost button-sm" style="height: 42px; padding: 0 18px; display: inline-flex; align-items: center;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Transaction Ledger Table --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Transactions Ledger</h2>
                <small style="color: var(--muted); font-size: 13px;">Showing page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }} ({{ $transactions->total() }} total records)</small>
            </div>
        </div>

        @if($transactions->isEmpty())
            <div style="text-align: center; padding: 48px 24px; color: var(--muted);">
                <div style="font-size: 36px; margin-bottom: 8px;">💵</div>
                <h3>No transactions found</h3>
                <p>Record a new cash contribution below or adjust your filter query.</p>
            </div>
        @else
            {{-- Batch Actions Toolbar --}}
            <form id="form-batch-delete-cash" method="POST" action="{{ route('admin.cash.batch-delete') }}" style="display: flex; justify-content: space-between; align-items: center; background: var(--cream); border: 1px solid var(--line); border-radius: 10px; padding: 10px 16px; margin-bottom: 16px;">
                @csrf
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span id="batch-selected-count-cash" style="font-weight: 700; font-size: 13px; color: var(--forest);">
                        0 transactions selected
                    </span>
                </div>
                <div>
                    <button type="button" class="button button-danger button-sm" id="btn-batch-delete-cash" disabled style="opacity: 0.5; height: 32px; font-size: 12px;">
                        Delete Selected
                    </button>
                </div>
            </form>

            <div class="admin-table-wrap">
                <table class="admin-table" id="admin-cash-table">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="check-select-all-cash" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;" title="Select all on this page">
                            </th>
                            <th>Date & Time</th>
                            <th>Contributor</th>
                            <th>Account / Type</th>
                            <th>Amount</th>
                            <th>Transfer Proof</th>
                            <th>Recorded By</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr id="tx-row-{{ $tx->id }}">
                                <td style="text-align: center;">
                                    <input type="checkbox" name="ids[]" value="{{ $tx->id }}" form="form-batch-delete-cash" class="cash-batch-checkbox" style="width: 17px; height: 17px; accent-color: var(--forest); cursor: pointer;">
                                </td>
                                <td>
                                    <span style="color: var(--muted); font-size: 13px;">
                                        {{ $tx->created_at->format('M j, Y H:i:s') }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--ink); font-size: 14.5px;">
                                        {{ $tx->member ? $tx->member->full_name : $tx->contributor_name }}
                                    </strong>
                                    @if($tx->member)
                                        <small style="display: block; color: var(--muted); font-size: 11px;">(Member Record #{{ $tx->member->id }})</small>
                                    @else
                                        <small style="display: block; color: var(--muted); font-size: 11px;">(Fellowship Guest)</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="role-badge" style="background: var(--cream); color: var(--forest);">
                                        {{ $tx->account_type ?: 'Standard' }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--forest); font-size: 14.5px; font-family: 'Manrope', sans-serif;">
                                        Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                    </strong>
                                </td>
                                <td>
                                    @if($tx->proof_file_url)
                                        <button
                                            type="button"
                                            class="button button-ghost button-sm btn-open-proof"
                                            data-url="{{ $tx->proof_file_url }}"
                                            data-contributor="{{ $tx->member ? $tx->member->full_name : $tx->contributor_name }}"
                                            data-amount="Rp {{ number_format($tx->amount, 0, ',', '.') }}"
                                            data-date="{{ $tx->created_at->format('M j, Y') }}"
                                            style="font-size: 11.5px; padding: 3px 10px; height: 28px; display: inline-flex; align-items: center; gap: 4px;"
                                            title="View Transfer Proof"
                                        >
                                            📷 View Proof
                                        </button>
                                    @else
                                        <span style="color: var(--muted); font-size: 12px; font-style: italic;">No proof</span>
                                    @endif
                                </td>
                                <td>
                                    <small style="color: var(--muted); font-size: 12px;">
                                        {{ $tx->user->username ?? $tx->user->name ?? 'System' }}
                                    </small>
                                </td>
                                <td style="text-align: right;">
                                    <div class="admin-action-group" style="justify-content: flex-end; gap: 6px;">
                                        <button
                                            type="button"
                                            class="button button-ghost button-sm btn-edit-tx"
                                            data-id="{{ $tx->id }}"
                                            data-member-id="{{ $tx->member_id ?? '' }}"
                                            data-contributor="{{ $tx->contributor_name }}"
                                            data-account="{{ $tx->account_type }}"
                                            data-amount="{{ (float)$tx->amount }}"
                                            data-proof-url="{{ $tx->proof_file_url ?? '' }}"
                                            style="font-size: 12px; padding: 4px 8px; height: 30px;"
                                            title="Edit Transaction"
                                        >
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('admin.cash.destroy', $tx->id) }}" style="display: inline; margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="button button-danger button-sm"
                                                style="font-size: 12px; padding: 4px 8px; height: 30px;"
                                                data-admin-confirm="Are you sure you want to delete this cash transaction of Rp {{ number_format($tx->amount, 0, ',', '.') }} for {{ $tx->contributor_name }}? Associated proof media will be safely cleaned up."
                                                title="Delete Transaction"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div style="margin-top: 20px;">
                    {{ $transactions->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Record New Transaction Card --}}
    <div id="record-tx-section" class="admin-card" style="margin-top: 28px;">
        <div class="admin-card-header">
            <div>
                <h2>Record New Contribution</h2>
                <small style="color: var(--muted); font-size: 13px;">File an official cash transfer or cash deposit into fellowship records.</small>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.cash.store') }}" enctype="multipart/form-data" id="form-record-tx">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 20px;">
                {{-- Member Selection --}}
                <div class="form-group">
                    <label class="form-label" for="record-member-id">Associated Member *</label>
                    <select name="member_id" id="record-member-id" class="form-input" required>
                        <option value="">-- Choose Member --</option>
                        @foreach($members as $m)
                            <option
                                value="{{ $m->id }}"
                                data-name="{{ $m->full_name }}"
                                data-account="{{ $shortcuts[$m->id]['account_type'] ?? '' }}"
                                data-amount="{{ $shortcuts[$m->id]['amount'] ?? '' }}"
                            >
                                {{ $m->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Account Type --}}
                <div class="form-group">
                    <label class="form-label" for="record-account-type">Source Bank / Account Type *</label>
                    <input
                        type="text"
                        id="record-account-type"
                        name="account_type"
                        class="form-input"
                        required
                        placeholder="e.g. BCA, Mandiri, Cash"
                    >
                </div>

                {{-- Transfer Amount --}}
                <div class="form-group">
                    <label class="form-label" for="record-amount">Transfer Amount (IDR) *</label>
                    <input
                        type="number"
                        id="record-amount"
                        name="amount"
                        class="form-input"
                        required
                        min="1000"
                        step="1000"
                        placeholder="e.g. 50000"
                    >
                </div>

                {{-- Proof Image File --}}
                <div class="form-group">
                    <label class="form-label" for="record-proof">Transfer Proof Image *</label>
                    <input
                        type="file"
                        id="record-proof"
                        name="proof"
                        class="form-input"
                        required
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                    >
                    <small style="color: var(--muted); font-size: 11px;">Image only (JPG, PNG, WEBP, max 5MB). PDF/documents rejected.</small>
                </div>

                {{-- System Input Time --}}
                <div class="form-group">
                    <label class="form-label">System Input Time</label>
                    <div style="padding: 10px 14px; background: var(--paper); border: 1px dashed var(--line); border-radius: 10px; font-size: 12.5px; color: var(--muted); display: flex; align-items: center; gap: 8px;">
                        <span>⏱️</span>
                        <span>Auto-recorded on server submission (Asia/Jakarta)</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="reset" class="button button-ghost button-sm">
                    Reset Form
                </button>
                <button type="submit" class="button button-primary button-sm" style="min-width: 160px;">
                    <x-icon name="check" /> Submit Transaction
                </button>
            </div>
        </form>
    </div>

    {{-- Proof Image Viewer Modal --}}
    <div id="modal-proof-viewer" class="admin-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.75); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 18px; max-width: 600px; width: 100%; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <div style="padding: 16px 20px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 id="proof-viewer-title" style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 800; color: var(--ink);">
                        Transfer Proof
                    </h3>
                    <small id="proof-viewer-sub" style="color: var(--muted); font-size: 12px;"></small>
                </div>
                <button type="button" class="btn-close-modal" data-target="modal-proof-viewer" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
            </div>
            <div style="padding: 20px; text-align: center; background: #000; min-height: 250px; display: flex; align-items: center; justify-content: center;">
                <img id="proof-viewer-img" src="" alt="Transfer Proof" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px;">
            </div>
            <div style="padding: 14px 20px; background: var(--paper); border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
                <a id="proof-viewer-link" href="" target="_blank" class="button button-ghost button-sm" style="font-size: 12px;">
                    Open Full Image &rarr;
                </a>
                <button type="button" class="button button-primary button-sm btn-close-modal" data-target="modal-proof-viewer">
                    Done
                </button>
            </div>
        </div>
    </div>

    {{-- Edit Transaction Modal --}}
    <div id="modal-edit-tx" class="admin-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(18, 30, 23, 0.65); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
        <div class="admin-modal-card" style="background: var(--white); border-radius: 18px; max-width: 540px; width: 100%; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--line);">
            <form id="form-edit-tx" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div style="padding: 20px 24px; background: var(--cream); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">✏️</span>
                        <h3 style="margin: 0; font-family: 'Manrope', sans-serif; font-size: 18px; font-weight: 800; color: var(--ink);">
                            Edit Cash Transaction
                        </h3>
                    </div>
                    <button type="button" class="btn-close-modal" data-target="modal-edit-tx" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); line-height: 1;">&times;</button>
                </div>
                <div style="padding: 24px;">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="edit-tx-member">Associated Member</label>
                        <select name="member_id" id="edit-tx-member" class="form-input">
                            <option value="">-- Non-Member / Custom Contributor --</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}">{{ $m->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="edit-tx-contributor">Contributor Name</label>
                        <input type="text" id="edit-tx-contributor" name="contributor_name" class="form-input" placeholder="Contributor Name">
                        <small style="color: var(--muted); font-size: 11px;">Automatically syncs with selected member, or can be custom.</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="edit-tx-account">Source Bank / Account Type *</label>
                        <input type="text" id="edit-tx-account" name="account_type" class="form-input" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="edit-tx-amount">Amount (IDR) *</label>
                        <input type="number" id="edit-tx-amount" name="amount" class="form-input" required min="1" step="1000">
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label class="form-label" for="edit-tx-proof">Replacement Proof Image (Optional)</label>
                        <input type="file" id="edit-tx-proof" name="proof" class="form-input" accept="image/jpeg,image/png,image/jpg,image/webp">
                        <small style="color: var(--muted); font-size: 11px;">Upload only if replacing existing proof. Old proof will be safely cleaned up.</small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="button button-ghost button-sm btn-close-modal" data-target="modal-edit-tx">
                            Cancel
                        </button>
                        <button type="submit" class="button button-primary button-sm" style="min-width: 120px;">
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
    // Member dropdown change listener for auto-fill in Record Form
    const memberSelect = document.getElementById('record-member-id');
    const accountInput = document.getElementById('record-account-type');
    const amountInput = document.getElementById('record-amount');

    if (memberSelect) {
        memberSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (selected && selected.value) {
                const acc = selected.getAttribute('data-account');
                const amt = selected.getAttribute('data-amount');
                if (acc && accountInput && !accountInput.value) {
                    accountInput.value = acc;
                }
                if (amt && amountInput && !amountInput.value) {
                    amountInput.value = amt;
                }
            }
        });
    }

    // Member Shortcut Buttons
    document.querySelectorAll('.btn-member-shortcut').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const memberId = this.dataset.id;
            const account = this.dataset.account;
            const amount = this.dataset.amount;

            if (memberSelect) {
                memberSelect.value = memberId;
            }
            if (accountInput && account) {
                accountInput.value = account;
            }
            if (amountInput && amount) {
                amountInput.value = amount;
            }

            const recordSection = document.getElementById('record-tx-section');
            if (recordSection) {
                recordSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Proof Modal Viewer
    document.querySelectorAll('.btn-open-proof').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const url = this.dataset.url;
            const contributor = this.dataset.contributor;
            const amount = this.dataset.amount;
            const date = this.dataset.date;

            document.getElementById('proof-viewer-title').textContent = 'Proof: ' + contributor + ' (' + amount + ')';
            document.getElementById('proof-viewer-sub').textContent = 'Transferred on ' + date;
            document.getElementById('proof-viewer-img').src = url;
            document.getElementById('proof-viewer-link').href = url;

            const modal = document.getElementById('modal-proof-viewer');
            modal.style.display = 'flex';
        });
    });

    // Edit Transaction Modal
    document.querySelectorAll('.btn-edit-tx').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const memberId = this.dataset.memberId;
            const contributor = this.dataset.contributor;
            const account = this.dataset.account;
            const amount = this.dataset.amount;

            const form = document.getElementById('form-edit-tx');
            form.action = '/admin/cash-management/' + id;

            document.getElementById('edit-tx-member').value = memberId || '';
            document.getElementById('edit-tx-contributor').value = contributor || '';
            document.getElementById('edit-tx-account').value = account || '';
            document.getElementById('edit-tx-amount').value = amount || '';

            const modal = document.getElementById('modal-edit-tx');
            modal.style.display = 'flex';
        });
    });

    // Edit Modal Member sync
    const editMemberSelect = document.getElementById('edit-tx-member');
    if (editMemberSelect) {
        editMemberSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (selected && selected.value) {
                document.getElementById('edit-tx-contributor').value = selected.textContent.trim();
            }
        });
    }

    // Close Modals
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
    const selectAllCheckbox = document.getElementById('check-select-all-cash');
    const batchCheckboxes = document.querySelectorAll('.cash-batch-checkbox');
    const batchCountSpan = document.getElementById('batch-selected-count-cash');
    const btnBatchDelete = document.getElementById('btn-batch-delete-cash');
    const batchForm = document.getElementById('form-batch-delete-cash');

    function updateBatchDeleteState() {
        const checkedBoxes = document.querySelectorAll('.cash-batch-checkbox:checked');
        const count = checkedBoxes.length;

        if (batchCountSpan) {
            batchCountSpan.textContent = `${count} transaction${count === 1 ? '' : 's'} selected`;
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
            const count = document.querySelectorAll('.cash-batch-checkbox:checked').length;
            if (count === 0) return;

            if (typeof window.openAdminConfirm === 'function') {
                window.openAdminConfirm({
                    title: 'Delete Selected Cash Transactions',
                    message: `Are you sure you want to delete ${count} selected transaction${count === 1 ? '' : 's'}? All associated proof media files will be permanently removed.`,
                    confirmText: 'Yes, Delete Selected',
                    buttonClass: 'button-danger',
                    onConfirm: function () {
                        batchForm.submit();
                    }
                });
            } else {
                if (confirm(`Are you sure you want to delete ${count} selected transaction(s)?`)) {
                    batchForm.submit();
                }
            }
        });
    }
});
</script>
@endpush

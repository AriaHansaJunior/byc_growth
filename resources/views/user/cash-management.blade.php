@extends('layouts.app')

@section('title', 'Cash Management — BYC GROWTH')

@section('content')
<div class="page-shell">
    <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <span style="font-size: 13px; font-weight: 700; color: var(--forest); background: #eaf3dc; padding: 6px 14px; border-radius: 20px; border: 1px solid var(--lime);">
            Admin Financial Stewardship
        </span>
    </div>

    {{-- Page Header --}}
    <header class="page-header">
        <span class="eyebrow">Financial Stewardship & Treasury</span>
        <h1>Cash Management</h1>
        <p>
            Record member contributions, verify transfer proofs, monitor treasury inflow, and maintain transparent fellowship accounting.
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

    {{-- Record New Transaction Card --}}
    <div style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 28px 32px; box-shadow: var(--shadow); margin-bottom: 32px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 14px;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--cream); display: flex; align-items: center; justify-content: center; color: var(--forest);">
                <x-icon name="cash" />
            </div>
            <div>
                <h2 style="font-family: 'Manrope', sans-serif; font-size: 20px; font-weight: 800; color: var(--ink); margin: 0;">Record Contribution</h2>
                <small style="color: var(--muted);">Select a member to auto-fill payment shortcut, or enter new details.</small>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.cash.store') }}" enctype="multipart/form-data" id="form-cash-transaction">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                {{-- Member Selection (Searchable Dropdown) --}}
                <div class="searchable-member-wrapper" style="position: relative;">
                    <label for="cash-member-search" style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">
                        Select Member *
                    </label>

                    {{-- Search Input with Clear Button --}}
                    <div style="position: relative; display: flex; align-items: center;">
                        <input
                            type="text"
                            id="cash-member-search"
                            placeholder="Type to search member (e.g. Mega)..."
                            autocomplete="off"
                            class="input-field"
                            style="width: 100%; padding: 10px 38px 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit; font-size: 14px; background: var(--white); color: var(--ink);"
                        >
                        <button
                            type="button"
                            id="btn-clear-member"
                            style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: var(--muted); font-size: 18px; line-height: 1; padding: 4px; display: none;"
                            title="Clear member selection"
                            aria-label="Clear member selection"
                        >&times;</button>
                    </div>

                    {{-- Searchable Dropdown List --}}
                    <div
                        id="cash-member-dropdown"
                        style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; max-height: 220px; overflow-y: auto; background: var(--white); border: 1px solid var(--line); border-radius: 10px; box-shadow: var(--shadow); z-index: 250;"
                    >
                        <div id="cash-member-empty-notice" style="display: none; padding: 12px 14px; font-size: 13px; color: var(--muted); text-align: center;">
                            No members found matching your search.
                        </div>
                        <div id="cash-member-items">
                            @foreach($members as $member)
                                <div
                                    class="cash-member-item"
                                    data-id="{{ $member->id }}"
                                    data-name="{{ $member->full_name }}"
                                    data-account="{{ $shortcuts[$member->id]['account_type'] ?? '' }}"
                                    data-amount="{{ $shortcuts[$member->id]['amount'] ?? '' }}"
                                    style="padding: 10px 14px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--paper); font-size: 14px; color: var(--ink); transition: background .15s ease;"
                                >
                                    <span class="member-display-name" style="font-weight: 600;">{{ $member->full_name }}</span>
                                    @if(isset($shortcuts[$member->id]))
                                        <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 10px; background: #eaf3dc; color: var(--forest); border: 1px solid var(--lime);">
                                            {{ $shortcuts[$member->id]['account_type'] }} &bull; Rp {{ number_format($shortcuts[$member->id]['amount'], 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span style="font-size: 11px; color: var(--muted);">New contributor</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Synchronized native select for accessibility and form submission --}}
                    <select name="member_id" id="cash-member-select" required style="position: absolute; opacity: 0; width: 1px; height: 1px; top: 30px; left: 10px; z-index: -1;">
                        <option value="">-- Choose Member --</option>
                        @foreach($members as $member)
                            <option
                                value="{{ $member->id }}"
                                data-name="{{ $member->full_name }}"
                                data-account="{{ $shortcuts[$member->id]['account_type'] ?? '' }}"
                                data-amount="{{ $shortcuts[$member->id]['amount'] ?? '' }}"
                            >
                                {{ $member->full_name }}
                            </option>
                        @endforeach
                    </select>

                    <div id="shortcut-status-badge" style="display: none; margin-top: 6px; font-size: 12px; font-weight: 600;"></div>
                </div>

                {{-- Account Type --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Source Bank / Account Type *</label>
                    <input
                        type="text"
                        name="account_type"
                        id="cash-account-type"
                        required
                        placeholder="e.g. BCA, Mandiri, BRI, Cash"
                        class="input-field"
                        style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;"
                    >
                </div>

                {{-- Transfer Amount --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Transfer Amount (IDR) *</label>
                    <input
                        type="number"
                        name="amount"
                        id="cash-amount"
                        required
                        step="1000"
                        min="1"
                        placeholder="Amount in Rupiah"
                        class="input-field"
                        style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;"
                    >
                </div>

                {{-- Transfer Proof --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Transfer Proof Image *</label>
                    <input
                        type="file"
                        name="proof"
                        id="cash-proof"
                        required
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="input-field"
                        style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;"
                    >
                    <small style="color: var(--muted); font-size: 11px;">Image only (JPG, PNG, WEBP). Rejects PDF/docs.</small>
                </div>

                {{-- 5. System Input Time (Server Timestamp) --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">System Input Time</label>
                    <div style="padding: 10px 14px; background: var(--paper); border: 1px dashed var(--line); border-radius: 10px; font-size: 13px; color: var(--muted); display: flex; align-items: center; gap: 8px;">
                        <span>⏱️</span>
                        <span>Auto-recorded on server submission (Asia/Jakarta)</span>
                    </div>
                </div>
            </div>


            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; margin-top: 20px;">
                <button type="button" id="btn-reset-cash-form" class="button button-secondary">
                    <x-icon name="refresh" /> Reset Form
                </button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check" /> Submit Contribution
                </button>
            </div>
        </form>
    </div>

    {{-- Filters and Search Bar --}}
    <div style="background: var(--cream); border: 1px solid var(--line); border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;">
        <form method="GET" action="{{ route('cash-management') }}" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Search Name</label>
                <input type="text" name="name" value="{{ $filters['name'] }}" placeholder="Search contributor..." class="input-field" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;">
            </div>

            <div style="min-width: 150px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Account Type</label>
                <select name="account_type" class="input-field" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;">
                    <option value="">All Accounts</option>
                    @foreach($accountTypes as $type)
                        <option value="{{ $type }}" {{ $filters['account_type'] === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 130px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Amount</label>
                <input type="number" name="amount" value="{{ $filters['amount'] }}" placeholder="Exact amount..." class="input-field" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;">
            </div>

            <div style="min-width: 140px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Input Date</label>
                <input type="date" name="date" value="{{ $filters['date'] }}" class="input-field" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;">
            </div>

            <div style="min-width: 110px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Per Page</label>
                <select name="per_page" class="input-field" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;">
                    <option value="5" {{ $currentPerPage == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ $currentPerPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $currentPerPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $currentPerPage == 50 ? 'selected' : '' }}>50</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="button button-primary button-sm" style="height: 38px;">
                    Filter
                </button>
                <a href="{{ route('cash-management') }}" class="button button-secondary button-sm" style="height: 38px;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Transactions Output Table --}}
    <div style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 24px; box-shadow: var(--shadow); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;" id="cash-transactions-table">
            <thead>
                <tr style="border-bottom: 2px solid var(--line);">
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Name</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Account Type</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Amount</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; text-align: center;">Proof</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Input Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $tx)
                    <tr style="border-bottom: 1px solid var(--line);">
                        <td style="padding: 14px; font-weight: 700; color: var(--muted); font-size: 13px;">
                            {{ $transactions->firstItem() + $index }}
                        </td>
                        <td style="padding: 14px; font-weight: 700; color: var(--ink);">
                            {{ $tx->member ? $tx->member->full_name : $tx->contributor_name }}
                        </td>
                        <td style="padding: 14px; color: var(--muted);">
                            <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; background: var(--paper); border: 1px solid var(--line); font-size: 12px; font-weight: 600;">
                                {{ $tx->account_type ?: 'Standard' }}
                            </span>
                        </td>
                        <td style="padding: 14px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                            Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </td>
                        <td style="padding: 14px; text-align: center;">
                            @if($tx->proof && $tx->proof->getUrl())
                                <button
                                    type="button"
                                    class="button button-ghost button-sm btn-view-proof"
                                    data-url="{{ $tx->proof->getUrl() }}"
                                    data-title="Proof: {{ $tx->member ? $tx->member->full_name : $tx->contributor_name }}"
                                    style="padding: 4px 10px; font-size: 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px;"
                                    title="View Proof Image"
                                >
                                    🖼️ View
                                </button>
                            @else
                                <span style="color: var(--muted); font-size: 12px; font-style: italic;">No proof</span>
                            @endif
                        </td>
                        <td style="padding: 14px; font-size: 13px; color: var(--muted);">
                            {{ $tx->created_at ? $tx->created_at->format('M d, Y H:i:s') : ($tx->transaction_date ? $tx->transaction_date->format('M d, Y') : '-') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 36px; text-align: center; color: var(--muted); font-size: 14px;">
                            No cash transactions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid var(--ink); background: var(--paper);">
                    <td colspan="3" style="padding: 16px; font-weight: 800; font-family: 'Manrope', sans-serif; font-size: 16px; color: var(--ink);">
                        Total Cash Contribution: <span style="font-size: 13px; font-weight: 600; color: var(--muted);">(Total Cash Inflow:)</span>
                    </td>
                    <td colspan="3" style="padding: 16px; font-weight: 800; font-family: 'Manrope', sans-serif; font-size: 20px; color: var(--forest);" id="total-cash-display">
                        Rp {{ number_format($totalCash, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Pagination Links --}}
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            {{ $transactions->links() }}
        </div>
    </div>
</div>

{{-- Proof Image Preview Modal --}}
<div class="game-modal-overlay" id="modal-proof-preview" style="display: none;">
    <div class="game-modal" style="max-width: 600px; width: 90%; text-align: center;">
        <div class="game-modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-family: 'Manrope', sans-serif;" id="modal-proof-title">Transfer Proof</h3>
            <button type="button" class="btn-close-modal" id="btn-close-proof" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted);">&times;</button>
        </div>
        <div style="max-height: 70vh; overflow: auto; border-radius: 12px; border: 1px solid var(--line); background: var(--cream);">
            <img src="" id="modal-proof-img" alt="Transfer Proof" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
        </div>
    </div>
</div>

<style>
.cash-member-item:hover, .cash-member-item.is-selected {
    background: var(--cream);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // S10 Searchable Member Dropdown & Authoritative Shortcut Logic
    const memberSelect = document.getElementById('cash-member-select');
    const memberSearch = document.getElementById('cash-member-search');
    const memberDropdown = document.getElementById('cash-member-dropdown');
    const memberItems = document.querySelectorAll('.cash-member-item');
    const emptyNotice = document.getElementById('cash-member-empty-notice');
    const btnClearMember = document.getElementById('btn-clear-member');
    const accountInput = document.getElementById('cash-account-type');
    const amountInput = document.getElementById('cash-amount');
    const proofInput = document.getElementById('cash-proof');
    const statusBadge = document.getElementById('shortcut-status-badge');
    const btnResetForm = document.getElementById('btn-reset-cash-form');
    const cashForm = document.getElementById('form-cash-transaction');

    function applyShortcutForMember(memberId, fallbackAccount, fallbackAmount) {
        if (!memberId) {
            accountInput.value = '';
            amountInput.value = '';
            if (statusBadge) statusBadge.style.display = 'none';
            return;
        }

        // Proof must NEVER be populated or reused — always reset/remain empty
        if (proofInput) proofInput.value = '';

        // Query authoritative backend endpoint
        fetch(`/admin/cash-management/shortcut/${memberId}`)
            .then(res => res.json())
            .then(data => {
                if (data.has_shortcut) {
                    accountInput.value = data.account_type || '';
                    amountInput.value = data.amount || '';
                    if (statusBadge) {
                        statusBadge.textContent = `✓ Auto-filled previous: ${data.account_type} • Rp ${Number(data.amount).toLocaleString('id-ID')} (editable)`;
                        statusBadge.style.color = 'var(--forest)';
                        statusBadge.style.display = 'block';
                    }
                } else {
                    accountInput.value = '';
                    amountInput.value = '';
                    if (statusBadge) {
                        statusBadge.textContent = 'ℹ No previous transaction — please enter bank and amount.';
                        statusBadge.style.color = 'var(--muted)';
                        statusBadge.style.display = 'block';
                    }
                }
            })
            .catch(() => {
                // Fallback to pre-rendered HTML dataset
                if (fallbackAccount || fallbackAmount) {
                    accountInput.value = fallbackAccount || '';
                    amountInput.value = fallbackAmount || '';
                    if (statusBadge) {
                        statusBadge.textContent = `✓ Auto-filled previous: ${fallbackAccount} • Rp ${Number(fallbackAmount).toLocaleString('id-ID')} (editable)`;
                        statusBadge.style.color = 'var(--forest)';
                        statusBadge.style.display = 'block';
                    }
                } else {
                    accountInput.value = '';
                    amountInput.value = '';
                    if (statusBadge) {
                        statusBadge.textContent = 'ℹ No previous transaction — please enter bank and amount.';
                        statusBadge.style.color = 'var(--muted)';
                        statusBadge.style.display = 'block';
                    }
                }
            });
    }

    function selectMember(id, name, account, amount) {
        if (memberSelect) memberSelect.value = id;
        if (memberSearch) {
            memberSearch.value = name;
            memberSearch.setCustomValidity('');
        }
        if (btnClearMember) btnClearMember.style.display = 'block';
        if (memberDropdown) memberDropdown.style.display = 'none';

        memberItems.forEach(item => {
            if (item.dataset.id === String(id)) {
                item.classList.add('is-selected');
            } else {
                item.classList.remove('is-selected');
            }
        });

        applyShortcutForMember(id, account, amount);
    }

    function clearMemberSelection() {
        if (memberSelect) memberSelect.value = '';
        if (memberSearch) {
            memberSearch.value = '';
            memberSearch.setCustomValidity('');
        }
        if (accountInput) accountInput.value = '';
        if (amountInput) amountInput.value = '';
        if (proofInput) proofInput.value = '';
        if (btnClearMember) btnClearMember.style.display = 'none';
        if (statusBadge) statusBadge.style.display = 'none';

        memberItems.forEach(item => item.classList.remove('is-selected'));
        filterMembers('');
    }

    function filterMembers(query) {
        const q = (query || '').trim().toLowerCase();
        let matchCount = 0;

        memberItems.forEach(item => {
            const name = (item.dataset.name || '').toLowerCase();
            if (!q || name.includes(q)) {
                item.style.display = 'flex';
                matchCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (emptyNotice) {
            emptyNotice.style.display = (matchCount === 0) ? 'block' : 'none';
        }
    }

    if (memberSearch) {
        memberSearch.addEventListener('input', () => {
            filterMembers(memberSearch.value);
            if (memberDropdown) memberDropdown.style.display = 'block';
        });

        memberSearch.addEventListener('focus', () => {
            filterMembers(memberSearch.value);
            if (memberDropdown) memberDropdown.style.display = 'block';
        });

        memberSearch.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (memberDropdown) memberDropdown.style.display = 'none';
            } else if (e.key === 'Enter') {
                // If dropdown is open, pick first visible item
                if (memberDropdown && memberDropdown.style.display === 'block') {
                    const firstVisible = Array.from(memberItems).find(i => i.style.display !== 'none');
                    if (firstVisible) {
                        e.preventDefault();
                        selectMember(
                            firstVisible.dataset.id,
                            firstVisible.dataset.name,
                            firstVisible.dataset.account,
                            firstVisible.dataset.amount
                        );
                    }
                }
            }
        });
    }

    memberItems.forEach(item => {
        item.addEventListener('click', () => {
            selectMember(
                item.dataset.id,
                item.dataset.name,
                item.dataset.account,
                item.dataset.amount
            );
        });
    });

    if (btnClearMember) {
        btnClearMember.addEventListener('click', () => {
            clearMemberSelection();
        });
    }

    // Close dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.searchable-member-wrapper')) {
            if (memberDropdown) memberDropdown.style.display = 'none';
            // Restore search text to selected member name if valid, or clear
            if (memberSelect && memberSearch) {
                if (memberSelect.value) {
                    const selectedOpt = memberSelect.options[memberSelect.selectedIndex];
                    if (selectedOpt && selectedOpt.value) {
                        memberSearch.value = selectedOpt.dataset.name || selectedOpt.textContent.trim();
                    }
                } else {
                    memberSearch.value = '';
                }
            }
        }
    });

    // Synchronize if native select is modified programmatically
    if (memberSelect) {
        memberSelect.addEventListener('change', () => {
            const selectedOpt = memberSelect.options[memberSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                selectMember(
                    selectedOpt.value,
                    selectedOpt.dataset.name || selectedOpt.textContent.trim(),
                    selectedOpt.dataset.account,
                    selectedOpt.dataset.amount
                );
            } else {
                clearMemberSelection();
            }
        });
    }

    // Reset Form button
    if (btnResetForm) {
        btnResetForm.addEventListener('click', () => {
            clearMemberSelection();
            if (cashForm) cashForm.reset();
        });
    }

    if (cashForm) {
        cashForm.addEventListener('submit', (e) => {
            if (!memberSelect || !memberSelect.value) {
                e.preventDefault();
                if (memberSearch) {
                    memberSearch.focus();
                    memberSearch.setCustomValidity('Please select a member.');
                    memberSearch.reportValidity();
                }
                return false;
            }
        });

        cashForm.addEventListener('reset', () => {
            clearMemberSelection();
        });
    }

    // Proof preview modal
    const proofModal = document.getElementById('modal-proof-preview');
    const proofImg = document.getElementById('modal-proof-img');
    const proofTitle = document.getElementById('modal-proof-title');
    const closeProofBtn = document.getElementById('btn-close-proof');

    function closeProofModal() {
        if (proofModal) {
            proofModal.style.display = 'none';
            if (proofImg) proofImg.src = '';
        }
    }

    if (closeProofBtn) {
        closeProofBtn.addEventListener('click', closeProofModal);
    }

    if (proofModal) {
        proofModal.addEventListener('click', (e) => {
            if (e.target === proofModal) {
                closeProofModal();
            }
        });
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && proofModal && proofModal.style.display !== 'none') {
            closeProofModal();
        }
    });

    document.querySelectorAll('.btn-view-proof').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.dataset.url;
            const title = btn.dataset.title;

            if (proofImg && url) {
                proofImg.src = url;
            }
            if (proofTitle && title) {
                proofTitle.textContent = title;
            }
            if (proofModal) {
                proofModal.style.display = 'flex';
            }
        });
    });
});
</script>
@endsection

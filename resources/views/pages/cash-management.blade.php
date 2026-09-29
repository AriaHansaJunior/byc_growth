@extends('layouts.app')

@section('title', 'Cash Management — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Back Navigation & Role Badge --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>

        <div style="margin-left: auto; display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 13px; font-weight: 700; color: var(--forest); background: #eaf3dc; padding: 6px 14px; border-radius: 20px; border: 1px solid var(--lime);">
                Admin Financial Stewardship
            </span>
        </div>
    </nav>

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
                {{-- Member Selection --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--ink);">Select Member *</label>
                    <select name="member_id" id="cash-member-select" required class="input-field" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit;">
                        <option value="">-- Choose Member --</option>
                        @foreach($members as $member)
                            <option
                                value="{{ $member->id }}"
                                data-account="{{ $shortcuts[$member->id]['account_type'] ?? '' }}"
                                data-amount="{{ $shortcuts[$member->id]['amount'] ?? '' }}"
                            >
                                {{ $member->full_name }}
                            </option>
                        @endforeach
                    </select>
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
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
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
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">#</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Name</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Account Type</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Transfer Amount</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; text-align: center;">Proof</th>
                    <th style="padding: 12px 14px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase;">System Input Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $tx)
                    <tr style="border-bottom: 1px solid var(--line);">
                        <td style="padding: 14px; font-weight: 700; color: var(--muted); font-size: 13px;">
                            {{ $transactions->firstItem() + $index }}
                        </td>
                        <td style="padding: 14px; font-weight: 700; color: var(--ink);">
                            {{ $tx->contributor_name }}
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
                            @if($tx->proof)
                                <button
                                    type="button"
                                    class="button button-ghost button-sm btn-view-proof"
                                    data-url="{{ $tx->proof->getUrl() }}"
                                    data-title="Proof: {{ $tx->contributor_name }}"
                                    style="padding: 4px 8px; font-size: 12px; border-radius: 8px;"
                                    title="View Proof Image"
                                >
                                    🖼️ View
                                </button>
                            @else
                                <span style="color: var(--muted); font-size: 12px;">No proof</span>
                            @endif
                        </td>
                        <td style="padding: 14px; font-size: 13px; color: var(--muted);">
                            {{ $tx->created_at ? $tx->created_at->format('M d, Y H:i:s') : $tx->transaction_date->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 36px; text-align: center; color: var(--muted);">
                            No transactions recorded matching the selected criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid var(--ink); background: var(--paper);">
                    <td colspan="3" style="padding: 16px; font-weight: 800; font-family: 'Manrope', sans-serif; font-size: 16px; color: var(--ink);">
                        Total Cash Inflow:
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Member shortcut auto-fill logic
    const memberSelect = document.getElementById('cash-member-select');
    const accountInput = document.getElementById('cash-account-type');
    const amountInput = document.getElementById('cash-amount');

    if (memberSelect) {
        memberSelect.addEventListener('change', () => {
            const selected = memberSelect.options[memberSelect.selectedIndex];
            if (selected) {
                const account = selected.getAttribute('data-account');
                const amount = selected.getAttribute('data-amount');

                if (account) {
                    accountInput.value = account;
                }
                if (amount) {
                    amountInput.value = amount;
                }
            }
        });
    }

    // Proof preview modal
    const proofModal = document.getElementById('modal-proof-preview');
    const proofImg = document.getElementById('modal-proof-img');
    const proofTitle = document.getElementById('modal-proof-title');
    const closeProofBtn = document.getElementById('btn-close-proof');

    if (closeProofBtn && proofModal) {
        closeProofBtn.addEventListener('click', () => {
            proofModal.style.display = 'none';
        });
    }

    document.querySelectorAll('.btn-view-proof').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.dataset.url;
            const title = btn.dataset.title;

            proofImg.src = url;
            proofTitle.textContent = title;
            proofModal.style.display = 'flex';
        });
    });
});
</script>
@endsection

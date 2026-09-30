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
    <div class="admin-card" style="background: linear-gradient(135deg, var(--forest) 0%, var(--forest-dark) 100%); color: #fff; margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <span style="color: var(--lime); font-size: 11px; text-transform: uppercase; letter-spacing: .12em; font-weight: 800; display: block; margin-bottom: 4px;">Treasury Balance</span>
                <div style="font: 800 clamp(32px, 3.5vw, 44px) 'Manrope', sans-serif; letter-spacing: -.03em; color: #fff;">
                    Rp {{ number_format($totalCash, 0, ',', '.') }}
                </div>
                <small style="color: #b8c5bf; font-size: 13.5px;">Accurate total across all recorded community transactions</small>
            </div>
            <a href="{{ route('cash-management') }}" class="button button-ghost button-sm" style="color: #fff; border-color: rgba(255,255,255,0.4);" target="_blank">
                Open Legacy Portal &rarr;
            </a>
        </div>
    </div>

    {{-- Member Quick Shortcuts --}}
    @if($members->isNotEmpty())
        <div class="admin-card" style="padding: 20px 24px; margin-bottom: 24px;">
            <div style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                <strong style="font-size: 13px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted);">Member Quick-Fill Shortcuts</strong>
                <small style="color: var(--muted); font-size: 12px;">Pre-populates contributor & last account details</small>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @foreach($members->take(12) as $m)
                    <a href="{{ route('admin.cash.shortcut', $m->id) }}" class="button button-ghost button-sm" style="font-size: 12px; padding: 4px 10px; border-radius: 99px;">
                        + {{ $m->full_name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Transaction Ledger Table --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Recent Transactions Ledger</h2>
                <small style="color: var(--muted); font-size: 13px;">Showing page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</small>
            </div>
        </div>

        @if($transactions->isEmpty())
            <div style="text-align: center; padding: 48px 24px; color: var(--muted);">
                <div style="font-size: 36px; margin-bottom: 8px;">💵</div>
                <h3>No transactions found</h3>
                <p>Transactions will appear here as members contribute.</p>
            </div>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Contributor</th>
                            <th>Account / Type</th>
                            <th>Amount</th>
                            <th>Proof</th>
                            <th style="text-align: right;">Recorder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr>
                                <td>
                                    <span style="color: var(--muted); font-size: 13px;">
                                        {{ $tx->created_at->format('M j, Y H:i') }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--ink); font-size: 14.5px;">
                                        {{ $tx->contributor_name }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="role-badge" style="background: var(--cream); color: var(--forest);">
                                        {{ $tx->account_type }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--forest); font-size: 14.5px; font-family: 'Manrope', sans-serif;">
                                        Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                    </strong>
                                </td>
                                <td>
                                    @if($tx->proof_file_url)
                                        <a href="{{ $tx->proof_file_url }}" target="_blank" class="button button-ghost button-sm" style="font-size: 11px; padding: 2px 8px; height: 26px;">
                                            📷 View Proof
                                        </a>
                                    @else
                                        <span style="color: var(--muted); font-size: 12px;">No proof</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <small style="color: var(--muted); font-size: 12px;">
                                        {{ $tx->user->username ?? 'System' }}
                                    </small>
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

    {{-- Record Transaction Form Section --}}
    <div id="record-tx-section" class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Record New Contribution</h2>
                <small style="color: var(--muted); font-size: 13px;">File an official cash transfer or cash deposit into fellowship records.</small>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.cash.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label" for="member_id">Associated Member (Optional)</label>
                    <select name="member_id" id="member_id" class="form-input">
                        <option value="">-- Non-Member / Fellowship Guest --</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="contributor_name">Contributor Name</label>
                    <input type="text" id="contributor_name" name="contributor_name" class="form-input" required placeholder="Contributor Name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="account_type">Account Type / Method</label>
                    <input type="text" id="account_type" name="account_type" class="form-input" required placeholder="e.g. BCA, Mandiri, Cash">
                </div>

                <div class="form-group">
                    <label class="form-label" for="amount">Amount (IDR)</label>
                    <input type="number" id="amount" name="amount" class="form-input" required min="1000" step="1000" placeholder="e.g. 50000">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" for="proof">Proof of Transfer / Receipt (Optional)</label>
                <input type="file" id="proof" name="proof" class="form-input" accept="image/*">
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="button button-primary button-sm" style="min-width: 160px;">
                    Submit Transaction
                </button>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Cash Management — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Back Navigation --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>
    </nav>

    {{-- Page Header --}}
    <header class="page-header">
        <span class="eyebrow">Financial Stewardship</span>
        <h1>Cash Management</h1>
        <p>
            Transparent financial accounting, fellowship fund tracking, and treasury records for Bethany Youth Community.
        </p>
    </header>

    {{-- Structural Foundation Card --}}
    <div class="placeholder-card">
        <span class="placeholder-badge">
            <x-icon name="info" /> Structural Foundation &bull; Scope 1
        </span>
        <h2>Treasury Portal Architecture</h2>
        <p>
            The Cash Management module is currently in its structural foundation stage. In upcoming phases, this section will provide secure treasury recording, cash inflow and outflow tracking, financial reports, and accountability ledgers for youth ministry events and operations.
        </p>

        <div class="feature-grid-3">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="cash" />
                </div>
                <h3>Treasury Balance</h3>
                <p>Real-time cash balance summary, weekly fellowship offerings, and departmental operational budget tracking.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="edit" />
                </div>
                <h3>Transaction Ledger</h3>
                <p>Detailed chronological ledger of income and approved expenses with receipts and categorization.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="book" />
                </div>
                <h3>Financial Statements</h3>
                <p>Monthly financial transparency statements published for church leadership and community stewards.</p>
            </div>
        </div>
    </div>
</div>
@endsection

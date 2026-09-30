@extends('layouts.admin')

@section('title', 'Birthday Wishes Administration — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Celebration Moderation</span>
        <h1>Birthday Wishes Administration</h1>
        <p>
            God Mode archive access across all members, all years, confidential sender records, and letter audit privileges.
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="{{ route('birthday.wishes') }}" class="button button-ghost button-sm" target="_blank">
            🎁 View Public Wishes &rarr;
        </a>
    </div>
</div>
@endsection

@section('content')
    {{-- Filtering Controls --}}
    <div class="admin-card" style="padding: 20px 24px; margin-bottom: 24px;">
        <form method="GET" action="{{ route('admin.birthday-wishes') }}" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Filter by Member</label>
                <select name="member_id" class="form-input" style="height: 38px; font-size: 13px;" onchange="this.form.submit()">
                    <option value="">All Birthday Celebrants</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ $selectedMemberId == $m->id ? 'selected' : '' }}>
                            {{ $m->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="width: 140px;">
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Birthday Year</label>
                <select name="year" class="form-input" style="height: 38px; font-size: 13px;" onchange="this.form.submit()">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <a href="{{ route('admin.birthday-wishes') }}" class="button button-ghost button-sm" style="height: 38px;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Letters Ledger Table --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Letters Archive ({{ $selectedYear }})</h2>
                <small style="color: var(--muted); font-size: 13px;">Total: {{ $letters->total() }} letters found</small>
            </div>
            <span class="role-badge" style="background: var(--cream); color: var(--forest);">
                {{ $totalLetters }} Total Historical Letters
            </span>
        </div>

        @if($letters->isEmpty())
            <div style="text-align: center; padding: 48px 24px; color: var(--muted);">
                <div style="font-size: 36px; margin-bottom: 8px;">💌</div>
                <h3>No letters found for this filter</h3>
                <p>Select another member or year to review historical birthday wishes.</p>
            </div>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Sender Identity</th>
                            <th>Year</th>
                            <th>Letter Message</th>
                            <th>Sent At</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($letters as $letter)
                            <tr>
                                <td>
                                    <strong style="color: var(--ink); font-size: 14px;">{{ $letter->recipient->full_name ?? 'Unknown' }}</strong>
                                </td>
                                <td>
                                    <span style="font-size: 13.5px; color: var(--forest);">
                                        {{ $letter->display_name }}
                                    </span>
                                    @if($letter->is_anonymous)
                                        <small style="color: var(--muted); display: block; font-size: 11px;">(Confidential to public)</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="role-badge" style="background: var(--paper); color: var(--ink);">{{ $letter->birthday_year }}</span>
                                </td>
                                <td>
                                    <span style="color: var(--muted); font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 320px;">
                                        {{ $letter->message }}
                                    </span>
                                </td>
                                <td>
                                    <span style="color: var(--muted); font-size: 12.5px;">{{ $letter->created_at->format('M j, Y') }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="admin-action-group" style="justify-content: flex-end;">
                                        <a href="{{ route('birthday.letter.show', $letter->id) }}" class="button button-ghost button-sm" target="_blank" style="font-size: 12px; padding: 4px 10px; height: 32px;">
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($letters->hasPages())
                <div style="margin-top: 20px;">
                    {{ $letters->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection

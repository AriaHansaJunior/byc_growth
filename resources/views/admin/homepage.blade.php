@extends('layouts.admin')

@section('title', 'Homepage Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Homepage Architecture</span>
        <h1>Homepage Data & Showcase</h1>
        <p>
            Manage the hero slideshow, community highlights, and editable homepage showcase content.
        </p>
    </div>
    <div class="admin-header-actions">
        <button type="button" class="button button-primary button-sm" id="btn-add-slide">
            <x-icon name="plus" /> Add Slide
        </button>
        <button type="button" class="button button-secondary button-sm" id="btn-edit-slideshow">
            <x-icon name="sparkles" /> ✎ Edit Slideshow
        </button>
    </div>
</div>
@endsection

@section('content')
    {{-- Hero Slideshow Management Card --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Hero Carousel Slideshow</h2>
                <small style="color: var(--muted); font-size: 13px;">Manage rotating slides, captions, and display order.</small>
            </div>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white); margin: 0;">3 Slides Active</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            @foreach($slides as $slide)
                <div class="admin-card" style="padding: 16px; margin: 0; background: var(--paper); border: 1.5px solid var(--line);">
                    <div style="position: relative; height: 160px; border-radius: 12px; overflow: hidden; background: #e2e8e4; margin-bottom: 12px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 36px;">🖼️</span>
                        <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.6); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            Slide #{{ $slide['order'] }}
                        </span>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 6px; color: var(--ink);">{{ $slide['title'] }}</h3>
                    <p style="font-size: 13px; color: var(--muted); margin: 0 0 14px; line-height: 1.4;">{{ $slide['caption'] }}</p>
                    <div class="admin-action-group" style="justify-content: flex-end; width: 100%;">
                        <button type="button" class="button button-secondary button-sm" style="font-size: 12px; padding: 4px 10px; height: 32px;">
                            ✎ Edit
                        </button>
                        <button type="button" class="button button-danger button-sm" data-admin-confirm="Are you sure you want to remove slide #{{ $slide['order'] }}?" style="font-size: 12px; padding: 4px 10px; height: 32px;">
                            Delete
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Editable Content Modules --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Welcome Announcement</h3>
                <button type="button" class="button button-secondary button-sm">✎ Edit</button>
            </div>
            <p style="color: var(--muted); font-size: 14px; line-height: 1.5; margin: 0 0 12px;">
                "Welcome to BYC Growth — A place where young people grow deeper in faith and friendship."
            </p>
            <span style="font-size: 12px; color: var(--forest); font-weight: 700;">Status: Active on Homepage</span>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Quick Highlights Hub</h3>
                <button type="button" class="button button-secondary button-sm">Manage</button>
            </div>
            <p style="color: var(--muted); font-size: 14px; line-height: 1.5; margin: 0 0 12px;">
                Direct shortcuts to Community Fellowship, Interactive Games, and Birthday Celebrations.
            </p>
            <span style="font-size: 12px; color: var(--forest); font-weight: 700;">Status: Synced with Navigation</span>
        </div>
    </div>
@endsection

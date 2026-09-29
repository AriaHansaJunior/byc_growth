@extends('layouts.app')

@section('title', 'Contact Us — BYC GROWTH')

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
        <span class="eyebrow">Get in Touch</span>
        <h1>Contact BYC Growth</h1>
        <p>
            Have questions about our youth fellowship, weekly gatherings, or want to get connected? Reach out to our team below.
        </p>
    </header>

    {{-- Content Layout --}}
    <div class="placeholder-card">
        <span class="placeholder-badge">
            <x-icon name="phone" /> Connect With Us
        </span>
        <h2>We’d Love to Hear From You</h2>
        <p>
            Our leadership team and fellowship coordinators are ready to welcome you. Drop us an email, message us via WhatsApp, or join us in person at our weekend service.
        </p>

        <div class="feature-grid-3">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="phone" />
                </div>
                <h3>Contact Person</h3>
                <p>
                    <strong>Youth Coordinator:</strong><br>
                    <a href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer" style="color: var(--forest); font-weight: 700; text-decoration: none;">
                        +62 812-3456-7890
                    </a><br>
                    <a href="mailto:fellowship@bycgrowth.org" style="color: var(--muted); text-decoration: none; font-size: 13px;">
                        fellowship@bycgrowth.org
                    </a>
                </p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="home" />
                </div>
                <h3>Our Church Location</h3>
                <p>
                    <strong>Part of Successful Bethany Families</strong><br>
                    Gunung Anyar, Surabaya<br>
                    East Java, Indonesia
                </p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="sparkles" />
                </div>
                <h3>Weekly Gatherings</h3>
                <p>
                    <strong>Youth Service & Fellowship</strong><br>
                    Every Saturday &bull; 17:00 WIB<br>
                    All young people are welcome!
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

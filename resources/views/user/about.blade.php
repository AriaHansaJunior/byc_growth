@extends('layouts.app')

@section('title', 'About & Contact — BYC GROWTH')

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
        <span class="eyebrow">Who We Are & Connect</span>
        <h1>About BYC Growth</h1>
        <p>
            Bethany Youth Community (BYC) Growth is a vibrant fellowship of young believers committed to walking together in faith, building lasting friendships, and discovering God’s purpose for our lives.
        </p>
    </header>

    {{-- Main About Section --}}
    <div class="placeholder-card" style="margin-bottom: 32px;">
        <span class="placeholder-badge">
            <x-icon name="sparkles" /> Our Identity & Heritage
        </span>
        <h2>Rooted in Christ, Growing in Fellowship</h2>
        <p>
            As part of <strong>Successful Bethany Families</strong> located in <strong>Gunung Anyar, Surabaya</strong>, BYC Growth exists to equip the next generation with spiritual conviction, leadership skills, and genuine brotherly love. We believe that true growth happens when we step forward by faith and encourage one another every day.
        </p>

        <div class="feature-grid-3">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="sparkles" />
                </div>
                <h3>Spiritual Maturity</h3>
                <p>Grounding our lives on Biblical truth, prayer, and authentic worship that transforms our daily character.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="users" />
                </div>
                <h3>Authentic Fellowship</h3>
                <p>Creating a warm, supportive space where young people form meaningful relationships and do life together.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="trophy" />
                </div>
                <h3>Impactful Leadership</h3>
                <p>Nurturing young leaders to serve their church, families, campuses, and workplaces with integrity.</p>
            </div>
        </div>
    </div>

    {{-- Integrated Contact & Location Section --}}
    <div class="placeholder-card">
        <span class="placeholder-badge">
            <x-icon name="phone" /> Connect With Us
        </span>
        <h2>Get in Touch with BYC Growth</h2>
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

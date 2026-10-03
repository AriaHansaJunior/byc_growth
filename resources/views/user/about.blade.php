@extends('layouts.app')

@section('title', 'About & Contact — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Page Header --}}
    <header class="page-header">
        <span class="eyebrow">Who We Are & Connect</span>
        <h1>About BYC Growth</h1>
        <p>
            Bethany Youth Community (BYC) Growth is a vibrant fellowship of young believers committed to walking together in faith, building lasting friendships, and discovering God’s purpose for our lives.
        </p>
    </header>

    {{-- Primary Information Showcase: Location & Gathering Priority with Supporting Contact --}}
    <section class="about-hero-showcase" aria-label="Location, Gathering, and Contact Information">
        {{-- Visual Focal Point: Location & Weekly Gathering --}}
        <div class="about-focal-column">
            {{-- Location Block --}}
            <div class="about-location-unit">
                <span class="about-kicker">Location</span>
                <h2 class="about-location-city">Gunung Anyar, Surabaya</h2>
                <p class="about-location-details">
                    Part of Successful Bethany Families &bull; East Java, Indonesia
                </p>
            </div>

            {{-- Weekly Gathering Feature (Standout emphasis) --}}
            <div class="about-gathering-unit">
                <span class="about-kicker">Weekly Gathering</span>
                <div class="about-gathering-time">Every Monday &bull; 19:00 WIB</div>
            </div>
        </div>

        {{-- Supporting Column: Contact Information --}}
        <div class="about-contact-column">
            <span class="about-kicker">Contact Person</span>
            <h3 class="about-contact-role">Fellowship & Youth Coordinator</h3>

            <div class="about-contact-channels">
                <a href="https://wa.me/6283857509420" target="_blank" rel="noopener noreferrer" class="about-contact-card">
                    <span class="about-channel-type">WhatsApp</span>
                    <span class="about-channel-value">+62 838-5750-9420</span>
                </a>

                <a href="mailto:bycgrowthbethany1@gmail.com" class="about-contact-card">
                    <span class="about-channel-type">Email</span>
                    <span class="about-channel-value">bycgrowthbethany1@gmail.com</span>
                </a>
            </div>
        </div>
    </section>

    {{-- Secondary Section: Our Identity & Heritage --}}
    <div class="placeholder-card">
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
</div>
@endsection

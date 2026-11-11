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

                <a href="https://www.instagram.com/bycgrowth?xtok=MXNzemZubG9xZHpkYw==" target="_blank" rel="noopener noreferrer" class="about-contact-card">
                    <span class="about-channel-type">Instagram</span>
                    <span class="about-channel-value">@bycgrowth</span>
                </a>
            </div>
        </div>
    </section>

    {{-- Philosophy Showcase: The Meaning & Superhero Origin of BYC Growth --}}
    <section class="about-philosophy-card" data-reveal aria-label="Philosophy and Origin of BYC Growth">
        <div class="about-philosophy-grid">
            {{-- Character Visual Column --}}
            <div class="about-philosophy-visual">
                <div class="about-philosophy-figure-frame">
                    <div class="about-philosophy-aura" aria-hidden="true"></div>
                    <img
                        src="{{ asset('assets/images/groot-philosophy.png') }}"
                        alt="Groot — The Superhero Inspiration for BYC Growth"
                        class="about-philosophy-groot-img"
                        loading="lazy"
                    >
                </div>
                <div class="about-philosophy-tag">
                    <span class="about-philosophy-tag-quote">"We Are Groot"</span>
                    <span class="about-philosophy-tag-sub">Marvel's GOTG</span>
                </div>
            </div>

            {{-- Narrative & Spiritual Philosophy Column --}}
            <div class="about-philosophy-content">
                <span class="about-kicker">The Meaning &amp; Philosophy</span>
                <h2 class="about-philosophy-title">The Story Behind "Growth"</h2>
                <div class="about-philosophy-subtitle">
                    Inspired by Marvel’s Guardians of the Galaxy — A Journey of Sacrifice, Rebirth, and Rooted Strength in Christ.
                </div>

                <div class="about-philosophy-story">
                    <p>
                        The name <strong>Growth</strong> was born from a meaningful inspiration: <strong>Groot</strong>, the iconic tree-like hero from Marvel’s <em>Guardians of the Galaxy</em>. In a fateful battle to protect his companions from total destruction, Groot selflessly sacrificed his own body—wrapping his branches around his friends so they would survive. His adult form was shattered to pieces, but his journey did not end there.
                    </p>
                    <p>
                        From a single living twig placed in soil, he was reborn as a tender sapling—<strong>Baby Groot</strong>. With time, patient care, and perseverance, that tiny sprout took deep root and grew once again into a towering, mighty tree, standing just as strong as before.
                    </p>
                </div>

                <div class="about-philosophy-faith-bridge">
                    <div class="about-faith-quote-bar">
                        <span class="about-faith-icon">🌱</span>
                        <p>
                            Just like Groot, <strong>BYC Growth</strong> envisions every young believer flourishing through life's seasons: though we may face brokenness, trials, or fresh beginnings, we are called to <strong>grow and be deeply rooted in Jesus Christ</strong>. In our daily walk, our fellowship strives to reflect Christ’s love and become a lasting blessing to the world around us.
                        </p>
                    </div>
                </div>

                <div class="about-philosophy-pillars">
                    <div class="about-pillar-item">
                        <div class="about-pillar-number">01</div>
                        <div class="about-pillar-body">
                            <h4>Reborn by Grace</h4>
                            <p>No brokenness is final. God continually breathes new life into our roots, turning our trials into fresh beginnings.</p>
                        </div>
                    </div>

                    <div class="about-pillar-item">
                        <div class="about-pillar-number">02</div>
                        <div class="about-pillar-body">
                            <h4>Deeply Rooted in Christ</h4>
                            <p>True spiritual maturity takes time. Anchored in God's Word and authentic prayer, our faith stands firm against any storm.</p>
                        </div>
                    </div>

                    <div class="about-pillar-item">
                        <div class="about-pillar-number">03</div>
                        <div class="about-pillar-body">
                            <h4>A Blessing to Others</h4>
                            <p>Like a flourishing tree offering shade, our lives are called to mirror Christlike character and bring hope to our community.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

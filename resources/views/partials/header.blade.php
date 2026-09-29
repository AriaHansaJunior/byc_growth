<header class="global-header">
    <div class="header-container">
        <a href="{{ route('home') }}" class="header-brand-link" aria-label="BYC Growth Home">
            <x-brand :compact="true" />
        </a>

        <nav class="header-nav" aria-label="Main Navigation">
            <a href="{{ route('about') }}" class="nav-item {{ request()->routeIs('about') ? 'active' : '' }}">
                About
            </a>
            <a href="{{ route('members') }}" class="nav-item {{ request()->routeIs('members') ? 'active' : '' }}">
                Members
            </a>
            <a href="{{ route('game.center') }}" class="nav-item {{ request()->routeIs('game.*') ? 'active' : '' }}">
                Game Center
            </a>
            <a href="{{ route('contact') }}" class="nav-item {{ request()->routeIs('contact') ? 'active' : '' }}">
                Contact Us
            </a>
        </nav>
    </div>
</header>

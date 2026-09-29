<header class="global-header">
    <div class="header-container">
        <a href="{{ route('home') }}" class="header-brand-link" aria-label="BYC Growth Home">
            <x-brand :compact="true" />
        </a>

        <nav class="header-nav" aria-label="Main Navigation">
            <a href="{{ route('about') }}" class="nav-item {{ request()->routeIs('about') ? 'active' : '' }}">
                About
            </a>
            <a href="{{ route('activity') }}" class="nav-item {{ request()->routeIs('activity*') ? 'active' : '' }}">
                Activity
            </a>
            <a href="{{ route('members') }}" class="nav-item {{ request()->routeIs('members*') ? 'active' : '' }}">
                Members
            </a>
            <a href="{{ route('game.center') }}" class="nav-item {{ request()->routeIs('game.*') ? 'active' : '' }}">
                Game Center
            </a>

            @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('admin.roles') }}" class="nav-item {{ request()->routeIs('admin.roles*') ? 'active' : '' }}">
                    Role
                </a>
                <a href="{{ route('cash-management') }}" class="nav-item {{ request()->routeIs('cash-management*') ? 'active' : '' }}">
                    Cash Management
                </a>
                <a href="{{ route('admin.dashboard') }}" class="nav-badge-link" title="Administrator Dashboard">
                    Admin Portal
                </a>
            @endif
        </nav>
    </div>
</header>

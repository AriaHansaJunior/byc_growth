<header class="global-header">
    <div class="header-container">
        <a href="{{ (auth()->check() && auth()->user()->isAdmin()) ? route('admin.dashboard') : route('home') }}" class="header-brand-link" aria-label="BYC Growth Home">
            <x-brand :compact="true" />
            <span class="header-brand-divider" aria-hidden="true"></span>
            <span class="header-brand-tagline">Bethany Youth Community</span>
        </a>

        {{-- Desktop Navigation --}}
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

            @if(auth()->check() && auth()->user()->member && auth()->user()->member->isBirthdayToday())
                <a href="{{ route('birthday.wishes') }}" class="nav-item {{ request()->routeIs('birthday.wishes*') ? 'active' : '' }}" style="color: var(--gold); font-weight: 700;">
                    🎂 My Wishes
                </a>
            @endif

            @if(auth()->guest())
                <a href="{{ route('login') }}" class="nav-login-btn" id="btn-header-login" title="Member Sign In">
                    Login
                </a>
            @else
                <div class="nav-user-dropdown-wrap" id="nav-user-dropdown-wrap">
                    <button type="button" class="nav-user-btn" id="btn-user-dropdown" aria-haspopup="true" aria-expanded="false" title="Account Menu">
                        <span class="nav-user-username">{{ auth()->user()->username }}</span>
                        <x-icon name="chevron-down" />
                    </button>
                    <div class="nav-user-dropdown-menu" id="nav-user-dropdown-menu" role="menu" style="display: none;">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="nav-user-logout-item" style="text-decoration: none; border-bottom: 1px solid var(--line); display: block;" role="menuitem">
                                Admin Dashboard
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" style="margin: 0; width: 100%;">
                            @csrf
                            <button type="submit" class="nav-user-logout-item" id="btn-header-logout" role="menuitem">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </nav>

        {{-- Mobile Hamburger Button --}}
        <button type="button" class="mobile-nav-toggle" id="btn-mobile-nav" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobile-nav-drawer">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
        </button>
    </div>

    {{-- Mobile Navigation Drawer & Backdrop --}}
    <div class="mobile-nav-backdrop" id="mobile-nav-backdrop" aria-hidden="true"></div>
    <div class="mobile-nav-drawer" id="mobile-nav-drawer" role="dialog" aria-modal="true" aria-label="Mobile Navigation">
        <div class="mobile-nav-header">
            <x-brand :compact="true" />
            <button type="button" class="mobile-nav-close" id="btn-mobile-nav-close" aria-label="Close menu">&times;</button>
        </div>
        <nav class="mobile-nav-links" aria-label="Mobile Navigation Links">
            <a href="{{ route('home') }}" class="mobile-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                Home
            </a>
            <a href="{{ route('about') }}" class="mobile-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">
                About
            </a>
            <a href="{{ route('activity') }}" class="mobile-nav-link {{ request()->routeIs('activity*') ? 'active' : '' }}">
                Activity
            </a>
            <a href="{{ route('members') }}" class="mobile-nav-link {{ request()->routeIs('members*') ? 'active' : '' }}">
                Members
            </a>
            <a href="{{ route('game.center') }}" class="mobile-nav-link {{ request()->routeIs('game.*') ? 'active' : '' }}">
                Game Center
            </a>

            @if(auth()->check() && auth()->user()->member && auth()->user()->member->isBirthdayToday())
                <a href="{{ route('birthday.wishes') }}" class="mobile-nav-link {{ request()->routeIs('birthday.wishes*') ? 'active' : '' }}" style="color: var(--gold); font-weight: 700;">
                    🎂 My Wishes
                </a>
            @endif
        </nav>

        <div class="mobile-nav-footer">
            @if(auth()->guest())
                <a href="{{ route('login') }}" class="button button-primary mobile-nav-login-btn">
                    Member Sign In
                </a>
            @else
                <div class="mobile-nav-user-card">
                    <div class="mobile-nav-user-info">
                        <span class="mobile-nav-user-label">Signed in as</span>
                        <strong class="mobile-nav-username">{{ auth()->user()->username }}</strong>
                    </div>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="button button-secondary button-sm" style="margin-top: 8px; width: 100%; text-align: center;">
                            Admin Dashboard
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px; width: 100%;">
                        @csrf
                        <button type="submit" class="button button-danger button-sm" style="width: 100%;">
                            Logout
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</header>

<header class="global-header">
    <div class="header-container">
        <a href="{{ (auth()->check() && auth()->user()->isAdmin()) ? route('admin.dashboard') : route('home') }}" class="header-brand-link" aria-label="BYC Growth Home">
            <x-brand :compact="true" />
            <span class="header-brand-divider" aria-hidden="true"></span>
            <span class="header-brand-tagline">Bethany Youth Community</span>
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
    </div>
</header>

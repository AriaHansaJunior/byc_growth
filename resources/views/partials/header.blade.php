@php
    $currentUser = auth('web')->user() ?? auth('admin')->user();
@endphp
<header class="global-header">
    <div class="header-container">
        <a href="{{ route('home') }}" class="header-brand-link" aria-label="BYC Growth Home">
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

            @if(!$currentUser)
                <a href="{{ route('login') }}" class="nav-login-btn" id="btn-header-login" title="Member Sign In">
                    Login
                </a>
            @else
                <div class="nav-user-dropdown-wrap" id="nav-user-dropdown-wrap">
                    <button type="button" class="nav-user-btn" id="btn-user-dropdown" aria-haspopup="true" aria-expanded="false" title="Account Menu">
                        <span class="nav-user-username">{{ $currentUser->username }}</span>
                        <x-icon name="chevron-down" />
                    </button>
                    <div class="nav-user-dropdown-menu" id="nav-user-dropdown-menu" role="menu" style="display: none; min-width: 180px;">
                        @if($currentUser->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="nav-user-dropdown-item" role="menuitem">
                                Admin Dashboard
                            </a>
                            <div style="height: 1px; background: var(--line); margin: 4px 6px;"></div>
                        @else
                            <a href="{{ $currentUser->member ? route('birthday.wishes.received') : route('birthday.wishes.sent') }}" class="nav-user-dropdown-item {{ request()->routeIs('birthday.*') ? 'active' : '' }}" role="menuitem">
                                My Birthday Wishes
                            </a>
                            <a href="{{ route('profile') }}" class="nav-user-dropdown-item {{ request()->routeIs('profile') ? 'active' : '' }}" role="menuitem">
                                Profile
                            </a>
                            <div style="height: 1px; background: var(--line); margin: 4px 6px;"></div>
                        @endif
                        <form method="POST" action="{{ $currentUser->isAdmin() ? route('admin.logout') : route('logout') }}" id="form-header-logout" class="js-logout-form" style="margin: 0; width: 100%;">
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
</header>

{{-- Mobile Navigation Drawer & Backdrop (Independent Viewport Overlay) --}}
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
        </nav>

        <div class="mobile-nav-footer">
            @if(!$currentUser)
                <a href="{{ route('login') }}" class="button button-primary mobile-nav-login-btn">
                    Member Sign In
                </a>
            @else
                <div class="mobile-nav-user-card">
                    <div class="mobile-nav-user-info">
                        <span class="mobile-nav-user-label">Signed in as</span>
                        <strong class="mobile-nav-username">{{ $currentUser->username }} @if($currentUser->isAdmin()) (Admin) @endif</strong>
                    </div>
                    @if($currentUser->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="button button-primary button-sm" style="width: 100%; margin-top: 10px; margin-bottom: 6px; text-decoration: none; text-align: center; justify-content: center; font-weight: 700;">
                            Admin Dashboard
                        </a>
                    @else
                        <a href="{{ $currentUser->member ? route('birthday.wishes.received') : route('birthday.wishes.sent') }}" class="button button-ghost button-sm" style="width: 100%; margin-top: 10px; margin-bottom: 6px; text-decoration: none; text-align: center; justify-content: center; font-weight: 700;">
                            My Birthday Wishes
                        </a>
                        <a href="{{ route('profile') }}" class="button button-secondary button-sm" style="width: 100%; margin-top: 6px; margin-bottom: 6px; text-decoration: none; text-align: center; justify-content: center; font-weight: 700;">
                            Profile
                        </a>
                    @endif
                    <form method="POST" action="{{ $currentUser->isAdmin() ? route('admin.logout') : route('logout') }}" id="form-mobile-logout" class="js-logout-form" style="margin-top: 8px; width: 100%;">
                        @csrf
                        <button type="submit" class="button button-danger button-sm" style="width: 100%;">
                            Logout
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

@if($currentUser)
{{-- Logout Confirmation Modal --}}
<div class="game-modal-overlay" id="modal-confirm-logout" role="dialog" aria-modal="true" aria-labelledby="modal-confirm-logout-title" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(32, 48, 41, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="game-modal" style="max-width: 420px; width: 92%; background: var(--white); border: 2px solid var(--forest); border-radius: 22px; padding: 28px 24px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
        <h3 id="modal-confirm-logout-title" style="font-family: 'Manrope', sans-serif; font-size: 19px; font-weight: 800; color: var(--ink); margin: 0 0 10px;">
            Confirm Logout
        </h3>
        <p style="color: var(--muted); font-size: 14px; line-height: 1.5; margin: 0 0 24px;">
            Are you sure you want to log out?
        </p>
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <button type="button" id="btn-cancel-logout" class="button button-secondary" style="padding: 10px 20px;">
                Cancel
            </button>
            <button type="button" id="btn-confirm-logout" class="button button-danger" style="padding: 10px 24px;">
                Log Out
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const logoutModal = document.getElementById('modal-confirm-logout');
    const cancelLogoutBtn = document.getElementById('btn-cancel-logout');
    const confirmLogoutBtn = document.getElementById('btn-confirm-logout');
    let pendingLogoutForm = null;

    if (!logoutModal) return;

    document.querySelectorAll('.js-logout-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.confirmed === '1') return;
            e.preventDefault();
            pendingLogoutForm = form;
            logoutModal.style.display = 'flex';
        });
    });

    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', () => {
            logoutModal.style.display = 'none';
            pendingLogoutForm = null;
        });
    }

    logoutModal.addEventListener('click', (e) => {
        if (e.target === logoutModal) {
            logoutModal.style.display = 'none';
            pendingLogoutForm = null;
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && logoutModal.style.display !== 'none') {
            logoutModal.style.display = 'none';
            pendingLogoutForm = null;
        }
    });

    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', () => {
            const targetForm = pendingLogoutForm || document.getElementById('form-header-logout');
            if (targetForm) {
                confirmLogoutBtn.disabled = true;
                confirmLogoutBtn.textContent = 'Logging Out...';
                targetForm.dataset.confirmed = '1';
                targetForm.submit();
            }
        });
    }
});
</script>
@endif

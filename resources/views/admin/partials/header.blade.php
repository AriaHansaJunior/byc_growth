@php
    /** @var \App\Models\User|null $admin */
    $admin = Auth::user();
@endphp

<header class="admin-topbar">
    <div class="admin-brand-wrap">
        <a href="{{ route('admin.dashboard') }}" class="brand-link" title="BYC Growth Admin Dashboard">
            <x-brand :compact="true" />
        </a>
        <span class="admin-portal-badge">Admin Portal</span>
    </div>

    <nav class="admin-quick-nav" aria-label="Admin Navigation">
        <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.homepage') }}" class="admin-nav-item {{ request()->routeIs('admin.homepage*') ? 'active' : '' }}">Homepage</a>
        <a href="{{ route('admin.activities') }}" class="admin-nav-item {{ request()->routeIs('admin.activities*') ? 'active' : '' }}">Activities</a>
        <a href="{{ route('admin.members') }}" class="admin-nav-item {{ request()->routeIs('admin.members*') ? 'active' : '' }}">Members</a>
        <a href="{{ route('admin.games') }}" class="admin-nav-item {{ request()->routeIs('admin.games*') ? 'active' : '' }}">Games</a>
        <a href="{{ route('admin.birthday-wishes') }}" class="admin-nav-item {{ request()->routeIs('admin.birthday-wishes*') ? 'active' : '' }}">Birthday Wishes</a>
        <a href="{{ route('admin.cash-management') }}" class="admin-nav-item {{ request()->routeIs('admin.cash-management*') ? 'active' : '' }}">Cash Management</a>
        <a href="{{ route('admin.roles') }}" class="admin-nav-item {{ request()->routeIs('admin.roles*') ? 'active' : '' }}">Roles / Accounts</a>
    </nav>

    <div class="admin-user-nav">
        @if($admin)
            <div class="admin-profile-dropdown-wrap" id="admin-profile-dropdown-wrap">
                <button type="button" 
                        class="admin-profile-btn" 
                        id="btn-admin-profile-dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false" 
                        title="Profile: {{ $admin->username ?? $admin->name }}">
                    <span class="admin-profile-avatar" aria-hidden="true">
                        {{ strtoupper(substr($admin->username ?? $admin->name ?? 'A', 0, 1)) }}
                    </span>
                    <span class="admin-profile-username"><strong>{{ $admin->username ?? $admin->name }}</strong></span>
                    <svg class="admin-profile-chevron" aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <div class="admin-profile-dropdown-menu" id="admin-profile-dropdown-menu" role="menu">
                    <div class="admin-profile-menu-header">
                        <div class="admin-profile-menu-avatar">
                            {{ strtoupper(substr($admin->username ?? $admin->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="admin-profile-menu-details">
                            <span class="admin-profile-menu-username">{{ $admin->username ?? $admin->name }}</span>
                            <span class="admin-profile-menu-email" title="{{ $admin->email }}">{{ $admin->email }}</span>
                            <span class="admin-profile-menu-role">{{ ucfirst($admin->role ?? 'Admin') }}</span>
                        </div>
                    </div>

                    <div class="admin-profile-menu-divider"></div>

                    <div class="admin-profile-menu-actions">
                        <a href="{{ route('admin.roles', ['edit' => $admin->id]) }}" class="admin-profile-logout-btn" style="text-decoration: none; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;" role="menuitem">
                            <svg aria-hidden="true" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            <span>Edit Your Account</span>
                        </a>
                        <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0; width: 100%;">
                            @csrf
                            <button type="submit" class="admin-profile-logout-btn" id="btn-admin-logout" role="menuitem">
                                <svg aria-hidden="true" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</header>

<script>
    (function() {
        // Maintain quick-nav scroll position across page navigation
        var quickNav = document.querySelector('.admin-quick-nav');
        if (quickNav) {
            var savedScroll = sessionStorage.getItem('admin_quick_nav_scroll');
            if (savedScroll !== null) {
                quickNav.scrollLeft = parseInt(savedScroll, 10);
            }
            var activeTab = quickNav.querySelector('.admin-nav-item.active');
            if (activeTab) {
                var navRect = quickNav.getBoundingClientRect();
                var activeRect = activeTab.getBoundingClientRect();
                if (savedScroll === null || activeRect.left < navRect.left || activeRect.right > navRect.right) {
                    activeTab.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'auto' });
                }
            }
            quickNav.addEventListener('scroll', function() {
                sessionStorage.setItem('admin_quick_nav_scroll', String(quickNav.scrollLeft));
            }, { passive: true });
            quickNav.querySelectorAll('.admin-nav-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    sessionStorage.setItem('admin_quick_nav_scroll', String(quickNav.scrollLeft));
                });
            });
        }

        var wrap = document.getElementById('admin-profile-dropdown-wrap');
        var btn = document.getElementById('btn-admin-profile-dropdown');
        if (!btn || !wrap) return;
        btn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = wrap.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', String(isOpen));
        };
        if (!window.__adminDocClickBound) {
            window.__adminDocClickBound = true;
            document.addEventListener('click', function(e) {
                var currentWrap = document.getElementById('admin-profile-dropdown-wrap');
                var currentBtn = document.getElementById('btn-admin-profile-dropdown');
                if (currentWrap && !currentWrap.contains(e.target)) {
                    currentWrap.classList.remove('is-open');
                    if (currentBtn) currentBtn.setAttribute('aria-expanded', 'false');
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    var currentWrap = document.getElementById('admin-profile-dropdown-wrap');
                    var currentBtn = document.getElementById('btn-admin-profile-dropdown');
                    if (currentWrap && currentWrap.classList.contains('is-open')) {
                        currentWrap.classList.remove('is-open');
                        if (currentBtn) {
                            currentBtn.setAttribute('aria-expanded', 'false');
                            currentBtn.focus();
                        }
                    }
                }
            });
        }
    })();
</script>

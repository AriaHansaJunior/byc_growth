@if(session('welcome_user'))
    <div id="byc-welcome-toast" class="byc-welcome-toast" role="status" aria-live="polite">
        <div class="byc-welcome-toast-icon">✨</div>
        <div class="byc-welcome-toast-content">
            <span class="byc-welcome-toast-title">Signed In</span>
            <p class="byc-welcome-toast-msg">Welcome, {{ session('welcome_user') }}!</p>
        </div>
        <button type="button" class="byc-welcome-toast-close" onclick="this.closest('.byc-welcome-toast').remove()" aria-label="Close notification">×</button>
    </div>
@endif

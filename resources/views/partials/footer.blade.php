<footer class="global-footer">
    <div class="footer-container">
        {{-- Footer Top: Join CTA & Details --}}
        <div class="footer-top">
            <div class="footer-cta-card">
                <span class="eyebrow footer-eyebrow">Get Involved</span>
                <h2>Want to join us?</h2>
                <p>
                    Whether you are taking your first steps in faith or looking for a vibrant fellowship to grow with, BYC is here for you. Join our weekly gatherings and experience real community.
                </p>
                <div class="footer-cta-action">
                    <a href="{{ route('about') }}" class="button button-primary">
                        Connect With Us <x-icon name="arrow" />
                    </a>
                </div>
            </div>

            <div class="footer-columns">
                <div class="footer-col">
                    <span class="footer-col-heading">Contact Person</span>
                    <p class="footer-contact-title">Fellowship & Youth Coordinator</p>
                    <ul class="footer-contact-list">
                        <li>
                            <a href="mailto:fellowship@bycgrowth.org" class="footer-link">
                                fellowship@bycgrowth.org
                            </a>
                        </li>
                        <li>
                            <a href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer" class="footer-link">
                                +62 812-3456-7890 (WhatsApp)
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="footer-col">
                    <span class="footer-col-heading">Community & Location</span>
                    <p class="footer-location-text">
                        <strong>Part of Successful Bethany Families</strong><br>
                        Gunung Anyar, Surabaya, East Java<br>
                        Indonesia
                    </p>
                </div>

                <div class="footer-col">
                    <span class="footer-col-heading">Explore</span>
                    <ul class="footer-nav-list">
                        <li><a href="{{ route('home') }}" class="footer-link">Home</a></li>
                        <li><a href="{{ route('about') }}" class="footer-link">About Us</a></li>
                        <li><a href="{{ route('activity') }}" class="footer-link">Activity</a></li>
                        <li><a href="{{ route('members') }}" class="footer-link">Members</a></li>
                        <li><a href="{{ route('game.center') }}" class="footer-link">Game Center</a></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Footer Bottom: Brand & Copyright --}}
        <div class="footer-bottom">
            <div class="footer-brand-lockup">
                <a href="{{ route('home') }}" class="footer-brand-link">
                    <x-brand :compact="true" />
                </a>
                <span class="footer-tagline">Faith &bull; Purpose &bull; Fellowship</span>
            </div>
            <div class="footer-legal">
                <p>&copy; 2026 BYC Growth. All rights reserved.</p>
                <p class="footer-subtext">Part of Successful Bethany Families &bull; Gunung Anyar, Surabaya</p>
            </div>
        </div>
    </div>
</footer>

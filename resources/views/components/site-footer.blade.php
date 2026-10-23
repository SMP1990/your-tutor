@php
    $social = ['Facebook' => 'Fb', 'Instagram' => 'Ig', 'YouTube' => 'Yt', 'WhatsApp' => 'Wa'];
    $payments = ['JazzCash', 'Easypaisa', 'Visa', 'Mastercard', 'Bank transfer'];
@endphp

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__cols">
            <div class="site-footer__about">
                <strong class="site-footer__brand">{{ config('app.name') }}</strong>
                <p>Online tutoring for students across Pakistan and overseas. Verified tutors, free demo classes, pay per session.</p>
                <div class="site-footer__social">
                    @foreach ($social as $name => $abbr)
                        <a href="#" class="social-link" aria-label="{{ $name }}">{{ $abbr }}</a>
                    @endforeach
                </div>
            </div>

            <nav class="footer-col" aria-label="Company">
                <strong class="footer-col__title">Company</strong>
                <a href="#">About Us</a>
                <a href="#">Contact</a>
                <a href="#">FAQs</a>
                <a href="{{ route('become-a-tutor') }}">Become a Tutor</a>
            </nav>

            <nav class="footer-col" aria-label="Legal">
                <strong class="footer-col__title">Legal</strong>
                <a href="#">Terms &amp; Conditions</a>
                <a href="#">Privacy Policy</a>
            </nav>

            <div class="footer-pay">
                <strong class="footer-col__title">We accept</strong>
                <ul class="footer-pay__list">
                    @foreach ($payments as $method)
                        <li>{{ $method }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="site-footer__legal">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <span>Made in Pakistan</span>
        </div>
    </div>
</footer>

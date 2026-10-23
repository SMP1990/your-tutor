@php
    // Opens WhatsApp when a number is configured, otherwise the contact page.
    $number = config('site.whatsapp_number');
    $href = $number ? 'https://wa.me/' . $number : route('contact');
@endphp

<a href="{{ $href }}" class="wa-button" aria-label="Chat with us on WhatsApp" @if ($number) target="_blank" rel="noopener" @endif>
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M9 10c0 3 2 5 5 5l1-1.5-2-1-1 .8c-.8-.4-1.4-1-1.8-1.8l.8-1-1-2z" fill="currentColor" stroke="none"/></svg>
    <span class="wa-button__label">Need help?</span>
</a>

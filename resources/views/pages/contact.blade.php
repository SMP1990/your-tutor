@extends('layouts.app')

@section('title', 'Contact us | ' . config('app.name'))
@section('description', 'Contact ' . config('app.name') . ' on WhatsApp or email, or send us a message. We reply within one working day.')

@php
    $site = config('site');
    $waUrl = $site['whatsapp_number'] ? 'https://wa.me/' . $site['whatsapp_number'] : null;
@endphp

@section('content')

<section class="page-hero">
    <div class="container page-hero__inner">
        <span class="eyebrow">Contact</span>
        <h1>We are here to help</h1>
        <p>Questions about tutors, classes or payments? Message us on WhatsApp or send the form. We reply within one working day.</p>
    </div>
</section>

<div class="container demo">
    <div class="demo__main">
        <h2 class="profile-card__title">Send us a message</h2>

        @if ($errors->any())
            <div class="form-alert" role="alert">Please check the highlighted fields.</div>
        @endif

        <form class="demo-form" method="post" action="{{ route('contact.store') }}" novalidate>
            @csrf
            {{-- Bot trap: hidden from people; a filled value means a bot (ContactController ignores the message). --}}
            <div hidden aria-hidden="true">
                <label for="website">Leave this empty</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <div class="form-grid">
                <div class="field field--wide">
                    <label for="name">Your name</label>
                    <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="name">
                    @error('name') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="phone">WhatsApp number</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" class="input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="0300 1234567" autocomplete="tel">
                    @error('phone') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email">
                    @error('email') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <p class="field--wide field__hint">Give at least a WhatsApp number or an email so we can reply.</p>
                <div class="field field--wide">
                    <label for="topic">Topic</label>
                    <select id="topic" name="topic" class="input @error('topic') is-invalid @enderror" required>
                        <option value="">Choose…</option>
                        @foreach ($topics as $topic)
                            <option @selected(old('topic') === $topic)>{{ $topic }}</option>
                        @endforeach
                    </select>
                    @error('topic') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <div class="field field--wide">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" minlength="10" maxlength="2000" class="input @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                    @error('message') <p class="field__error">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primary demo-form__submit">Send message</button>
        </form>
    </div>

    <aside class="demo__side" aria-label="Other ways to reach us">
        <div class="book-card">
            <ul class="contact-list">
                <li>
                    <span class="info-card__icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
                    </span>
                    <div>
                        <strong>WhatsApp</strong>
                        @if ($waUrl)
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener">{{ $site['whatsapp_display'] }}</a>
                        @else
                            <span>{{ $site['whatsapp_display'] }}</span>
                        @endif
                    </div>
                </li>
                <li>
                    <span class="info-card__icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    </span>
                    <div>
                        <strong>Email</strong>
                        <span>{{ $site['support_email'] }}</span>
                    </div>
                </li>
                <li>
                    <span class="info-card__icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </span>
                    <div>
                        <strong>Support hours</strong>
                        <span>{{ $site['support_hours'] }}</span>
                    </div>
                </li>
            </ul>
            <p class="book-card__free">Looking for answers fast? Check the <a href="{{ route('faqs') }}">FAQs</a>.</p>
        </div>
    </aside>
</div>

@endsection

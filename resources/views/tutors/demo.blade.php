@extends('layouts.app')

@section('title', 'Book a free demo with ' . $tutor['name'] . ' | ' . config('app.name'))
@section('description', 'Book a free online demo class with ' . $tutor['name'] . '. No payment needed.')

@section('content')

<div class="container profile-crumbs">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li><a href="{{ route('tutors.index') }}">Find a Tutor</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('tutors.show', $tutor['slug']) }}">{{ $tutor['name'] }}</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page">Free demo</li>
        </ol>
    </nav>
</div>

<div class="container demo">
    <div class="demo__main">
        <div class="demo__head">
            <h1>Book a free demo class</h1>
            <p>30 minutes online with {{ rtrim($tutor['name'], '.') }}. No payment needed. We confirm the time with you on WhatsApp.</p>
        </div>

        @if ($errors->any())
            <div class="form-alert" role="alert">Please check the highlighted fields.</div>
        @endif

        <form class="demo-form" method="post" action="{{ route('tutors.demo.store', $tutor['slug']) }}" novalidate>
            @csrf

            <fieldset class="form-section">
                <legend>Who is the class for?</legend>
                <div class="pills">
                    @foreach (['child' => 'My child', 'self' => 'Myself'] as $value => $label)
                        <label class="pill">
                            <input type="radio" name="for" value="{{ $value }}" @checked(old('for', 'child') === $value) data-for-toggle>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Your details</legend>
                <div class="form-grid">
                    <div class="field">
                        <label for="name">Your name</label>
                        <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="name">
                        @error('name') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field" data-student-field>
                        <label for="student_name">Student's name</label>
                        <input id="student_name" name="student_name" class="input @error('student_name') is-invalid @enderror" value="{{ old('student_name') }}">
                        @error('student_name') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="phone">WhatsApp number</label>
                        <input id="phone" name="phone" type="tel" inputmode="tel" class="input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="0300 1234567" required autocomplete="tel">
                        @error('phone') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="email">Email <span class="field__optional">(optional)</span></label>
                        <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email">
                        @error('email') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Class details</legend>
                <div class="form-grid">
                    <div class="field">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" class="input @error('subject') is-invalid @enderror" required>
                            @foreach ($tutor['subjects'] as $subject)
                                <option @selected(old('subject') === $subject)>{{ $subject }}</option>
                            @endforeach
                        </select>
                        @error('subject') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="grade">Grade / level</label>
                        <select id="grade" name="grade" class="input @error('grade') is-invalid @enderror" required>
                            <option value="">Choose…</option>
                            @foreach ($grades as $grade)
                                <option @selected(old('grade') === $grade)>{{ $grade }}</option>
                            @endforeach
                        </select>
                        @error('grade') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="day">Preferred day</label>
                        <select id="day" name="day" class="input @error('day') is-invalid @enderror" required>
                            <option value="">Choose…</option>
                            @foreach ($tutor['availability'] as $day => $slots)
                                @if ($slots)
                                    <option value="{{ $day }}" @selected(old('day') === $day)>{{ $day }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('day') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="time">Preferred time</label>
                        <select id="time" name="time" class="input @error('time') is-invalid @enderror" required>
                            <option value="">Choose…</option>
                            @foreach ($times as $value => $label)
                                <option value="{{ $value }}" @selected(old('time') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('time') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field field--wide">
                        <label for="notes">What do you want help with? <span class="field__optional">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="4" maxlength="500" class="input @error('notes') is-invalid @enderror" placeholder="e.g. Chapter 3 numericals, preparing for March exams">{{ old('notes') }}</textarea>
                        @error('notes') <p class="field__error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <div class="field">
                <label class="check">
                    <input type="checkbox" name="contact_ok" value="1" @checked(old('contact_ok')) required>
                    You may contact me on WhatsApp or phone about this demo class.
                </label>
                @error('contact_ok') <p class="field__error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary demo-form__submit">Request free demo</button>
        </form>
    </div>

    <aside class="demo__side" aria-label="Your tutor">
        <div class="book-card">
            <div class="demo-tutor">
                <img src="{{ $tutor['photo_url'] }}" alt="" width="64" height="64">
                <div>
                    <strong>{{ $tutor['name'] }}</strong>
                    <span>{{ implode(' · ', $tutor['subjects']) }}</span>
                    <span>★ {{ $tutor['rating'] }} ({{ $tutor['reviews_count'] }} reviews)</span>
                </div>
            </div>
            <ol class="next-steps">
                <li><strong>You send this request</strong><span>Takes 1 minute.</span></li>
                <li><strong>We confirm on WhatsApp</strong><span>Usually within 24 hours.</span></li>
                <li><strong>Join the free class</strong><span>Online, from phone or laptop.</span></li>
            </ol>
            <p class="book-card__free">Regular classes from <strong>{{ $tutor['rate'] }} / hr</strong>, only if you like the demo.</p>
        </div>
    </aside>
</div>

@endsection


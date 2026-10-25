@extends('layouts.app')

@section('title', 'Book a free demo with ' . $tutor['name'] . ' | ' . config('app.name'))
@section('description', 'Book a free online demo class with ' . $tutor['name'] . '. No payment needed.')

{{-- The booking form is for logged-in families, not for search results (the tutor's profile page is). --}}
@push('styles')
    <meta name="robots" content="noindex">
@endpush

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
            <p>30 minutes online with {{ rtrim($tutor['name'], '.') }}. No payment needed. The tutor replies within {{ \App\Models\DemoRequest::ANSWER_HOURS }} hours.</p>
        </div>

        @guest
            <div class="panel">
                <p>Please log in or create a free parent or student account to book. It takes one minute, and you can follow your demo from your dashboard.</p>
                <div class="form-actions">
                    <a href="{{ route('register', ['role' => 'parent']) }}" class="btn btn-primary">Create free account</a>
                    <a href="{{ route('login') }}" class="btn btn-outline">Log in</a>
                </div>
            </div>
        @elseif (! $canBook)
            <div class="form-note" role="status">Free demos are booked from a parent or student account. You are logged in as a {{ strtolower($account->role->label()) }}.</div>
        @elseif ($account->role === \App\Enums\Role::Parent && $children->isEmpty())
            <div class="panel">
                <p>First add the child this class is for. Then come back to this page.</p>
                <div class="form-actions"><a href="{{ route('children.create') }}" class="btn btn-primary">+ Add child</a></div>
            </div>
        @else
            @if ($errors->has('tutor'))
                <div class="form-alert" role="alert">{{ $errors->first('tutor') }}</div>
            @elseif ($errors->any())
                <div class="form-alert" role="alert">Please check the highlighted fields.</div>
            @endif

            <form class="demo-form" method="post" action="{{ route('tutors.demo.store', $tutor['slug']) }}" novalidate>
                @csrf

                <fieldset class="form-section">
                    <legend>Who is the class for?</legend>
                    <div class="form-grid">
                        @if ($account->role === \App\Enums\Role::Parent)
                            <div class="field">
                                <label for="child_id">Child</label>
                                <select id="child_id" name="child_id" class="input @error('child_id') is-invalid @enderror" required>
                                    @if ($children->count() > 1)<option value="">Choose…</option>@endif
                                    @foreach ($children as $child)
                                        <option value="{{ $child->id }}" @selected((string) old('child_id') === (string) $child->id)>{{ $child->name }}{{ $child->grade ? ' ('.$child->grade.')' : '' }}</option>
                                    @endforeach
                                </select>
                                <p class="field__hint"><a href="{{ route('children.create') }}">Add another child</a></p>
                                @error('child_id') <p class="field__error">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div class="field">
                                <span class="field__legend">Student</span>
                                <p>{{ $account->name }} (you)</p>
                            </div>
                        @endif
                        <div class="field">
                            <label for="phone">WhatsApp number</label>
                            <input id="phone" name="phone" type="tel" inputmode="tel" class="input @error('phone') is-invalid @enderror" value="{{ old('phone', $account->phone) }}" placeholder="0300 1234567" required autocomplete="tel">
                            @error('phone') <p class="field__error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Class details</legend>
                    <div class="form-grid">
                        <div class="field">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject" class="input @error('subject') is-invalid @enderror" required>
                                @foreach ($tutor['subject_slugs'] as $i => $slug)
                                    <option value="{{ $slug }}" @selected(old('subject') === $slug)>{{ $tutor['subjects'][$i] }}</option>
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
        @endguest
    </div>

    <aside class="demo__side" aria-label="Your tutor">
        <div class="book-card">
            <div class="demo-tutor">
                <img src="{{ $tutor['photo_url'] }}" alt="" width="64" height="64">
                <div>
                    <strong>{{ $tutor['name'] }}</strong>
                    <span>{{ implode(' · ', $tutor['subjects']) }}</span>
                    <span>{{ $tutor['reviews_count'] > 0 ? '★ '.$tutor['rating'].' ('.$tutor['reviews_count'].' '.str('review')->plural($tutor['reviews_count']).')' : 'New tutor' }}</span>
                </div>
            </div>
            <ol class="next-steps">
                <li><strong>You send this request</strong><span>Takes 1 minute.</span></li>
                <li><strong>The tutor replies</strong><span>Within {{ \App\Models\DemoRequest::ANSWER_HOURS }} hours, on your dashboard.</span></li>
                <li><strong>Join the free class</strong><span>Online, from phone or laptop.</span></li>
            </ol>
            <p class="book-card__free">Regular classes from <strong>{{ $tutor['rate'] }} / hr</strong>, only if you like the demo.</p>
        </div>
    </aside>
</div>

@endsection


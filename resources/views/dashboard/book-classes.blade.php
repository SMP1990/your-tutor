@extends('layouts.dashboard')

@section('title', 'Book regular classes')

@section('content')
<div class="dash-head">
    <div>
        <h1>Book regular classes</h1>
        <p>With {{ $tutor['name'] }} for {{ $demo->student_name }} · {{ $demo->subject?->name }}. The tutor replies within {{ \App\Models\ClassBooking::ANSWER_HOURS }} hours.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">Back to dashboard</a>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">Please check the highlighted fields.</div>
@endif

<form method="post" action="{{ route('bookings.store', $demo) }}" class="demo-form panel" novalidate>
    @csrf

    <fieldset class="form-section">
        <legend>Days each week <span class="field__optional">(the tutor's free days)</span></legend>
        <div class="pills">
            @foreach ($days as $day)
                <label class="pill"><input type="checkbox" name="days[]" value="{{ $day }}" @checked(in_array($day, old('days', [$demo->preferred_day]), true))><span>{{ $day }}</span></label>
            @endforeach
        </div>
        @error('days') <p class="field__error">{{ $message }}</p> @enderror
        @error('days.*') <p class="field__error">{{ $message }}</p> @enderror
    </fieldset>

    <div class="form-grid">
        <div class="field">
            <label for="start_time">Start time ({{ config('site.timezone_label') }})</label>
            <select id="start_time" name="start_time" class="input @error('start_time') is-invalid @enderror" required>
                <option value="">Choose…</option>
                @foreach ($times as $value => $label)
                    <option value="{{ $value }}" @selected(old('start_time') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('start_time') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="duration">Class length</label>
            <select id="duration" name="duration" class="input @error('duration') is-invalid @enderror" required>
                @foreach (\App\Models\ClassBooking::DURATIONS as $value => $label)
                    <option value="{{ $value }}" @selected((int) old('duration', 60) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('duration') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="starts_on">First week starts on</label>
            <input id="starts_on" name="starts_on" type="date" class="input @error('starts_on') is-invalid @enderror" required
                   min="{{ now(config('site.timezone'))->addDay()->toDateString() }}" max="{{ now(config('site.timezone'))->addDays(30)->toDateString() }}" value="{{ old('starts_on') }}">
            @error('starts_on') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="weeks">For how long</label>
            <select id="weeks" name="weeks" class="input @error('weeks') is-invalid @enderror" required>
                @foreach (\App\Models\ClassBooking::WEEKS as $value => $label)
                    <option value="{{ $value }}" @selected((int) old('weeks', 4) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('weeks') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="grade">Grade / level</label>
            <select id="grade" name="grade" class="input @error('grade') is-invalid @enderror" required>
                @foreach (\App\Http\Requests\BookDemoRequest::GRADES as $grade)
                    <option @selected(old('grade', $demo->grade) === $grade)>{{ $grade }}</option>
                @endforeach
            </select>
            @error('grade') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field field--wide">
            <label for="notes">Anything the tutor should know? <span class="field__optional">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3" maxlength="500" class="input @error('notes') is-invalid @enderror" placeholder="e.g. Board exams in March; focus on numericals.">{{ old('notes') }}</textarea>
            @error('notes') <p class="field__error">{{ $message }}</p> @enderror
        </div>
    </div>

    <p class="muted">Rate: <strong>{{ $tutor['rate'] }} / hour</strong>. Online payment is not switched on yet; we will contact you about paying before classes start.</p>

    <button type="submit" class="btn btn-primary">Send booking request</button>
</form>
@endsection

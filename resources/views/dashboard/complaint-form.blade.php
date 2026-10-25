@extends('layouts.dashboard')

@section('title', 'Report a problem')

@section('content')
<div class="dash-head">
    <div>
        <h1>Report a problem</h1>
        <p>
            @if ($session) Class on {{ $session->label() }} · @endif
            {{ $isTutor ? 'Student: '.$booking->student_name : 'Tutor: '.($booking->tutorProfile?->user?->name ?? '—') }}
            · {{ $booking->subject?->name ?? 'Class' }}. Only our team reads this.
        </p>
    </div>
    <a href="{{ route('complaints.index') }}" class="btn btn-outline btn-sm">My reports</a>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ $action }}" class="demo-form panel" novalidate>
    @csrf
    <div class="field">
        <label for="category">What is it about?</label>
        <select id="category" name="category" class="input @error('category') is-invalid @enderror" required>
            <option value="">Choose one</option>
            @foreach ($categories as $key => $label)
                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="details">What happened?</label>
        <textarea id="details" name="details" rows="5" minlength="{{ \App\Models\Complaint::MIN_DETAILS }}" maxlength="{{ \App\Models\Complaint::MAX_DETAILS }}" required
                  class="input @error('details') is-invalid @enderror" placeholder="Please tell us what happened and when.">{{ old('details') }}</textarea>
        @error('details') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="btn btn-primary">Send report</button>
</form>
@endsection

@extends('layouts.dashboard')

@section('title', 'Tutor application')

@php
    $c = config('tutors');
    $editable = $profile->isEditableByTutor();
    $chosenLanguages = array_filter(array_map('trim', explode(',', (string) $profile->languages)));
    $chosenLevels = array_filter(array_map('trim', explode('·', (string) $profile->levels)));
    $education = old('education', $profile->education ?: []);
    $education = array_pad($education, max(1, count($education)), ['degree' => '', 'institute' => '', 'year' => '']);
    $statusColour = match ($profile->status) {
        \App\Enums\TutorStatus::Approved => 'green',
        \App\Enums\TutorStatus::Rejected => 'red',
        \App\Enums\TutorStatus::Draft => 'grey',
        default => 'amber',
    };
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Tutor application</h1>
        <p>Status: <span class="status status--{{ $statusColour }}">{{ $profile->exists ? $profile->status->label() : 'Not started' }}</span></p>
    </div>
    <a href="{{ route('dashboard.tutor') }}" class="btn btn-outline btn-sm">Back to dashboard</a>
</div>

@if (! $editable)
    <section class="panel">
        <p>Your application has been sent for review. You can change it again if our team asks for more documents.</p>
    </section>
@endif

@if ($editable && $profile->exists && $missing)
    <div class="form-note form-note--warn" role="status">
        <strong>Still needed before you can submit:</strong> {{ implode(', ', $missing) }}.
    </div>
@endif

@if ($errors->any())
    <div class="form-alert" role="alert">Please check the highlighted fields.</div>
@endif

<form method="post" action="{{ route('tutor.application.update') }}" enctype="multipart/form-data" class="application" novalidate>
    @csrf
    @method('PUT')
    <fieldset @disabled(! $editable) class="application__fields">

    {{-- 1. ABOUT --}}
    <section class="panel">
        <div class="panel__head"><h2>1. About you</h2></div>
        <div class="form-grid">
            <div class="field field--wide">
                <label for="headline">Headline</label>
                <input id="headline" name="headline" class="input @error('headline') is-invalid @enderror" maxlength="120" value="{{ old('headline', $profile->headline) }}" placeholder="e.g. MSc Physics tutor for FSc and O Level">
                @error('headline') <p class="field__error">{{ $message }}</p> @enderror
            </div>
            <div class="field field--wide">
                <label for="bio">About you <span class="field__optional">(at least 80 characters)</span></label>
                <textarea id="bio" name="bio" rows="5" maxlength="2000" class="input @error('bio') is-invalid @enderror" placeholder="How you teach, who you help, results you are proud of.">{{ old('bio', $profile->bio) }}</textarea>
                @error('bio') <p class="field__error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="city">City</label>
                <input id="city" name="city" class="input @error('city') is-invalid @enderror" maxlength="60" value="{{ old('city', $profile->city) }}">
                @error('city') <p class="field__error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="experience_years">Years of teaching <span class="field__optional">(optional)</span></label>
                <input id="experience_years" name="experience_years" type="number" min="0" max="60" class="input @error('experience_years') is-invalid @enderror" value="{{ old('experience_years', $profile->experience_years) }}">
                @error('experience_years') <p class="field__error">{{ $message }}</p> @enderror
            </div>
            <fieldset class="field field--wide form-section">
                <legend class="field__legend">Languages you teach in</legend>
                <div class="pills">
                    @foreach ($c['languages'] as $language)
                        <label class="pill"><input type="checkbox" name="languages[]" value="{{ $language }}" @checked(in_array($language, old('languages', $chosenLanguages), true))><span>{{ $language }}</span></label>
                    @endforeach
                </div>
                @error('languages.*') <p class="field__error">{{ $message }}</p> @enderror
            </fieldset>
        </div>
    </section>

    {{-- 2. TEACHING --}}
    <section class="panel">
        <div class="panel__head"><h2>2. What you teach</h2></div>
        <fieldset class="form-section">
            <legend class="field__legend">Subjects <span class="field__optional">(up to {{ $c['max_subjects'] }})</span></legend>
            <div class="pills">
                @foreach ($subjects as $subject)
                    <label class="pill"><input type="checkbox" name="subjects[]" value="{{ $subject->id }}" @checked(in_array($subject->id, array_map('intval', old('subjects', $chosenSubjects)), true))><span>{{ $subject->name }}</span></label>
                @endforeach
            </div>
            @error('subjects') <p class="field__error">{{ $message }}</p> @enderror
        </fieldset>
        <fieldset class="form-section">
            <legend class="field__legend">Levels</legend>
            <div class="pills">
                @foreach ($c['levels'] as $level)
                    <label class="pill"><input type="checkbox" name="levels[]" value="{{ $level }}" @checked(in_array($level, old('levels', $chosenLevels), true))><span>{{ $level }}</span></label>
                @endforeach
            </div>
        </fieldset>
        <div class="form-grid">
            <div class="field">
                <label for="rate">Hourly rate (PKR)</label>
                <input id="rate" name="rate" inputmode="numeric" class="input @error('rate') is-invalid @enderror" value="{{ old('rate', $profile->hourlyRate()) }}" placeholder="e.g. 1800">
                <p class="field__hint">Between PKR {{ number_format($c['rate_min']) }} and {{ number_format($c['rate_max']) }}.</p>
                @error('rate') <p class="field__error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="intro_video_url">Intro video link <span class="field__optional">(optional)</span></label>
                <input id="intro_video_url" name="intro_video_url" type="url" class="input @error('intro_video_url') is-invalid @enderror" value="{{ old('intro_video_url', $profile->intro_video_url) }}" placeholder="https://youtu.be/…">
                @error('intro_video_url') <p class="field__error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- 3. AVAILABILITY --}}
    <section class="panel">
        <div class="panel__head"><h2>3. When you are free</h2></div>
        <p class="profile-card__note">Tick the times you can teach (Pakistan time).</p>
        <div class="avail-scroll">
            <table class="avail avail--pick">
                <thead><tr><th scope="col"><span class="sr-only">Time</span></th>@foreach ($c['days'] as $day)<th scope="col">{{ $day }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($c['time_slots'] as $slot => $label)
                        <tr>
                            <th scope="row">{{ $label }}</th>
                            @foreach ($c['days'] as $day)
                                <td>
                                    <label class="avail__pick">
                                        <input type="checkbox" name="availability[{{ $day }}][]" value="{{ $slot }}" @checked(in_array($slot, old("availability.$day", $profile->availability[$day] ?? []), true))>
                                        <span class="sr-only">{{ $day }} {{ $label }}</span>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- 4. EDUCATION --}}
    <section class="panel">
        <div class="panel__head"><h2>4. Education</h2></div>
        @foreach (array_slice($education, 0, $c['max_education']) as $i => $row)
            <div class="form-grid form-grid--three">
                <div class="field"><label for="edu-{{ $i }}-degree">Degree</label><input id="edu-{{ $i }}-degree" name="education[{{ $i }}][degree]" class="input @error("education.$i.degree") is-invalid @enderror" value="{{ $row['degree'] ?? '' }}" placeholder="e.g. MSc Physics"></div>
                <div class="field"><label for="edu-{{ $i }}-institute">Institute</label><input id="edu-{{ $i }}-institute" name="education[{{ $i }}][institute]" class="input @error("education.$i.institute") is-invalid @enderror" value="{{ $row['institute'] ?? '' }}"></div>
                <div class="field"><label for="edu-{{ $i }}-year">Year</label><input id="edu-{{ $i }}-year" name="education[{{ $i }}][year]" inputmode="numeric" class="input @error("education.$i.year") is-invalid @enderror" value="{{ $row['year'] ?? '' }}"></div>
            </div>
        @endforeach
        @if (count($education) < $c['max_education'])
            @php $i = count($education); @endphp
            <div class="form-grid form-grid--three">
                <div class="field"><label for="edu-{{ $i }}-degree">Another degree <span class="field__optional">(optional)</span></label><input id="edu-{{ $i }}-degree" name="education[{{ $i }}][degree]" class="input"></div>
                <div class="field"><label for="edu-{{ $i }}-institute">Institute</label><input id="edu-{{ $i }}-institute" name="education[{{ $i }}][institute]" class="input"></div>
                <div class="field"><label for="edu-{{ $i }}-year">Year</label><input id="edu-{{ $i }}-year" name="education[{{ $i }}][year]" inputmode="numeric" class="input"></div>
            </div>
        @endif
        @error('education.*') <p class="field__error">{{ $message }}</p> @enderror
        @foreach (['degree', 'institute', 'year'] as $f)
            @foreach ($errors->get("education.*.$f") as $messages)
                <p class="field__error">{{ $messages[0] }}</p>
            @endforeach
        @endforeach
    </section>

    {{-- 5. PHOTO AND DOCUMENTS --}}
    <section class="panel">
        <div class="panel__head"><h2>5. Photo and documents</h2></div>
        <p class="profile-card__note">Documents are private: only our review team can see them. JPG, PNG or PDF, up to {{ intdiv($c['document_max_kb'], 1024) }} MB.</p>
        <div class="form-grid">
            @foreach ([
                'photo' => ['Profile photo (shown on your profile)', 'photo_path', 'image/jpeg,image/png,image/webp'],
                'cnic_front' => ['CNIC, front', 'cnic_front_path', 'image/jpeg,image/png,application/pdf'],
                'cnic_back' => ['CNIC, back', 'cnic_back_path', 'image/jpeg,image/png,application/pdf'],
                'degree' => ['Highest degree or transcript', 'degree_path', 'image/jpeg,image/png,application/pdf'],
            ] as $input => [$label, $column, $accept])
                <div class="field">
                    <label for="{{ $input }}">{{ $label }}</label>
                    <input id="{{ $input }}" name="{{ $input }}" type="file" accept="{{ $accept }}" class="input input--file @error($input) is-invalid @enderror">
                    @if ($profile->{$column})
                        <p class="field__hint upload-ok">✓ Uploaded. Choose a new file only to replace it.</p>
                    @endif
                    @error($input) <p class="field__error">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        @if ($profile->photo_path)
            {{-- asset() follows the current domain, so it keeps working after a domain change --}}
            <img src="{{ asset('storage/'.$profile->photo_path) }}" alt="Your profile photo" width="96" height="96" class="application__photo">
        @endif
    </section>

    </fieldset>

    @if ($editable)
        <div class="form-actions application__actions">
            <button type="submit" name="submit" value="0" class="btn btn-outline">Save draft</button>
            <button type="submit" name="submit" value="1" class="btn btn-primary">Save and submit for review</button>
        </div>
    @endif
</form>
@endsection

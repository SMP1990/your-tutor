@extends('layouts.dashboard')

@section('title', $child->exists ? 'Edit child' : 'Add a child')

@section('content')
<div class="dash-head">
    <div>
        <h1>{{ $child->exists ? 'Edit '.$child->name : 'Add a child' }}</h1>
        <p>We only ask for a first name and grade. Nothing else about your child is needed.</p>
    </div>
</div>

<section class="panel panel--form">
    @if ($errors->any())
        <div class="form-alert" role="alert">Please check the highlighted fields.</div>
    @endif

    <form method="post" action="{{ $child->exists ? route('children.update', $child) : route('children.store') }}" class="demo-form" novalidate>
        @csrf
        @if ($child->exists)
            @method('PUT')
        @endif

        <div class="form-grid">
            <div class="field">
                <label for="name">Child's first name</label>
                <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name', $child->name) }}" required maxlength="40" autocomplete="off">
                @error('name') <p class="field__error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="grade">Grade / level</label>
                <select id="grade" name="grade" class="input @error('grade') is-invalid @enderror" required>
                    <option value="">Choose…</option>
                    @foreach ($grades as $grade)
                        <option @selected(old('grade', $child->grade) === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>
                @error('grade') <p class="field__error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $child->exists ? 'Save changes' : 'Add child' }}</button>
            <a href="{{ route('dashboard.parent') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>
@endsection

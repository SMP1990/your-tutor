@extends('layouts.app')

@section('title', 'Subjects | Online tutors for every subject | ' . config('app.name'))
@section('description', 'Online tutors in Pakistan for Maths, Physics, Chemistry, Biology, English, Urdu, Computer Science, Quran, entry tests and more.')

@section('content')

<section class="page-hero">
    <div class="container page-hero__inner">
        <span class="eyebrow">Subjects</span>
        <h1>Online tutors for every subject</h1>
        <p>From Grade 1 to A Levels, MDCAT and ECAT. Choose a subject to see its tutors and what they teach.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        @include('partials.subject-grid', ['subjects' => $subjects])
    </div>
</section>

@endsection

{{-- Overview card: the next few regular classes. Expects $nextClasses and $isTutor. --}}
<section class="panel">
    <div class="panel__head">
        <h2>Next classes</h2>
        <a href="{{ route('classes.index') }}" class="btn btn-outline btn-sm">{{ $isTutor ? 'Full timetable' : 'All classes' }}</a>
    </div>
    @include('dashboard.partials.class-list', ['sessions' => $nextClasses, 'isTutor' => $isTutor])
</section>

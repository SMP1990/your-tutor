{{-- Expects $subjects: list of ['slug', 'name', 'levels'] --}}
<div class="grid grid--subjects">
    @foreach ($subjects as $subject)
        <a href="{{ route('subjects.show', $subject['slug']) }}" class="subject-tile">
            <strong>{{ $subject['name'] }}</strong>
            <span>{{ $subject['levels'] }}</span>
        </a>
    @endforeach
</div>

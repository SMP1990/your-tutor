@php
    $tutorSteps = [
        ['title' => 'Apply online', 'body' => 'Create a tutor account, add your subjects and upload your degree and ID.'],
        ['title' => 'Subject test and interview', 'body' => 'A short test in your subject and a live interview with our team.'],
        ['title' => 'Your profile goes live', 'body' => 'Set your rate and available times. Students can now find you and book demos.'],
        ['title' => 'Teach and get paid', 'body' => 'Teach from home. Your earnings are released after each completed class and you withdraw them from your dashboard.'],
    ];
@endphp

<ol class="grid grid--four tutor-steps">
    @foreach ($tutorSteps as $step)
        <li class="feature-card">
            <span class="tutor-steps__num" aria-hidden="true">{{ $loop->iteration }}</span>
            <strong>{{ $step['title'] }}</strong>
            <span>{{ $step['body'] }}</span>
        </li>
    @endforeach
</ol>

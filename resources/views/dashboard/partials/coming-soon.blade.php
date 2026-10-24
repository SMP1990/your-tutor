{{-- Expects $title and $text. Honest placeholder for a section that is built in a later step. --}}
<section class="panel">
    <div class="panel__head"><h2>{{ $title }}</h2><span class="status status--grey">Coming soon</span></div>
    <p class="muted">{{ $text }}</p>
    @isset($action)
        <div><a href="{{ $action['href'] }}" class="btn btn-outline btn-sm">{{ $action['label'] }}</a></div>
    @endisset
</section>

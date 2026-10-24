{{-- Tab buttons with counts. Expects $tabs (key => [label, count]), $tab (current) and $route. --}}
<nav class="child-tabs" aria-label="Filter">
    @foreach ($tabs as $key => $t)
        <a href="{{ route($route, ['tab' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $tab === $key, 'btn-outline' => $tab !== $key]) @if ($tab === $key) aria-current="page" @endif>
            {{ $t['label'] }} ({{ $t['count'] }})
        </a>
    @endforeach
</nav>

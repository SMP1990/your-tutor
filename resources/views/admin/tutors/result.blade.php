@if ($result === 'passed')
    <span class="status status--green">Passed</span>
@elseif ($result === 'failed')
    <span class="status status--red">Failed</span>
@else
    <span class="status status--grey">Not yet</span>
@endif

{{-- An approved tutor's changes that wait for review (Step 2c-2). Expects $profile and $pendingSubjects. --}}
@php $pending = $profile->pending_changes; @endphp
<section class="panel" id="changes">
    <div class="panel__head">
        <h2>Profile changes to review</h2>
        <span class="status status--amber">Sent {{ $profile->changes_submitted_at?->format('d M Y, g:i A') }}</span>
    </div>
    <p class="muted">The profile stays live with the current details until you approve these.</p>

    <dl class="review-list">
        @if ($pendingSubjects)
            <div><dt>New subjects</dt><dd>{{ implode(', ', $pendingSubjects) }}</dd></div>
        @endif
        @isset($pending['education'])
            <div><dt>New education</dt><dd>
                @foreach ($pending['education'] as $e)
                    {{ $e['degree'] }}, {{ $e['institute'] }}{{ ! empty($e['year']) ? ', '.$e['year'] : '' }}<br>
                @endforeach
            </dd></div>
        @endisset
        @isset($pending['files']['photo_path'])
            <div><dt>New photo</dt><dd><img src="{{ asset('storage/'.$pending['files']['photo_path']) }}" alt="" width="88" height="88" class="application__photo"></dd></div>
        @endisset
    </dl>

    @if ($pendingDocuments->isNotEmpty())
        <div class="child-tabs">
            @foreach ($pendingDocuments as $key)
                <a href="{{ route('admin.tutors.document', [$profile->id, $key, 'pending' => 1]) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">View new {{ $docLabels[$key] }}</a>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('admin.tutors.changes', $profile->id) }}" class="demo-form" novalidate>
        @csrf
        <div class="field">
            <label for="changes_note">Message to the tutor <span class="field__optional">(required to reject)</span></label>
            <textarea id="changes_note" name="changes_note" rows="2" class="input">{{ old('changes_note') }}</textarea>
        </div>
        <div class="form-actions">
            <button type="submit" name="decision" value="approve" class="btn btn-primary">Approve changes</button>
            <button type="submit" name="decision" value="reject" class="btn btn-outline btn-danger" data-confirm-button="Reject these changes?">Reject changes</button>
        </div>
    </form>
</section>

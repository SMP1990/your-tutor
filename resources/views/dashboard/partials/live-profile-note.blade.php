{{-- Approved tutor editing a live profile. Expects $pending (pending_changes array) and $pendingSubjects (names). --}}
<div class="form-note" role="status">
    <strong>Your profile is live.</strong> Changes to your rate, about text, availability, levels, languages
    and city show on your public profile as soon as you save. New subjects, education, a new photo or new
    documents are checked by our team first; until then your current details stay on your profile.
</div>

@if ($pending)
    <div class="form-note form-note--warn" role="status">
        <strong>Waiting for review:</strong>
        {{ collect([
            $pendingSubjects ? 'new subjects ('.implode(', ', $pendingSubjects).')' : null,
            isset($pending['education']) ? 'education' : null,
            isset($pending['files']['photo_path']) ? 'new photo' : null,
            array_intersect_key($pending['files'] ?? [], array_flip(['cnic_front_path', 'cnic_back_path', 'degree_path'])) ? 'new documents' : null,
        ])->filter()->implode(', ') }}.
    </div>
@endif

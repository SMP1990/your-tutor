<?php

namespace App\Support;

use App\Models\TutorProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Owner rule (Step 2c-2): once approved, a tutor's rate, bio, availability and other details go live at
 * once, but NEW subjects, education and uploaded photo/documents wait for an admin to approve them.
 * Waiting changes live in `pending_changes`: {subjects: int[], education: array, files: {column: path}}.
 */
class TutorProfileChanges
{
    /** Column => disk. The photo is public; documents are private. */
    public const FILES = [
        'photo_path' => 'public',
        'cnic_front_path' => 'local',
        'cnic_back_path' => 'local',
        'degree_path' => 'local',
    ];

    /**
     * Saves what may go live now and queues the rest. Returns true when something waits for review.
     *
     * @param  array<string, UploadedFile>  $files  column => new upload
     */
    public static function save(TutorProfile $profile, array $liveData, array $subjectIds, array $education, array $files): bool
    {
        $pending = $profile->pending_changes ?? [];
        $subjectIds = array_map('intval', $subjectIds);

        $current = $profile->subjects()->pluck('subjects.id')->all();
        $kept = array_values(array_intersect($current, $subjectIds));
        if (! $kept) {
            throw ValidationException::withMessages(['subjects' => 'Keep at least one of your approved subjects. New subjects are added after review.']);
        }
        $added = array_values(array_diff($subjectIds, $current));
        $pending['subjects'] = $added;

        if ($education != ($profile->education ?? [])) {
            $pending['education'] = $education;
        } else {
            unset($pending['education']);
        }

        foreach ($files as $column => $file) {
            $path = self::FILES[$column] === 'public'
                ? $file->store("tutors/{$profile->id}", 'public')
                : $file->store("tutors/{$profile->id}/documents", 'local'); // private, random name
            if ($old = $pending['files'][$column] ?? null) {
                Storage::disk(self::FILES[$column])->delete($old);
            }
            $pending['files'][$column] = $path;
        }

        $pending = array_filter($pending); // drop empty lists
        $isNew = $pending != ($profile->pending_changes ?? []);

        $profile->fill($liveData);
        $profile->forceFill([
            'pending_changes' => $pending ?: null,
            'changes_submitted_at' => $pending ? ($isNew ? now() : $profile->changes_submitted_at) : null,
        ])->save();
        $profile->subjects()->sync($kept); // removing a subject is live at once

        return (bool) $pending;
    }

    /** Admin approves: subjects added, education replaced, new files replace the old ones. */
    public static function approve(TutorProfile $profile): void
    {
        $pending = $profile->pending_changes ?? [];

        $profile->subjects()->syncWithoutDetaching($pending['subjects'] ?? []);
        if (array_key_exists('education', $pending)) {
            $profile->education = $pending['education'];
        }
        foreach ($pending['files'] ?? [] as $column => $path) {
            if ($old = $profile->{$column}) {
                Storage::disk(self::FILES[$column])->delete($old);
            }
            $profile->{$column} = $path;
        }

        self::clear($profile);
    }

    /** Admin rejects: uploaded files are deleted, the live profile stays as it was. */
    public static function reject(TutorProfile $profile): void
    {
        foreach ($profile->pending_changes['files'] ?? [] as $column => $path) {
            Storage::disk(self::FILES[$column])->delete($path);
        }

        self::clear($profile);
    }

    private static function clear(TutorProfile $profile): void
    {
        $profile->forceFill(['pending_changes' => null, 'changes_submitted_at' => null])->save();
    }
}

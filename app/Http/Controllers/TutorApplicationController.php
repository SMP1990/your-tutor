<?php

namespace App\Http\Controllers;

use App\Enums\TutorStatus;
use App\Http\Requests\TutorApplicationRequest;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Support\DashboardNav;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** A tutor fills in, saves and submits their own application (Step 2a). Reviewing is Step 2b. */
class TutorApplicationController extends Controller
{
    /** Private documents go to the "local" disk; only the profile photo is public. */
    private const DOCUMENTS = ['cnic_front' => 'cnic_front_path', 'cnic_back' => 'cnic_back_path', 'degree' => 'degree_path'];

    public function edit(Request $request)
    {
        $profile = $this->profileFor($request);

        return view('dashboard.tutor-application', [
            'profile' => $profile,
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'chosenSubjects' => $profile->subjects()->pluck('subjects.id')->all(),
            'missing' => $profile->exists ? $profile->missingForSubmit() : [],
            'nav' => DashboardNav::for('tutor'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function update(TutorApplicationRequest $request)
    {
        $profile = $this->profileFor($request);
        abort_unless($profile->isEditableByTutor(), 403, 'Your application is being reviewed and cannot be changed now.');

        $profile->fill($request->profileData());
        $profile->user()->associate($request->user());
        $profile->slug ??= $this->newSlug($request->user()->name);
        $profile->save();

        $profile->subjects()->sync($request->validated('subjects', []));
        $this->storeFiles($request, $profile);

        $message = $request->boolean('submit') ? $this->submit($profile) : 'Your application is saved.';

        return redirect()->route('tutor.application')->with('notice', $message);
    }

    private function submit(TutorProfile $profile): string
    {
        $missing = $profile->fresh()->missingForSubmit();
        if ($missing) {
            return 'Saved. Before you can submit, please add: '.implode(', ', $missing).'.';
        }

        $profile->forceFill(['status' => TutorStatus::Submitted, 'submitted_at' => now()])->save();

        return 'Thank you! Your application has been sent for review. We will email you about the next step.';
    }

    private function storeFiles(TutorApplicationRequest $request, TutorProfile $profile): void
    {
        $folder = "tutors/{$profile->id}";

        if ($photo = $request->file('photo')) {
            $this->replace($profile, 'photo_path', $photo->store($folder, 'public'), 'public');
        }

        foreach (self::DOCUMENTS as $input => $column) {
            if ($file = $request->file($input)) {
                $this->replace($profile, $column, $this->storePrivate($file, $folder), 'local');
            }
        }
    }

    private function storePrivate(UploadedFile $file, string $folder): string
    {
        // "local" disk = storage/app/private, never reachable from the web. Random file name, so the
        // original name (which may contain personal data) is not kept.
        return $file->store("{$folder}/documents", 'local');
    }

    private function replace(TutorProfile $profile, string $column, string $newPath, string $disk): void
    {
        if ($old = $profile->{$column}) {
            Storage::disk($disk)->delete($old);
        }
        $profile->forceFill([$column => $newPath])->save();
    }

    private function profileFor(Request $request): TutorProfile
    {
        // Always read from the database (not a cached relation), so a tutor never gets a second profile.
        return $request->user()->tutorProfile()->first() ?? new TutorProfile;
    }

    private function newSlug(string $name): string
    {
        do {
            $slug = Str::slug(Str::words($name, 2, '')).'-'.Str::lower(Str::random(4));
        } while (TutorProfile::where('slug', $slug)->exists());

        return $slug;
    }
}

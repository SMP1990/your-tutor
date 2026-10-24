<?php

namespace App\Models;

use App\Enums\TutorStatus;
use Database\Factories\TutorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// 'status', 'approved_at', 'admin_notes' and the document paths are set only by code (admin actions,
// uploads), never straight from a form, so they are not fillable.
#[Fillable([
    'slug', 'headline', 'bio', 'city', 'languages', 'levels', 'experience_years', 'education',
    'hourly_rate_minor', 'currency', 'intro_video_url', 'availability',
])]
class TutorProfile extends Model
{
    /** @use HasFactory<TutorProfileFactory> */
    use HasFactory;

    /** Defaults for a new (unsaved) profile, matching the database defaults. */
    protected $attributes = ['status' => 'draft', 'currency' => 'PKR'];

    /** Fields a tutor must fill in before submitting the application. */
    public const REQUIRED_FOR_SUBMIT = [
        'headline' => 'Headline',
        'bio' => 'About you',
        'city' => 'City',
        'languages' => 'Languages',
        'levels' => 'Levels you teach',
        'hourly_rate_minor' => 'Hourly rate',
        'photo_path' => 'Profile photo',
        'cnic_front_path' => 'CNIC (front)',
        'cnic_back_path' => 'CNIC (back)',
        'degree_path' => 'Degree or transcript',
    ];

    protected function casts(): array
    {
        return [
            'status' => TutorStatus::class,
            'availability' => 'array',
            'education' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Subject, $this> */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    /** Only approved tutors may appear on public pages. */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', TutorStatus::Approved);
    }

    /** Hourly rate in whole currency units for display, e.g. 1800 for PKR 1,800. */
    public function hourlyRate(): ?int
    {
        return $this->hourly_rate_minor === null ? null : intdiv($this->hourly_rate_minor, 100);
    }

    /** The tutor may change the application while it is a draft or when the admin asked for more. */
    public function isEditableByTutor(): bool
    {
        return in_array($this->status, [TutorStatus::Draft, TutorStatus::DocumentsNeeded], true);
    }

    /** @return string[] Labels of what is still missing before the application can be submitted. */
    public function missingForSubmit(): array
    {
        $missing = [];
        foreach (self::REQUIRED_FOR_SUBMIT as $field => $label) {
            if (blank($this->{$field})) {
                $missing[] = $label;
            }
        }
        if ($this->subjects()->doesntExist()) {
            $missing[] = 'Subjects';
        }
        if (empty(array_filter($this->availability ?? []))) {
            $missing[] = 'Availability';
        }
        if (empty($this->education)) {
            $missing[] = 'Education';
        }
        if (mb_strlen((string) $this->bio) > 0 && mb_strlen((string) $this->bio) < 80) {
            $missing[] = 'About you (at least 80 characters)';
        }

        return $missing;
    }
}

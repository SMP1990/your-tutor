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
            'interview_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'pending_changes' => 'array',
            'changes_submitted_at' => 'datetime',
        ];
    }

    /** Approved tutors keep editing their live profile; some changes wait for the admin (TutorProfileChanges). */
    public function isLive(): bool
    {
        return $this->status === TutorStatus::Approved;
    }

    public function hasPendingChanges(): bool
    {
        return ! empty($this->pending_changes);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Approved tutors have a public page at /tutors/{slug}. */
    public function hasPublicPage(): bool
    {
        return $this->status === TutorStatus::Approved && filled($this->slug);
    }

    /** Uploaded photo, else the sample avatar for demo tutors, else a neutral avatar. Domain-portable. */
    public function photoUrl(): string
    {
        if ($this->photo_path) {
            return asset('storage/'.$this->photo_path);
        }
        $avatar = 'images/avatars/'.\Illuminate\Support\Str::slug(strtok((string) $this->user?->name, ' ')).'.svg';

        return asset($this->is_demo && is_file(public_path($avatar)) ? $avatar : 'images/avatars/default.svg');
    }

    /**
     * The shape the public views use (same keys the old DemoTutors arrays had), so pages and
     * forms read real data without knowing about the model.
     */
    public function toPublicArray(): array
    {
        $this->loadMissing(['user:id,name', 'subjects:id,name,slug']);
        $days = config('tutors.days');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->user->name,
            'photo_url' => $this->photoUrl(),
            'is_demo' => (bool) $this->is_demo,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'about' => array_values(array_filter(preg_split('/\R{2,}/', (string) $this->bio))),
            'city' => $this->city,
            'experience_years' => $this->experience_years,
            'subjects' => $this->subjects->pluck('name')->all(),
            'subject_slugs' => $this->subjects->pluck('slug')->all(),
            'levels' => $this->levels,
            'languages' => $this->languages,
            'rate' => $this->hourlyRate() !== null ? $this->currency.' '.number_format($this->hourlyRate()) : null,
            'rating' => $this->rating_avg !== null ? number_format((float) $this->rating_avg, 1) : null,
            'reviews_count' => (int) $this->reviews_count,
            'availability' => collect($days)->mapWithKeys(fn ($d) => [$d => $this->availability[$d] ?? []])->all(),
            'qualifications' => array_map(fn ($e) => [
                'title' => $e['degree'] ?? '', 'place' => $e['institute'] ?? '', 'year' => $e['year'] ?? '',
            ], $this->education ?? []),
            'intro_video_url' => $this->intro_video_url,
        ];
    }

    /** Owner rule: a tutor is approved only after passing the subject test and the interview. */
    public function canBeApproved(): bool
    {
        return $this->subject_test_result === 'passed' && $this->interview_result === 'passed';
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

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<DemoRequest, $this> */
    public function demoRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DemoRequest::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<ClassBooking, $this> */
    public function classBookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<Review, $this> */
    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Recalculates the stored rating from visible reviews (Step 5). Called after a review is saved or
     * hidden. Stored on the profile so search can filter and sort on it cheaply.
     */
    public function refreshRating(): void
    {
        $count = $this->reviews()->visible()->count();

        $this->forceFill([
            'reviews_count' => $count,
            'rating_avg' => $count ? round((float) $this->reviews()->visible()->avg('rating'), 1) : null,
        ])->save();
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

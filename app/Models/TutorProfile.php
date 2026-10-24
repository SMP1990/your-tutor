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

// 'status', 'approved_at' and 'admin_notes' are set only by admin actions, so they are not fillable.
#[Fillable([
    'slug', 'headline', 'bio', 'city', 'languages', 'levels', 'experience_years',
    'hourly_rate_minor', 'currency', 'photo_path', 'intro_video_url', 'availability',
])]
class TutorProfile extends Model
{
    /** @use HasFactory<TutorProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TutorStatus::class,
            'availability' => 'array',
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
}

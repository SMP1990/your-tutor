<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 'role' is deliberately not fillable: it is set explicitly so a form can never make someone an admin.
#[Fillable(['name', 'email', 'password', 'phone', 'whatsapp_opt_in'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'whatsapp_opt_in' => 'boolean',
        ];
    }

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    /** Set only by the admin Users page (suspended_at is not fillable). */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** What admins did to this account, newest first. @return HasMany<AdminAction, $this> */
    public function adminActions(): HasMany
    {
        return $this->hasMany(AdminAction::class)->latest('id');
    }

    /** @return HasMany<Child, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'parent_id');
    }

    /** Free demo requests this parent or student sent. @return HasMany<DemoRequest, $this> */
    public function demoRequests(): HasMany
    {
        return $this->hasMany(DemoRequest::class);
    }

    /** Regular-class bookings this parent or student made. @return HasMany<ClassBooking, $this> */
    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    /** @return HasOne<TutorProfile, $this> */
    public function tutorProfile(): HasOne
    {
        return $this->hasOne(TutorProfile::class);
    }
}

<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:72'],
        ];
    }

    /** Log in with email or phone number. Locks out after 5 wrong tries for a minute. */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = $this->findUser();
        if (! $user || ! Auth::attempt(['id' => $user->id, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages(['login' => 'These details do not match an account.']);
        }

        RateLimiter::clear($this->throttleKey());

        // Said only after the right password, so nobody can probe which accounts exist or are suspended.
        if ($user->isSuspended()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages(['login' => 'This account has been suspended. Please contact support.']);
        }
    }

    private function findUser(): ?User
    {
        $login = trim($this->string('login'));

        if (str_contains($login, '@')) {
            return User::where('email', mb_strtolower($login))->first();
        }

        // Phone numbers are not unique (a family may share one), so only log in when it is unambiguous.
        $phone = preg_replace('/[\s-]/', '', $login);
        $matches = User::where('phone', $phone)->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => "Too many attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}

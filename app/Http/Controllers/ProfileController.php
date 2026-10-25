<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\ConfirmNewEmail;
use App\Notifications\EmailChangeRequested;
use App\Support\DashboardNav;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * "Profile" for every role: name and phone, email, password. Each change asks for the current password.
 * A new email only replaces the old one after its owner opens the link sent to it. A tutor's name is read-only
 * here: it was checked against the CNIC and shows on the public profile, so the admin changes it.
 */
class ProfileController extends Controller
{
    private const WRONG_PASSWORD = 'That is not your current password.';

    public function edit(Request $request)
    {
        $user = $request->user();

        return view('dashboard.profile', [
            'account' => $user,
            'nameLocked' => $user->hasRole(Role::Tutor),
            'passwordMin' => $this->passwordMin($user),
            'nav' => DashboardNav::for($user->role->value),
            'user' => ['name' => $user->name, 'role' => $user->role->label()],
        ]);
    }

    public function updateDetails(Request $request)
    {
        $user = $request->user();
        $request->merge(['phone' => preg_replace('/[\s-]/', '', (string) $request->input('phone'))]);

        $data = $request->validateWithBag('details', [
            'current_password' => ['required', 'current_password'],
            'name' => [$user->hasRole(Role::Tutor) ? 'nullable' : 'required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^(\+92|0)3\d{9}$/'],
        ], [
            'current_password.current_password' => self::WRONG_PASSWORD,
            'phone.regex' => 'Enter a Pakistani mobile number, e.g. 0300 1234567.',
        ]);

        $changes = ['phone' => $data['phone']];
        if (! $user->hasRole(Role::Tutor)) {
            $changes['name'] = trim($data['name']);
        }
        if ($data['phone'] !== $user->phone) {
            $changes['phone_verified_at'] = null; // a new number is not verified (phone codes come later)
        }
        $user->forceFill($changes)->save();

        return redirect()->route('profile.edit')->with('notice', 'Your details are saved.');
    }

    public function requestEmail(Request $request)
    {
        $user = $request->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validateWithBag('email', [
            'current_password' => ['required', 'current_password'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email'), Rule::notIn([$user->email])],
        ], [
            'current_password.current_password' => self::WRONG_PASSWORD,
            'email.unique' => 'An account with this email already exists.',
            'email.not_in' => 'This is already your email.',
        ]);

        $user->forceFill(['pending_email' => $data['email']])->save();

        try {
            Notification::route('mail', $data['email'])->notifyNow(new ConfirmNewEmail($user, $data['email']));
        } catch (Throwable $e) {
            report($e);
            $user->forceFill(['pending_email' => null])->save();

            return redirect()->route('profile.edit')->withErrors(['email' => 'We could not send the email right now. Please try again in a minute.'], 'email');
        }
        $user->notify(new EmailChangeRequested($data['email'])); // warns the old address (queued)

        return redirect()->route('profile.edit')->with('notice', "We sent a link to {$data['email']}. Open it to finish the change. Until then you keep logging in with your old email.");
    }

    /** The signed link from the new address. Works on any device, like the first verification link. */
    public function confirmEmail(Request $request, User $user, string $hash)
    {
        abort_unless($user->pending_email && hash_equals(sha1($user->pending_email), $hash), 403);

        $back = $request->user()?->is($user) ? 'profile.edit' : 'login';

        if (User::where('email', $user->pending_email)->where('id', '!=', $user->id)->exists()) {
            $user->forceFill(['pending_email' => null])->save();

            return redirect()->route($back)->with('notice', 'That email now belongs to another account, so it was not changed.');
        }

        $user->forceFill(['email' => $user->pending_email, 'pending_email' => null, 'email_verified_at' => now()])->save();

        return redirect()->route($back)->with('notice', 'Your new email is confirmed. Use it to log in from now on.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'max:72', 'confirmed', 'different:current_password', Password::min($this->passwordMin($user))->letters()->numbers()],
        ], [
            'current_password.current_password' => self::WRONG_PASSWORD,
            'password.different' => 'Choose a password you have not used just now.',
        ]);

        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();

        // Log out every other browser that is still signed in to this account.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        }

        return redirect()->route('profile.edit')->with('notice', 'Your password is changed. Any other device was logged out.');
    }

    private function passwordMin(User $user): int
    {
        return $user->hasRole(Role::Admin) ? 12 : 8;
    }
}

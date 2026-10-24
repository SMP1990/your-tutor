<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** Reset by email link (Laravel's built-in password broker). Phone reset comes with an SMS provider. */
class PasswordResetController extends Controller
{
    private const SENT = 'If an account exists for that email, we have sent a link to reset the password. Check your inbox and spam folder.';

    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $request->validate(['email' => ['required', 'email', 'max:120']]);

        Password::sendResetLink($request->only('email'));

        // Same answer whether or not the email exists, so nobody can test which emails have accounts.
        return back()->with('notice', self::SENT);
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'max:72', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'This reset link is invalid or has expired. Please ask for a new one.']);
        }

        return redirect()->route('login')->with('notice', 'Your password has been changed. Please log in.');
    }
}

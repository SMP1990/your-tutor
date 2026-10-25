<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationController extends Controller
{
    public const NOT_SENT = 'We could not send the email right now. Please wait a minute and press the button again.';

    /** "Check your email" page, shown after registering. */
    public function notice(Request $request)
    {
        return view('auth.verify-email', ['user' => $request->user()]);
    }

    /**
     * The link from the email. It is signed and expires, so it also works when opened on another
     * device (for example the phone's mail app) where the person is not logged in.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route($request->user()?->is($user) ? 'verification.notice' : 'login')
            ->with('notice', 'Your email is verified. Thank you!');
    }

    public function resend(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail() && ! self::sendLink($request->user())) {
            return back()->with('notice', self::NOT_SENT);
        }

        return back()->with('notice', 'We sent a new link. Please check your inbox and spam folder.');
    }

    /** Sends the verification email at once. False if the mail server failed (the page must not break). */
    public static function sendLink(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }
}

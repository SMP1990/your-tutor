<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
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
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('notice', 'We sent a new link. Please check your inbox and spam folder.');
    }
}

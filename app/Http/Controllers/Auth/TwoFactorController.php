<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    private const NOT_SENT = 'We could not send the email right now. Please wait a minute and press "Send a new code".';

    public function show(Request $request)
    {
        if (TwoFactorCode::passed($request)) {
            return redirect()->route('dashboard');
        }

        $view = view('auth.two-factor', ['email' => $request->user()->email]);

        if (! TwoFactorCode::hasActiveCode($request->user()) && ! TwoFactorCode::send($request->user())) {
            $view->withErrors(['code' => self::NOT_SENT]);
        }

        return $view;
    }

    public function store(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        if (! TwoFactorCode::check($request->user(), $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'This code is wrong or has expired.']);
        }

        TwoFactorCode::markPassed($request);

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request)
    {
        if (! TwoFactorCode::send($request->user())) {
            return back()->withErrors(['code' => self::NOT_SENT]);
        }

        return back()->with('notice', 'We sent a new code to your email.');
    }
}

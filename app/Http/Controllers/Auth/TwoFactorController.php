<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        if (TwoFactorCode::passed($request)) {
            return redirect()->route('dashboard');
        }

        if (! TwoFactorCode::hasActiveCode($request->user())) {
            TwoFactorCode::send($request->user());
        }

        return view('auth.two-factor', ['email' => $request->user()->email]);
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
        TwoFactorCode::send($request->user());

        return back()->with('notice', 'We sent a new code to your email.');
    }
}

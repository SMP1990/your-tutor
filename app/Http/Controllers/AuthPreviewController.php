<?php

namespace App\Http\Controllers;

/**
 * DESIGN ONLY. Shows the login pages and moves between them. Nothing is read, checked or stored;
 * replace with Laravel's built-in auth (plus phone OTP) in the database step.
 */
class AuthPreviewController extends Controller
{
    private const NOT_YET = 'Accounts open soon. This page is a preview and nothing was saved.';

    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function verifyPhone()
    {
        return view('auth.verify-phone');
    }

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function notYet()
    {
        return back()->with('notice', self::NOT_YET);
    }

    public function afterRegister()
    {
        return redirect()->route('verify-phone');
    }
}

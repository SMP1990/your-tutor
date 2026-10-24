<?php

namespace App\Http\Controllers;

/**
 * DESIGN ONLY for the pages not built yet (log in, phone code, forgot password).
 * Nothing is read, checked or stored. Replaced step by step (Register is real since Step 1b).
 */
class AuthPreviewController extends Controller
{
    private const NOT_YET = 'Log in opens soon. This page is a preview and nothing was saved.';

    public function login()
    {
        return view('auth.login');
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
}

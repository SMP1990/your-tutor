<?php

namespace App\Http\Controllers;

/**
 * DESIGN ONLY: the phone-code page. Nothing is read, checked or stored.
 * Replace when an SMS/WhatsApp OTP provider is added (owner decision: paid services later).
 */
class AuthPreviewController extends Controller
{
    public function verifyPhone()
    {
        return view('auth.verify-phone');
    }

    public function notYet()
    {
        return back()->with('notice', 'Phone codes are coming soon. This page is a preview and nothing was saved.');
    }
}

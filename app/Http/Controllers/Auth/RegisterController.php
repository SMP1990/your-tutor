<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'whatsapp_opt_in' => (bool) ($data['whatsapp_ok'] ?? false),
        ]);
        $user->role = Role::from($data['role']); // set explicitly, never mass-assigned
        $user->save();

        // Sent at once, not through the Registered event (its listener is queued and would wait for a cron worker).
        $sent = EmailVerificationController::sendLink($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with($sent ? [] : ['notice' => EmailVerificationController::NOT_SENT]);
    }
}

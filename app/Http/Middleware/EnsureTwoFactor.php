<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Support\TwoFactorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admins must also enter an emailed code once per login session before using admin pages. */
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole(Role::Admin) && ! TwoFactorCode::passed($request)) {
            return redirect()->guest(route('two-factor.show'));
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage on a route: ->middleware('role:parent') or 'role:student,parent'. Others get 403. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless($request->user() && in_array($request->user()->role, $allowed, true), 403);

        return $next($request);
    }
}

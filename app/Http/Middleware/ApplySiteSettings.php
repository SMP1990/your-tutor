<?php

namespace App\Http\Middleware;

use App\Support\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uses the contact details saved by the admin (Step 6d) instead of the .env defaults. */
class ApplySiteSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        SiteSettings::apply();

        return $next($request);
    }
}

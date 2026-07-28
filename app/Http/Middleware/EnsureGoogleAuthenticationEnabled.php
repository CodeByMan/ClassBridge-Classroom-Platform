<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGoogleAuthenticationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('services.google.enabled'), 404);

        return $next($request);
    }
}

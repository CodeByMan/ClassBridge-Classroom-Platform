<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureDatabaseIsReady
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('/') || $request->is('setup-required')) {
            return $next($request);
        }

        try {
            if (Schema::hasTable('migrations') && Schema::hasTable('users')) {
                return $next($request);
            }
        } catch (Throwable) {
            // Render a deterministic setup response instead of exposing a SQL stack trace.
        }

        return response()
            ->view('errors.setup-required', status: Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '60');
    }
}

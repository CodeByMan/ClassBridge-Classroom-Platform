<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function (): void {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by($this->emailAndIpKey($request))
        );

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(5)->by($this->emailAndIpKey($request))
        );

        RateLimiter::for('oauth', fn (Request $request) => Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('group-create', fn (Request $request) => Limit::perHour(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('group-join', fn (Request $request) => Limit::perHour(30)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('post-create', fn (Request $request) => Limit::perHour(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('post-submit', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('comment-create', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('profile-image', fn (Request $request) => Limit::perHour(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('content-filter', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip())
        );
    }

    private function emailAndIpKey(Request $request): string
    {
        return Str::lower(trim((string) $request->input('email'))).'|'.$request->ip();
    }
}

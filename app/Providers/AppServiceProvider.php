<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(180)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($this->rateLimitIdentity($request, 'email'));
        });

        RateLimiter::for('backoffice-login', function (Request $request) {
            return Limit::perMinute(5)->by('backoffice|'.$this->rateLimitIdentity($request, 'email'));
        });

        RateLimiter::for('public-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('nomination-submit', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });
    }

    private function rateLimitIdentity(Request $request, string $field): string
    {
        return strtolower((string) $request->input($field, 'guest')).'|'.$request->ip();
    }
}

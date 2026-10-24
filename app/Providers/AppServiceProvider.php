<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LogicException;

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
        $this->ensureApplicationRunsInUtc();
        $this->configureRateLimiting();
    }

    /**
     * Stop the application when it is configured for a timezone other than UTC.
     *
     * Times are stored in UTC and compared as text, so another server
     * timezone silently shifts reminders, completion times, and sync by its
     * offset. What users see follows the timezone of their account and space.
     */
    private function ensureApplicationRunsInUtc(): void
    {
        if (config('app.timezone') !== 'UTC') {
            throw new LogicException('APP_TIMEZONE harus UTC. Zona waktu pengguna diatur per akun dan per ruang, bukan di server.');
        }
    }

    /**
     * Define the request limits from the API contract.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by(
            $request->user()?->id ?: $request->ip(),
        ));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // The limit runs before validation, so an email sent as an array or a
        // number must not reach the string functions; it counts per IP alone.
        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');

            return Limit::perMinute(5)->by(
                Str::transliterate(Str::lower(is_string($email) ? $email : '').'|'.$request->ip()),
            );
        });

        RateLimiter::for('invitation-accept', fn (Request $request) => [
            Limit::perMinute(10)->by('account:'.$request->user()?->id),
            Limit::perMinute(10)->by('ip:'.$request->ip()),
        ]);
    }
}

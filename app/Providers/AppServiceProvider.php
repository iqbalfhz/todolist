<?php

namespace App\Providers;

use App\Actions\NotifySpaceChanges;
use App\Enums\SubjectType;
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
        $this->app->scoped(NotifySpaceChanges::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureApplicationRunsInUtc();
        $this->configureRateLimiting();

        SubjectType::registerMorphMap();
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

        // The email is only known once the token is checked, so this counts per IP alone, as registration does.
        RateLimiter::for('google', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

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

        RateLimiter::for('places', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));

        // Deleting the account asks for the password again, so this keeps it from being guessed here.
        RateLimiter::for('account-delete', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()));

        // One export reads every row the account can see.
        RateLimiter::for('data-export', fn (Request $request) => Limit::perHour(5)->by($request->user()?->id ?: $request->ip()));

        // One import part saves up to a thousand rows.
        RateLimiter::for('data-import', fn (Request $request) => Limit::perHour(30)->by($request->user()?->id ?: $request->ip()));

        // Per email, so one inbox is not flooded, and per IP, so one sender cannot try many inboxes.
        // The limit runs before validation, so only an email sent as text reaches the string functions.
        RateLimiter::for('password-forgot', function (Request $request): array {
            $email = $request->input('email');

            return [
                Limit::perMinutes(15, 3)->by('email:'.Str::transliterate(Str::lower(is_string($email) ? $email : ''))),
                Limit::perHour(10)->by('ip:'.$request->ip()),
            ];
        });

        // Each code also ends after five wrong tries, so codes cannot be guessed here.
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('email-verification', fn (Request $request) => Limit::perMinutes(15, 3)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('email-verification-confirm', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // Both ask for the current password, so this keeps it from being guessed through them.
        RateLimiter::for('password-change', fn (Request $request) => Limit::perMinutes(15, 5)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('email-change', fn (Request $request) => Limit::perMinutes(15, 3)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('email-change-confirm', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    }
}

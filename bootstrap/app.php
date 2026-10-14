<?php

use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // In production every request arrives from cloudflared (Cloudflare Tunnel)
        // through Docker. Trusting only that caller makes the app see HTTPS and take
        // the client IP from the last X-Forwarded-For entry, which Cloudflare adds;
        // the client can forge the entries before it. '*' would trust every address
        // and let a forged first entry slip past the rate limits.
        $middleware->trustProxies(at: 'REMOTE_ADDR');

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : route('filament.admin.auth.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->is('api/*')) {
                return app(ApiExceptionRenderer::class)->render($exception, $request);
            }
        });
    })->create();

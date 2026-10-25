<?php

namespace App\Http\Middleware;

use App\Actions\NotifySpaceChanges;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NotifyPartnersOfChanges
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(private NotifySpaceChanges $changes) {}

    /**
     * Let the request make its changes, then queue the sync pushes they call for.
     *
     * By then every transaction of the request has finished. The changes are
     * already saved, so a failure here is only reported: an error response
     * would make the phone send the same change again.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->changes->start();

        $response = $next($request);

        try {
            $this->changes->send($request->user());
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}

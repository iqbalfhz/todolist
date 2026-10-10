<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionRenderer
{
    public function __construct(private CurrentResourceResolver $currentResource) {}

    /**
     * Render any exception thrown by an API route in the contract's error shape.
     */
    public function render(Throwable $exception, Request $request): JsonResponse
    {
        $code = $this->codeFor($exception);

        $payload = [
            'message' => $this->messageFor($exception, $code),
            'code' => $code->value,
        ];

        if ($exception instanceof ValidationException) {
            $payload['errors'] = $exception->errors();
        }

        if ($this->carriesCurrent($code) && $this->currentResource->appliesTo($request)) {
            $payload['current'] = $this->currentResource->resolve($request);
        }

        if ($code === ErrorCode::ServerError && config('app.debug')) {
            $payload['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return response()->json(
            $payload,
            $this->statusFor($exception, $code),
            $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [],
        );
    }

    /**
     * Map the exception to one of the codes the app knows.
     */
    private function codeFor(Throwable $exception): ErrorCode
    {
        if ($exception instanceof ApiException) {
            return $exception->errorCode;
        }

        if ($exception instanceof ValidationException) {
            return ErrorCode::ValidationFailed;
        }

        if ($exception instanceof AuthenticationException) {
            return ErrorCode::Unauthenticated;
        }

        if (! $exception instanceof HttpExceptionInterface) {
            return ErrorCode::ServerError;
        }

        return match ($exception->getStatusCode()) {
            401 => ErrorCode::Unauthenticated,
            403 => ErrorCode::Forbidden,
            404 => $this->isMissingRecord($exception) ? ErrorCode::NotFound : ErrorCode::RouteNotFound,
            405 => ErrorCode::RouteNotFound,
            429 => ErrorCode::TooManyRequests,
            default => ErrorCode::ServerError,
        };
    }

    /**
     * Determine whether a 404 came from a missing or hidden record rather than an unknown route.
     */
    private function isMissingRecord(Throwable $exception): bool
    {
        $previous = $exception->getPrevious();

        return $previous instanceof ModelNotFoundException || $previous instanceof AuthorizationException;
    }

    /**
     * Get the message the app can show to the user.
     */
    private function messageFor(Throwable $exception, ErrorCode $code): string
    {
        if ($exception instanceof ApiException || $exception instanceof ValidationException) {
            return $exception->getMessage();
        }

        return __("api.errors.{$code->value}");
    }

    /**
     * Get the HTTP status, keeping the original one for server-side failures such as 503.
     */
    private function statusFor(Throwable $exception, ErrorCode $code): int
    {
        if ($code === ErrorCode::ServerError && $exception instanceof HttpExceptionInterface && $exception->getStatusCode() >= 500) {
            return $exception->getStatusCode();
        }

        return $code->status();
    }

    /**
     * Determine whether an error with this code tells the app what the server currently stores.
     */
    private function carriesCurrent(ErrorCode $code): bool
    {
        return ! in_array($code, [
            ErrorCode::Unauthenticated,
            ErrorCode::RouteNotFound,
            ErrorCode::TooManyRequests,
            ErrorCode::ServerError,
        ], true);
    }
}

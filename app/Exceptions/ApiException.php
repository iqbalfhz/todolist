<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class ApiException extends Exception implements ShouldntReport
{
    /**
     * Create an error the app can act on through its contract code.
     */
    public function __construct(public readonly ErrorCode $errorCode, ?string $message = null)
    {
        parent::__construct($message ?? __("api.errors.{$errorCode->value}"));
    }
}

<?php

namespace App\Http\Resources\Concerns;

trait FormatsPosition
{
    /**
     * Send a whole position as an integer, so 1024 does not become 1024.0 in JSON.
     */
    protected function position(float $position): int|float
    {
        return fmod($position, 1.0) === 0.0 ? (int) $position : $position;
    }
}

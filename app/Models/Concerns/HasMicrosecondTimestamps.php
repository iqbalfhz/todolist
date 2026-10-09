<?php

namespace App\Models\Concerns;

trait HasMicrosecondTimestamps
{
    /**
     * Get the format for database stored dates.
     *
     * A trait cannot redeclare the inherited $dateFormat property with a
     * different default, so the format is supplied through the getter.
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat ?: 'Y-m-d H:i:s.u';
    }
}

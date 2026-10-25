<?php

namespace App\Models\Concerns;

use App\Actions\NotifySpaceChanges;

/**
 * Notes the row's space whenever a row is created, changed, or deleted, so the
 * phones of the other members hear about it (see NotifySpaceChanges).
 *
 * Every model that GET /sync sends uses this.
 */
trait NotifiesSpaceChanges
{
    /**
     * Boot the trait.
     */
    public static function bootNotifiesSpaceChanges(): void
    {
        $record = fn (self $model) => resolve(NotifySpaceChanges::class)->record($model->changedSpaceId());

        static::created($record);
        static::updated($record);
        static::deleted($record);
    }

    /**
     * Get the ID of the space the row belongs to.
     */
    public function changedSpaceId(): string
    {
        return $this->space_id;
    }
}

<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\TaskList;
use App\Rules\Iso8601WithOffset;
use Illuminate\Support\Facades\DB;

class UpdateTaskList
{
    public function __construct(private KeepOneActiveList $keepOneActiveList) {}

    /**
     * Apply the given changes to the list, archiving or restoring it when asked.
     *
     * The checklist of a trip cannot be archived.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(TaskList $list, array $changes): TaskList
    {
        return DB::transaction(function () use ($list, $changes): TaskList {
            if (isset($changes['archived_at'])) {
                if ($list->trip_id !== null) {
                    throw new ApiException(ErrorCode::TripChecklist);
                }

                $this->keepOneActiveList->handle($list);

                $changes['archived_at'] = Iso8601WithOffset::toServerTime($changes['archived_at']);
            }

            $list->update($changes);

            return $list;
        });
    }
}

<?php

namespace App\Actions;

use App\Jobs\RecalculateSpaceReminders;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProfile
{
    /**
     * Apply the given changes to the user's profile.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(User $user, array $changes): User
    {
        return DB::transaction(function () use ($user, $changes): User {
            if (array_key_exists('notification_prefs', $changes)) {
                $changes['notification_prefs'] = [
                    ...$user->notification_prefs,
                    ...array_map(boolval(...), $changes['notification_prefs']),
                ];
            }

            $user->fill($changes);

            $timezoneChanged = $user->isDirty('timezone');
            $partnerSeesChange = $user->isDirty(['name', 'color', 'birth_date']);

            $user->save();

            if ($partnerSeesChange) {
                $this->touchSpaces($user);
            }

            if ($timezoneChanged) {
                $this->moveToTimezone($user);
            }

            return $user;
        });
    }

    /**
     * Bump every space the user is in, so the new profile reaches the partner through sync.
     */
    public function touchSpaces(User $user): void
    {
        $user->spaces()->get()->each->touch();
    }

    /**
     * Make the personal space follow the user's timezone and recalculate its reminders.
     */
    private function moveToTimezone(User $user): void
    {
        $space = $user->personalSpace();

        if ($space === null) {
            return;
        }

        $space->update(['timezone' => $user->timezone]);

        RecalculateSpaceReminders::dispatch($space->id)->afterCommit();
    }
}

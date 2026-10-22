<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\Trip;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SendTripReminders
{
    /**
     * How many days before the start of the trip each reminder goes out, with its text.
     *
     * @var array<int, string>
     */
    private const DAYS_BEFORE = [
        7 => 'push.trip_reminder.in_a_week',
        1 => 'push.trip_reminder.tomorrow',
    ];

    public function __construct(private DailyReminderWindow $window) {}

    /**
     * Push the reminders of the trips that start in 7 days or tomorrow, with the progress of their checklist.
     *
     * @return int The reminders sent.
     */
    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;

        $this->window->spacesDueNow($now)->each(function (Space $space) use ($now, &$sent): void {
            $today = $this->window->today($space, $now);

            foreach (self::DAYS_BEFORE as $daysBefore => $text) {
                $sent += Trip::query()
                    ->where('space_id', $space->id)
                    ->where('start_date', $today->addDays($daysBefore)->toDateString())
                    ->withCount([
                        'checklistTasks',
                        'checklistTasks as completed_checklist_tasks_count' => fn (Builder $tasks) => $tasks->whereNotNull('completed_at'),
                    ])
                    ->get()
                    // The start date is part of the key, so a trip that is moved is reminded again for its new date.
                    ->filter(fn (Trip $trip): bool => $this->window->send(
                        "trip:{$trip->id}:{$trip->start_date}",
                        $today,
                        fn () => $space->members->each(fn (User $member) => SendPushNotification::dispatch(
                            $member->id,
                            PushType::TripReminder,
                            __('push.trip_reminder.title'),
                            $this->body($trip, $text),
                            $space->id,
                            tripId: $trip->id,
                        )),
                    ))
                    ->count();
            }
        });

        return $sent;
    }

    /**
     * Get the text of the reminder, with the progress of the checklist once it has items.
     */
    private function body(Trip $trip, string $text): string
    {
        $reminder = __($text, ['title' => $trip->title]);

        if ($trip->checklist_tasks_count === 0) {
            return $reminder;
        }

        return __('push.trip_reminder.with_progress', [
            'reminder' => $reminder,
            'done' => $trip->completed_checklist_tasks_count,
            'total' => $trip->checklist_tasks_count,
        ]);
    }
}

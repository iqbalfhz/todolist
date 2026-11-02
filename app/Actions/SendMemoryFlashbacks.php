<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Memory;
use App\Models\Space;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SendMemoryFlashbacks
{
    public function __construct(private DailyReminderWindow $window) {}

    /**
     * Push one reminder per space of its memories from this day in earlier years.
     *
     * The memory from the latest of those years is the one shown, the first
     * made when a year has several, and the others are counted.
     *
     * @return int The reminders sent.
     */
    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;

        $this->window->spacesDueNow($now)->each(function (Space $space) use ($now, &$sent): void {
            $today = $this->window->today($space, $now);
            $memories = $this->memoriesOnThisDay($space, $today);

            if ($memories->isEmpty()) {
                return;
            }

            $shown = $memories->first();

            $sent += (int) $this->window->send(
                "flashback:{$space->id}",
                $today,
                fn () => $space->members->each(fn (User $member) => SendPushNotification::dispatch(
                    $member->id,
                    PushType::MemoryFlashback,
                    $this->title($shown, $today),
                    $this->body($shown, $memories->count() - 1),
                    $space->id,
                    memoryId: $shown->id,
                )),
            );
        });

        return $sent;
    }

    /**
     * Get the memories of the space made on this month and day in an earlier year, the one to show first.
     *
     * @return Collection<int, Memory>
     */
    private function memoriesOnThisDay(Space $space, CarbonImmutable $today): Collection
    {
        return Memory::query()
            ->where('space_id', $space->id)
            ->whereYear('visited_on', '<', $today->year)
            ->where(fn (Builder $query) => collect($this->window->monthDays($today))->each(
                fn (string $monthDay) => $query->orWhere(fn (Builder $day) => $day
                    ->whereMonth('visited_on', (int) substr($monthDay, 0, 2))
                    ->whereDay('visited_on', (int) substr($monthDay, 3, 2))),
            ))
            ->orderByDesc('visited_on')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get the title, which says how many years ago the memory shown was.
     */
    private function title(Memory $memory, CarbonImmutable $today): string
    {
        $years = $today->year - (int) substr($memory->visited_on, 0, 4);

        return $years === 1
            ? __('push.memory_flashback.one_year')
            : __('push.memory_flashback.years', ['years' => $years]);
    }

    /**
     * Get the body: the title of the memory shown, and how many others fall on this day.
     */
    private function body(Memory $memory, int $others): string
    {
        return $others === 0
            ? $memory->title
            : __('push.memory_flashback.with_others', ['title' => $memory->title, 'count' => $others]);
    }
}

<?php

use App\Actions\PurgeDeletedRecords;
use App\Models\Comment;
use App\Models\DailyReminder;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Reaction;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\SpecialDate;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('r2');
});

it('deletes for good the rows deleted more than 30 days ago, and keeps the others', function (Closure $create) {
    $space = User::factory()->withPersonalSpace()->create()->personalSpace();
    $this->travelTo('2026-10-31 23:59:59.999999');
    $old = tap($create($space))->delete();
    $this->travelTo('2026-11-01 00:00:00');
    $atTheLimit = tap($create($space))->delete();
    $active = $create($space);
    $this->travelTo('2026-12-01 00:00:00');

    $purged = app(PurgeDeletedRecords::class)->handle();

    expect($purged['records'])->toBe(1);
    $this->assertModelMissing($old);
    $this->assertSoftDeleted($atTheLimit);
    $this->assertNotSoftDeleted($active);
})->with([
    'a list' => [fn (Space $space) => TaskList::factory()->for($space)->create()],
    'a task' => [fn (Space $space) => Task::factory()->for(TaskList::factory()->for($space))->create()],
    'a goal' => [fn (Space $space) => SavingsGoal::factory()->for($space)->create()],
    'a deposit' => [fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->create()],
    'a special date' => [fn (Space $space) => SpecialDate::factory()->for($space)->create()],
    'a trip' => [fn (Space $space) => Trip::factory()->for($space)->create()],
    'a memory' => [fn (Space $space) => Memory::factory()->for($space)->create()],
    'a memory photo' => [fn (Space $space) => MemoryPhoto::factory()->for(Memory::factory()->for($space))->create()],
    'a comment' => [fn (Space $space) => Comment::factory()->on(Memory::factory()->for($space)->create())->create()],
    'a reaction' => [fn (Space $space) => Reaction::factory()->on(Memory::factory()->for($space)->create())->create()],
]);

it('keeps a deleted row while a row deleted later still points at it, then deletes both in one run', function (Closure $create) {
    $space = User::factory()->withPersonalSpace()->create()->personalSpace();
    [$parent, $child] = $create($space);
    $this->travelTo('2026-10-01 00:00:00');
    $parent->delete();
    $this->travelTo('2026-10-20 00:00:00');
    $child->delete();
    $this->travelTo('2026-11-05 00:00:00');

    app(PurgeDeletedRecords::class)->handle();

    $this->assertSoftDeleted($parent);
    $this->assertSoftDeleted($child);

    $this->travelTo('2026-11-20 00:00:00');
    app(PurgeDeletedRecords::class)->handle();

    $this->assertModelMissing($child);
    $this->assertModelMissing($parent);
})->with([
    'a task in a list' => [function (Space $space) {
        $list = TaskList::factory()->for($space)->create();

        return [$list, Task::factory()->for($list)->create()];
    }],
    'the checklist of a trip' => [function (Space $space) {
        $trip = Trip::factory()->for($space)->create();

        return [$trip, TaskList::factory()->for($space)->create(['trip_id' => $trip->id])];
    }],
    'a memory from a trip' => [function (Space $space) {
        $trip = Trip::factory()->for($space)->create();

        return [$trip, Memory::factory()->for($space)->create(['trip_id' => $trip->id])];
    }],
    'a deposit to a goal' => [function (Space $space) {
        $goal = SavingsGoal::factory()->for($space)->create();

        return [$goal, SavingsEntry::factory()->for($goal)->create()];
    }],
    'a trip saving towards a goal' => [function (Space $space) {
        $goal = SavingsGoal::factory()->for($space)->create();

        return [$goal, Trip::factory()->for($space)->create(['savings_goal_id' => $goal->id])];
    }],
    'a photo of a memory' => [function (Space $space) {
        $memory = Memory::factory()->for($space)->create();

        return [$memory, MemoryPhoto::factory()->for($memory)->create()];
    }],
]);

it('clears the link to a purged next occurrence with a new updated_at, and keeps the task before it', function () {
    $list = TaskList::factory()->create();
    $this->travelTo('2026-10-01 00:00:00');
    $next = Task::factory()->for($list)->create();
    $task = Task::factory()->for($list)->create(['next_task_id' => $next->id, 'completed_at' => now()]);
    $next->delete();
    $this->travelTo('2026-11-05 00:00:00.250000');

    app(PurgeDeletedRecords::class)->handle();

    $this->assertModelMissing($next);
    $task->refresh();
    expect($task->next_task_id)->toBeNull()
        ->and($task->updated_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-05 00:00:00.250000');
});

it('deletes a deleted task together with the deleted occurrence that followed it', function () {
    $list = TaskList::factory()->create();
    $this->travelTo('2026-10-01 00:00:00');
    $next = Task::factory()->for($list)->create();
    $task = Task::factory()->for($list)->create(['next_task_id' => $next->id, 'completed_at' => now()]);
    $next->delete();
    $task->delete();
    $this->travelTo('2026-11-05 00:00:00');

    $purged = app(PurgeDeletedRecords::class)->handle();

    expect($purged['records'])->toBe(2);
    $this->assertModelMissing($task);
    $this->assertModelMissing($next);
});

it('removes the files of a purged photo again, in case removing them failed when it was deleted', function () {
    $this->travelTo('2026-10-01 00:00:00');
    $purged = MemoryPhoto::factory()->create();
    $kept = MemoryPhoto::factory()->create();

    foreach ([$purged, $kept] as $photo) {
        Storage::disk('r2')->put($photo->path, 'foto');
        Storage::disk('r2')->put($photo->thumb_path, 'foto');
    }

    $purged->delete();
    $this->travelTo('2026-10-20 00:00:00');
    $kept->delete();
    $this->travelTo('2026-11-05 00:00:00');

    app(PurgeDeletedRecords::class)->handle();

    Storage::disk('r2')->assertMissing([$purged->path, $purged->thumb_path]);
    Storage::disk('r2')->assertExists([$kept->path, $kept->thumb_path]);
});

it('deletes more rows than fit in one chunk', function () {
    $this->travelTo('2026-10-01 00:00:00');
    $space = User::factory()->withPersonalSpace()->create()->personalSpace();
    SpecialDate::factory()->count(501)->for($space)->create()->each->delete();
    $this->travelTo('2026-11-05 00:00:00');

    $purged = app(PurgeDeletedRecords::class)->handle();

    expect($purged['records'])->toBe(501)
        ->and(SpecialDate::withTrashed()->count())->toBe(0);
});

it('deletes the reminders sent and the invitations that expired more than 30 days ago', function () {
    $this->travelTo('2026-10-31 23:59:59');
    $oldReminder = DailyReminder::query()->create(['reminder_key' => 'date:lama', 'remind_on' => '2026-11-01']);
    $this->travelTo('2026-11-01 00:00:00');
    $recentReminder = DailyReminder::query()->create(['reminder_key' => 'date:baru', 'remind_on' => '2026-11-01']);
    $oldInvitation = SpaceInvitation::factory()->create(['expires_at' => '2026-10-31 23:59:59']);
    $recentInvitation = SpaceInvitation::factory()->create(['expires_at' => '2026-11-01 00:00:00']);
    $this->travelTo('2026-12-01 00:00:00');

    $purged = app(PurgeDeletedRecords::class)->handle();

    expect($purged['reminders'])->toBe(1)->and($purged['invitations'])->toBe(1);
    $this->assertModelMissing($oldReminder);
    $this->assertModelExists($recentReminder);
    $this->assertModelMissing($oldInvitation);
    $this->assertModelExists($recentInvitation);
});

it('deletes the cache entries that already expired when the cache lives in the database', function () {
    config(['cache.default' => 'database']);
    $this->travelTo('2026-12-01 00:00:00');
    DB::table('cache')->insert([
        ['key' => 'lewat', 'value' => 'x', 'expiration' => now()->getTimestamp() - 1],
        ['key' => 'pas-habis', 'value' => 'x', 'expiration' => now()->getTimestamp()],
        ['key' => 'masih-berlaku', 'value' => 'x', 'expiration' => now()->getTimestamp() + 1],
    ]);

    $purged = app(PurgeDeletedRecords::class)->handle();

    expect($purged['cache'])->toBe(2)
        ->and(DB::table('cache')->pluck('key')->all())->toBe(['masih-berlaku']);
});

it('leaves the cache table alone when the cache lives elsewhere', function () {
    DB::table('cache')->insert(['key' => 'lewat', 'value' => 'x', 'expiration' => 0]);

    $purged = app(PurgeDeletedRecords::class)->handle();

    expect($purged['cache'])->toBe(0)
        ->and(DB::table('cache')->count())->toBe(1);
});

// A new foreign key between synced tables could make the purge fail, or
// delete a row that another one still needs, until it is handled here.
it('knows every foreign key between the synced tables', function () {
    $tables = collect(Space::CONTENT_MODELS)->map(fn (string $model): string => (new $model)->getTable());

    $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
        ->where('TABLE_SCHEMA', DB::getDatabaseName())
        ->whereIn('TABLE_NAME', $tables)
        ->whereIn('REFERENCED_TABLE_NAME', $tables)
        ->get(['TABLE_NAME as table', 'COLUMN_NAME as column', 'REFERENCED_TABLE_NAME as referenced'])
        ->map(fn (object $key): string => "{$key->table}.{$key->column} -> {$key->referenced}")
        ->sort()
        ->values()
        ->all();

    $handled = collect(PurgeDeletedRecords::BLOCKING_LINKS)
        ->flatMap(fn (array $links, string $model): array => array_map(
            fn (array $link): string => (new $link[0])->getTable().".{$link[1]} -> ".(new $model)->getTable(),
            $links,
        ))
        ->push('tasks.next_task_id -> tasks')
        ->sort()
        ->values()
        ->all();

    expect($foreignKeys)->toBe($handled);
});

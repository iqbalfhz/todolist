<?php

use App\Actions\DeleteAccount;
use App\Actions\ImportData;
use App\Jobs\NotifyMemoryCreated;
use App\Jobs\SendPushNotification;
use App\Jobs\SendSyncPush;
use App\Models\Memory;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\SpecialDate;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Build one part of an import for a space, shaped like the rows of an export
 * file whose IDs the phone has replaced with new ones.
 *
 * @return array{0: array<string, list<array<string, mixed>>>, 1: array<string, string>}
 */
function importPart(string $spaceId, string $authorId): array
{
    $ids = collect(['goal', 'trip', 'checklist', 'list', 'done', 'next', 'packing', 'deposit', 'withdrawal', 'date', 'memory'])
        ->mapWithKeys(fn (string $name): array => [$name => newId()])
        ->all();

    $rows = [
        'savings_goals' => [[
            'id' => $ids['goal'], 'space_id' => $spaceId, 'name' => 'Dana Liburan', 'emoji' => '✈️',
            'target_amount' => 1_000_000, 'currency' => 'IDR', 'target_date' => '2027-06-30',
            'reminder_frequency' => 'weekly', 'reminder_day' => 5, 'achieved_at' => null, 'archived_at' => null,
            'created_by' => $authorId, 'created_at' => '2026-09-01T08:00:00.000000Z', 'updated_at' => '2026-09-01T08:00:00.000000Z', 'deleted_at' => null,
        ]],
        'trips' => [[
            'id' => $ids['trip'], 'space_id' => $spaceId, 'title' => 'Liburan ke Bali', 'destination' => 'Bali',
            'start_date' => '2027-06-20', 'end_date' => '2027-06-25', 'notes' => null, 'savings_goal_id' => $ids['goal'],
            'created_by' => $authorId, 'created_at' => '2026-09-02T08:00:00.000000Z',
        ]],
        'task_lists' => [
            [
                'id' => $ids['checklist'], 'space_id' => $spaceId, 'trip_id' => $ids['trip'], 'name' => 'Persiapan',
                'emoji' => null, 'color' => 'toska', 'position' => 2048, 'archived_at' => null,
                'created_by' => $authorId, 'created_at' => '2026-09-02T08:00:01.000000Z',
            ],
            [
                'id' => $ids['list'], 'space_id' => $spaceId, 'trip_id' => null, 'name' => 'Belanja',
                'emoji' => '🛒', 'color' => 'mawar', 'position' => 1024, 'archived_at' => null,
                'created_by' => $authorId, 'created_at' => '2026-09-03T08:00:00.000000Z',
            ],
        ],
        'tasks' => [
            [
                'id' => $ids['done'], 'task_list_id' => $ids['list'], 'space_id' => $spaceId, 'title' => 'Beli galon', 'notes' => null,
                'due_date' => '2026-10-01', 'due_time' => '17:00', 'repeat_frequency' => 'weekly', 'repeat_interval' => 1, 'repeat_days' => null,
                'priority' => 2, 'position' => 1024, 'assignee_ids' => [$authorId], 'remind_at' => '2026-10-01T10:00:00.000000Z',
                'completed_at' => '2026-10-01T09:00:00.000000Z', 'completed_by' => $authorId, 'next_task_id' => $ids['next'],
                'created_by' => $authorId, 'updated_by' => $authorId, 'created_at' => '2026-09-30T08:00:00.000000Z',
            ],
            [
                'id' => $ids['next'], 'task_list_id' => $ids['list'], 'title' => 'Beli galon', 'notes' => null,
                'due_date' => '2027-01-08', 'due_time' => null, 'repeat_frequency' => 'weekly', 'repeat_interval' => 1, 'repeat_days' => null,
                'priority' => 2, 'position' => 2048, 'assignee_ids' => [$authorId], 'completed_at' => null, 'completed_by' => null,
                'next_task_id' => null, 'created_by' => $authorId, 'created_at' => '2026-10-01T09:00:00.000000Z',
            ],
            [
                'id' => $ids['packing'], 'task_list_id' => $ids['checklist'], 'title' => 'Paspor', 'notes' => 'Cek masa berlaku',
                'due_date' => null, 'due_time' => null, 'repeat_frequency' => null, 'repeat_interval' => null, 'repeat_days' => null,
                'priority' => 0, 'position' => 1024, 'assignee_ids' => [$authorId], 'completed_at' => null,
                'created_by' => $authorId, 'created_at' => '2026-09-02T08:00:02.000000Z',
            ],
        ],
        'savings_entries' => [
            [
                'id' => $ids['deposit'], 'savings_goal_id' => $ids['goal'], 'user_id' => $authorId, 'type' => 'deposit',
                'amount' => 600_000, 'entry_date' => '2026-09-05', 'note' => 'Gajian', 'created_at' => '2026-09-05T08:00:00.000000Z',
            ],
            [
                'id' => $ids['withdrawal'], 'savings_goal_id' => $ids['goal'], 'user_id' => $authorId, 'type' => 'withdrawal',
                'amount' => 100_000, 'entry_date' => '2026-09-06', 'note' => null, 'created_at' => '2026-09-06T08:00:00.000000Z',
            ],
        ],
        'special_dates' => [[
            'id' => $ids['date'], 'space_id' => $spaceId, 'type' => 'anniversary', 'title' => 'Hari jadi kita', 'date' => '2023-10-29',
            'created_by' => $authorId, 'created_at' => '2026-09-04T08:00:00.000000Z',
        ]],
        'memories' => [[
            'id' => $ids['memory'], 'space_id' => $spaceId, 'trip_id' => $ids['trip'], 'title' => 'Pantai Kuta', 'place_name' => 'Kuta, Bali',
            'latitude' => -8.7184921, 'longitude' => 115.1686324, 'visited_on' => '2026-08-17', 'story' => 'Senja paling bagus.',
            'created_by' => $authorId, 'created_at' => '2026-09-07T08:00:00.000000Z',
        ]],
    ];

    return [$rows, $ids];
}

/**
 * Get the number of rows each collection the import writes to holds.
 *
 * @return array<string, int>
 */
function importedRowCounts(): array
{
    return [
        'savings_goals' => SavingsGoal::withTrashed()->count(),
        'trips' => Trip::withTrashed()->count(),
        'task_lists' => TaskList::withTrashed()->count(),
        'tasks' => Task::withTrashed()->count(),
        'savings_entries' => SavingsEntry::withTrashed()->count(),
        'special_dates' => SpecialDate::withTrashed()->count(),
        'memories' => Memory::withTrashed()->count(),
    ];
}

it('saves every collection in the chosen space with its links and original times, and counts what it created', function () {
    Queue::fake([SendPushNotification::class, NotifyMemoryCreated::class]);
    $this->travelTo('2026-11-03 12:00:00.250000');
    $user = actingAsApiUser();
    $space = $user->personalSpace();
    [$rows, $ids] = importPart($space->id, $user->id);

    $response = $this->postJson('/api/v1/me/import', $rows);

    $response->assertOk()->assertExactJson(['data' => [
        'created' => ['savings_goals' => 1, 'trips' => 1, 'task_lists' => 2, 'tasks' => 3, 'savings_entries' => 2, 'special_dates' => 1, 'memories' => 1],
        'skipped' => ['savings_goals' => 0, 'trips' => 0, 'task_lists' => 0, 'tasks' => 0, 'savings_entries' => 0, 'special_dates' => 0, 'memories' => 0],
    ]]);
    $goal = SavingsGoal::query()->findOrFail($ids['goal']);
    expect($goal->space_id)->toBe($space->id)
        ->and($goal->name)->toBe('Dana Liburan')
        ->and($goal->reminder_day)->toBe(5)
        ->and($goal->created_at->format('Y-m-d H:i:s.u'))->toBe('2026-09-01 08:00:00.000000')
        ->and($goal->updated_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-03 12:00:00.250000')
        ->and($goal->achieved_at)->toBeNull();
    expect(Trip::query()->findOrFail($ids['trip'])->savings_goal_id)->toBe($ids['goal'])
        ->and(TaskList::query()->findOrFail($ids['checklist'])->trip_id)->toBe($ids['trip'])
        ->and(Memory::query()->findOrFail($ids['memory'])->trip_id)->toBe($ids['trip'])
        ->and(Memory::query()->findOrFail($ids['memory'])->latitude)->toEqual(-8.718492);
    $done = Task::query()->findOrFail($ids['done']);
    expect($done->space_id)->toBe($space->id)
        ->and($done->completed_at->toJSON())->toBe('2026-10-01T09:00:00.000000Z')
        ->and($done->completed_by)->toBe($user->id)
        ->and($done->next_task_id)->toBe($ids['next'])
        ->and($done->repeat_anchor)->toBe('2026-10-01')
        ->and($done->remind_at->toJSON())->toBe('2026-10-01T10:00:00.000000Z')
        ->and($done->assignees()->pluck('users.id')->all())->toBe([$user->id]);
    expect(Task::query()->count())->toBe(3)
        ->and(SavingsEntry::query()->findOrFail($ids['withdrawal'])->amount)->toBe(100_000);
    Queue::assertNothingPushed();
});

it('keeps the original people who are members of the shared space and puts the importer in place of the others', function () {
    Queue::fake([SendPushNotification::class]);
    [$user, $partner, $space] = actingAsPartnered();
    $stranger = User::factory()->create();
    [$rows, $ids] = importPart($space->id, $partner->id);
    $rows['tasks'][0]['assignee_ids'] = [$partner->id, $stranger->id];
    $rows['tasks'][1]['assignee_ids'] = [$stranger->id];
    $rows['special_dates'][0]['created_by'] = $stranger->id;

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    $done = Task::query()->findOrFail($ids['done']);
    expect($done->created_by)->toBe($partner->id)
        ->and($done->completed_by)->toBe($partner->id)
        ->and($done->updated_by)->toBe($user->id)
        ->and($done->assignees()->pluck('users.id')->all())->toBe([$partner->id])
        ->and(Task::query()->findOrFail($ids['next'])->assignees()->pluck('users.id')->all())->toBe([$user->id])
        ->and(SavingsEntry::query()->findOrFail($ids['deposit'])->user_id)->toBe($partner->id)
        ->and(SpecialDate::query()->findOrFail($ids['date'])->created_by)->toBe($user->id);
});

it('sends no push for the imported rows, only the sync push to the partner', function () {
    Queue::fake([SendPushNotification::class, NotifyMemoryCreated::class, SendSyncPush::class]);
    [, $partner, $space] = actingAsPartnered();
    [$rows] = importPart($space->id, $partner->id);
    $rows['savings_entries'][0]['amount'] = 2_000_000;

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    Queue::assertNotPushed(SendPushNotification::class);
    Queue::assertNotPushed(NotifyMemoryCreated::class);
    Queue::assertPushed(SendSyncPush::class, fn (SendSyncPush $job): bool => $job->userId === $partner->id && $job->spaceId === $space->id);
});

it('marks a goal that its imported entries take to the target as achieved, without a push to come', function () {
    Queue::fake([SendPushNotification::class]);
    $this->travelTo('2026-11-03 12:00:00');
    [, $partner, $space] = actingAsPartnered();
    [$rows, $ids] = importPart($space->id, $partner->id);
    $rows['savings_entries'][0]['amount'] = 1_100_000;

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    $goal = SavingsGoal::query()->findOrFail($ids['goal']);
    expect($goal->achieved_at->toJSON())->toBe('2026-11-03T12:00:00.000000Z')
        ->and($goal->achieved_notified_at)->not->toBeNull();
    Queue::assertNotPushed(SendPushNotification::class);
});

it('does not remind of a time that has passed, and reminds of the times to come', function () {
    $this->travelTo('2026-11-03 12:00:00');
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    expect(Task::query()->findOrFail($ids['done'])->reminder_sent_at->toJSON())->toBe('2026-11-03T12:00:00.000000Z')
        ->and(Task::query()->findOrFail($ids['next'])->remind_at->toJSON())->toBe('2027-01-08T01:00:00.000000Z')
        ->and(Task::query()->findOrFail($ids['next'])->reminder_sent_at)->toBeNull();
});

it('skips the rows that are in the space already, so a part sent again changes nothing', function () {
    $this->travelTo('2026-11-03 12:00:00');
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $this->postJson('/api/v1/me/import', $rows)->assertOk();
    Task::query()->findOrFail($ids['packing'])->delete();
    $before = importedRowCounts();
    $this->travelTo('2026-11-03 12:05:00');

    $response = $this->postJson('/api/v1/me/import', $rows);

    $response->assertOk()
        ->assertJsonPath('data.created', ['savings_goals' => 0, 'trips' => 0, 'task_lists' => 0, 'tasks' => 0, 'savings_entries' => 0, 'special_dates' => 0, 'memories' => 0])
        ->assertJsonPath('data.skipped', ['savings_goals' => 1, 'trips' => 1, 'task_lists' => 2, 'tasks' => 3, 'savings_entries' => 2, 'special_dates' => 1, 'memories' => 1]);
    expect(importedRowCounts())->toBe($before)
        ->and(SavingsGoal::query()->findOrFail($ids['goal'])->updated_at->toDateTimeString())->toBe('2026-11-03 12:00:00')
        ->and(Task::withTrashed()->findOrFail($ids['packing'])->trashed())->toBeTrue();
});

it('points to the rows that an earlier part saved', function () {
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $this->postJson('/api/v1/me/import', ['savings_goals' => $rows['savings_goals'], 'trips' => $rows['trips'], 'task_lists' => $rows['task_lists']])->assertOk();

    $response = $this->postJson('/api/v1/me/import', [
        'tasks' => $rows['tasks'],
        'savings_entries' => $rows['savings_entries'],
        'memories' => $rows['memories'],
    ]);

    $response->assertOk()->assertJsonPath('data.created.tasks', 3)->assertJsonPath('data.created.savings_entries', 2);
    expect(Task::query()->findOrFail($ids['packing'])->task_list_id)->toBe($ids['checklist'])
        ->and(Memory::query()->findOrFail($ids['memory'])->trip_id)->toBe($ids['trip']);
});

it('saves tasks in a list that is archived', function () {
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $rows['task_lists'][1]['archived_at'] = '2026-10-15T08:00:00.000000Z';

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    expect(TaskList::query()->findOrFail($ids['list'])->archived_at->toJSON())->toBe('2026-10-15T08:00:00.000000Z')
        ->and(Task::query()->findOrFail($ids['done'])->task_list_id)->toBe($ids['list']);
});

it('leaves a link empty when the row it points to is not in the space', function (Closure $missingId) {
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $rows['trips'][0]['savings_goal_id'] = $missingId();
    $rows['memories'][0]['trip_id'] = $missingId();
    $rows['tasks'][0]['next_task_id'] = $missingId();

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    expect(Trip::query()->findOrFail($ids['trip'])->savings_goal_id)->toBeNull()
        ->and(Memory::query()->findOrFail($ids['memory'])->trip_id)->toBeNull()
        ->and(Task::query()->findOrFail($ids['done'])->next_task_id)->toBeNull();
})->with([
    'an ID that does not exist' => [fn () => newId()],
    'a row of another couple' => [fn () => Task::factory()->create()->id],
]);

it('leaves a link empty when the row it points to is in the importer\'s other space', function () {
    [$user, , $sharedSpace] = actingAsPartnered();
    $personalSpace = $user->personalSpace();
    $goal = SavingsGoal::factory()->for($personalSpace)->create();
    $trip = Trip::factory()->for($personalSpace)->create();
    $task = Task::factory()->for(TaskList::factory()->for($personalSpace))->create();
    [$rows, $ids] = importPart($sharedSpace->id, $user->id);
    $rows['trips'][0]['savings_goal_id'] = $goal->id;
    $rows['memories'][0]['trip_id'] = $trip->id;
    $rows['task_lists'][1]['trip_id'] = $trip->id;
    $rows['tasks'][0]['next_task_id'] = $task->id;

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    expect(Trip::query()->findOrFail($ids['trip'])->savings_goal_id)->toBeNull()
        ->and(Memory::query()->findOrFail($ids['memory'])->trip_id)->toBeNull()
        ->and(TaskList::query()->findOrFail($ids['list'])->trip_id)->toBeNull()
        ->and(Task::query()->findOrFail($ids['done'])->next_task_id)->toBeNull();
});

it('links a next task once, and never a task to itself', function () {
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $rows['tasks'][2]['next_task_id'] = $ids['packing'];
    $rows['tasks'][] = [...$rows['tasks'][2], 'id' => $secondId = newId(), 'next_task_id' => $ids['next']];

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    expect(Task::query()->findOrFail($ids['done'])->next_task_id)->toBe($ids['next'])
        ->and(Task::query()->findOrFail($secondId)->next_task_id)->toBeNull()
        ->and(Task::query()->findOrFail($ids['packing'])->next_task_id)->toBeNull();
});

it('returns 422 for entries of a goal that was deleted after an earlier part saved it', function () {
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $this->postJson('/api/v1/me/import', ['savings_goals' => $rows['savings_goals']])->assertOk();
    SavingsGoal::query()->findOrFail($ids['goal'])->delete();

    $response = $this->postJson('/api/v1/me/import', ['savings_goals' => $rows['savings_goals'], 'savings_entries' => $rows['savings_entries']]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['savings_entries.0.savings_goal_id']);
    $this->assertDatabaseCount('savings_entries', 0);
});

it('clears the achievement of a goal when imported withdrawals take its total below the target', function () {
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $rows['savings_entries'][0]['amount'] = 1_000_000;
    $this->postJson('/api/v1/me/import', ['savings_goals' => $rows['savings_goals'], 'savings_entries' => [$rows['savings_entries'][0]]])->assertOk();
    expect(SavingsGoal::query()->findOrFail($ids['goal'])->achieved_at)->not->toBeNull();

    $this->postJson('/api/v1/me/import', ['savings_entries' => [$rows['savings_entries'][1]]])->assertOk();

    expect(SavingsGoal::query()->findOrFail($ids['goal'])->achieved_at)->toBeNull();
});

it('turns a checklist into a plain list when its trip is missing or has a checklist already', function (Closure $tripId) {
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $rows['task_lists'][1]['trip_id'] = $tripId($rows);

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    expect(TaskList::query()->findOrFail($ids['list'])->trip_id)->toBeNull()
        ->and(TaskList::query()->findOrFail($ids['checklist'])->trip_id)->toBe($ids['trip']);
})->with([
    'a trip that does not exist' => [fn (array $rows) => newId()],
    'the trip of the other checklist in the part' => [fn (array $rows) => $rows['trips'][0]['id']],
]);

it('keeps a creation time from the future at the time of the import', function () {
    $this->travelTo('2026-11-03 12:00:00');
    $user = actingAsApiUser();
    [$rows, $ids] = importPart($user->personalSpace()->id, $user->id);
    $rows['special_dates'][0]['created_at'] = '2030-01-01T00:00:00Z';

    $this->postJson('/api/v1/me/import', $rows)->assertOk();

    expect(SpecialDate::query()->findOrFail($ids['date'])->created_at->toDateTimeString())->toBe('2026-11-03 12:00:00');
});

it('returns 422 with the place of every invalid row and saves nothing', function () {
    $user = actingAsApiUser();
    [$rows] = importPart($user->personalSpace()->id, $user->id);
    $rows['tasks'][1]['title'] = '';
    $rows['memories'][0]['longitude'] = null;
    $before = importedRowCounts();

    $response = $this->postJson('/api/v1/me/import', $rows);

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors', [
            'tasks.1.title' => ['Judul wajib diisi.'],
            'memories.0.longitude' => ['Garis bujur wajib diisi bila terdapat garis lintang.'],
        ]);
    expect(importedRowCounts())->toBe($before);
});

it('returns 422 and saves nothing when a check shared with PUT fails', function (Closure $change, string $field) {
    $user = actingAsApiUser();
    [$rows] = importPart($user->personalSpace()->id, $user->id);
    $rows = $change($rows);
    $before = importedRowCounts();

    $response = $this->postJson('/api/v1/me/import', $rows);

    $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
    expect(importedRowCounts())->toBe($before);
})->with([
    'a repeat rule in a checklist' => [
        fn (array $rows) => array_replace_recursive($rows, ['tasks' => [2 => ['due_date' => '2027-06-01', 'repeat_frequency' => 'daily']]]),
        'tasks.2.repeat_frequency',
    ],
    'repeat days for a daily task' => [
        fn (array $rows) => array_replace_recursive($rows, ['tasks' => [1 => ['repeat_frequency' => 'daily', 'repeat_days' => [1, 3]]]]),
        'tasks.1.repeat_days',
    ],
    'a trip that ends before it starts' => [
        fn (array $rows) => array_replace_recursive($rows, ['trips' => [0 => ['end_date' => '2027-06-01']]]),
        'trips.0.end_date',
    ],
    'a deposit reminder without a day' => [
        fn (array $rows) => array_replace_recursive($rows, ['savings_goals' => [0 => ['reminder_day' => null]]]),
        'savings_goals.0.reminder_day',
    ],
]);

it('returns 422 at the space of a row whose space is not one of the importer\'s', function (Closure $space) {
    [$user, $partner] = actingAsPartnered();
    [$rows] = importPart($space($partner), $user->id);

    $response = $this->postJson('/api/v1/me/import', ['special_dates' => $rows['special_dates']]);

    $response->assertUnprocessable()->assertJsonPath('errors', ['special_dates.0.space_id' => ['Ruang ini tidak ditemukan.']]);
    $this->assertDatabaseCount('special_dates', 0);
})->with([
    'the personal space of the partner' => [fn (User $partner) => $partner->personalSpace()->id],
    'a space of another couple' => [fn (User $partner) => User::factory()->withPersonalSpace()->create()->personalSpace()->id],
    'a space that does not exist' => [fn (User $partner) => newId()],
]);

it('returns 422 at the id of a row whose ID belongs to another space', function () {
    $user = actingAsApiUser();
    $elsewhere = SpecialDate::factory()->create();
    [$rows] = importPart($user->personalSpace()->id, $user->id);
    $rows['special_dates'][0]['id'] = $elsewhere->id;

    $response = $this->postJson('/api/v1/me/import', $rows);

    $response->assertUnprocessable()->assertJsonPath('errors', ['special_dates.0.id' => ['ID ini sudah dipakai data lain.']]);
    expect(SpecialDate::query()->count())->toBe(1);
});

it('returns 422 for a task whose list or an entry whose goal is not in the importer\'s spaces', function (string $collection, string $field, Closure $parent, string $message) {
    $user = actingAsApiUser();
    [$rows] = importPart($user->personalSpace()->id, $user->id);

    $response = $this->postJson('/api/v1/me/import', [$collection => [[...$rows[$collection][0], $field => $parent($user)]]]);

    $response->assertUnprocessable()->assertJsonPath('errors', ["{$collection}.0.{$field}" => [$message]]);
})->with([
    'a list that does not exist' => ['tasks', 'task_list_id', fn () => newId(), 'Daftar untuk tugas ini tidak ditemukan.'],
    'a list that was deleted' => ['tasks', 'task_list_id', fn (User $user) => tap(TaskList::factory()->for($user->personalSpace())->create())->delete()->id, 'Daftar untuk tugas ini tidak ditemukan.'],
    'a list of another couple' => ['tasks', 'task_list_id', fn () => TaskList::factory()->create()->id, 'Daftar untuk tugas ini tidak ditemukan.'],
    'a goal that does not exist' => ['savings_entries', 'savings_goal_id', fn () => newId(), 'Target untuk setoran ini tidak ditemukan.'],
    'a goal that was deleted' => ['savings_entries', 'savings_goal_id', fn (User $user) => tap(SavingsGoal::factory()->for($user->personalSpace())->create())->delete()->id, 'Target untuk setoran ini tidak ditemukan.'],
]);

it('returns 422 for more than 1000 rows in one part', function () {
    actingAsApiUser();

    $response = $this->postJson('/api/v1/me/import', [
        'tasks' => array_fill(0, 600, ['id' => 'x']),
        'memories' => array_fill(0, 401, ['id' => 'x']),
    ]);

    $response->assertUnprocessable()->assertJsonPath('errors.rows', ['Paling banyak 1.000 baris per permintaan.']);
});

it('returns 422 when a collection is not a list of rows', function (mixed $tasks, string $field) {
    actingAsApiUser();

    $response = $this->postJson('/api/v1/me/import', ['tasks' => $tasks]);

    $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
})->with([
    'text' => ['bukan daftar', 'tasks'],
    'rows keyed by name' => [['pertama' => ['id' => '01m4fa0q20h7x3k9ws2bt5yn8e']], 'tasks'],
    'a row that is text' => [['bukan baris'], 'tasks.0'],
]);

it('allows 30 parts per hour for one account', function () {
    $this->travelTo('2026-11-03 12:00:00');
    actingAsApiUser();

    foreach (range(1, 30) as $part) {
        $this->postJson('/api/v1/me/import', [])->assertOk();
    }

    $this->postJson('/api/v1/me/import', [])->assertTooManyRequests();

    $this->travelTo('2026-11-03 13:00:01');
    $this->postJson('/api/v1/me/import', [])->assertOk();
});

it('takes the rows of an export file as they are, so a deleted account can be brought back into a new one', function () {
    Storage::fake('r2');
    Queue::fake([SendPushNotification::class]);
    $this->travelTo('2026-11-03 12:00:00');
    $old = actingAsApiUser();
    $space = $old->personalSpace();
    $goal = SavingsGoal::factory()->for($space)->create(['currency' => 'USD', 'target_amount' => 100_000, 'reminder_frequency' => 'monthly', 'reminder_day' => 31]);
    SavingsEntry::factory()->for($goal)->create(['amount' => 5_050]);
    SavingsEntry::factory()->for($goal)->withdrawal()->create(['amount' => 1_000]);
    $trip = Trip::factory()->for($space)->create(['savings_goal_id' => $goal->id]);
    $checklist = TaskList::factory()->for($space)->create(['trip_id' => $trip->id, 'name' => 'Persiapan']);
    Task::factory()->for($checklist)->create(['title' => 'Paspor']);
    $list = TaskList::factory()->for($space)->archived()->create(['name' => 'Belanja', 'emoji' => '🛒']);
    $next = Task::factory()->for($list)->create(['due_date' => '2026-11-12', 'repeat_frequency' => 'weekly', 'repeat_interval' => 2, 'repeat_days' => [2, 5], 'repeat_anchor' => '2026-11-12']);
    Task::factory()->for($list)->completed()->create(['due_date' => '2026-10-29', 'due_time' => '17:00', 'priority' => 3, 'notes' => "Dua galon\nyang besar", 'repeat_frequency' => 'weekly', 'repeat_interval' => 2, 'repeat_days' => [2, 5], 'repeat_anchor' => '2026-10-29', 'next_task_id' => $next->id]);
    SpecialDate::factory()->for($space)->create(['date' => '--02-29']);
    Memory::factory()->for($space)->create(['trip_id' => $trip->id, 'latitude' => -8.506853, 'longitude' => 115.262477, 'story' => 'Hujan, tapi sawahnya hijau.']);
    $export = $this->getJson('/api/v1/me/export')->assertOk()->json();
    app(DeleteAccount::class)->handle($old);
    $new = actingAsApiUser();
    $collections = collect(ImportData::COLLECTIONS)->mapWithKeys(fn (string $collection): array => [
        $collection => array_map(
            fn (array $row): array => array_key_exists('space_id', $row) ? [...$row, 'space_id' => $new->personalSpace()->id] : $row,
            $export[$collection],
        ),
    ]);

    $response = $this->postJson('/api/v1/me/import', $collections->all());

    $response->assertOk()->assertJsonPath('data.created', $collections->map(fn (array $rows): int => count($rows))->all());
    expect(Task::query()->where('title', 'Paspor')->sole()->task_list_id)->toBe($checklist->id)
        ->and(TaskList::query()->findOrFail($list->id)->archived_at)->not->toBeNull()
        ->and(Task::query()->whereKey($next->id)->sole()->repeat_days)->toBe([2, 5])
        ->and(Task::query()->where('next_task_id', $next->id)->sole()->created_by)->toBe($new->id)
        ->and(Memory::query()->sole()->trip_id)->toBe($trip->id)
        ->and(SpecialDate::query()->sole()->date)->toBe('--02-29');
});

it('returns 401 when no token is sent', function () {
    $this->postJson('/api/v1/me/import', [])->assertUnauthorized();
});

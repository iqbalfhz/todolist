<?php

use App\Actions\UpdateProfile;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use App\Rules\SyncCursor;

dataset('content rows', [
    'a list' => ['task_lists', fn (Space $space) => TaskList::factory()->for($space)->create()],
    'a task' => ['tasks', fn (Space $space) => Task::factory()->for(TaskList::factory()->for($space))->create()],
    'a goal' => ['savings_goals', fn (Space $space) => SavingsGoal::factory()->for($space)->create()],
    'a deposit' => ['savings_entries', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->create()],
]);

it('returns everything a new account has in the shape of the contract', function () {
    $this->travelTo('2026-10-08 11:10:05.123456');
    $user = actingAsApiUser();
    $space = $user->personalSpace();
    $list = $space->taskLists()->sole();

    $response = $this->getJson('/api/v1/sync');

    $response->assertOk()->assertExactJson([
        'data' => [
            'spaces' => [
                [
                    'id' => $space->id,
                    'name' => 'Ruang Pribadi',
                    'type' => 'personal',
                    'timezone' => 'Asia/Jakarta',
                    'owner_id' => $user->id,
                    'members' => [
                        [
                            'id' => $user->id,
                            'name' => $user->name,
                            'color' => 'mawar',
                            'avatar_url' => null,
                            'avatar_version' => 0,
                            'role' => 'owner',
                            'joined_at' => '2026-10-08T11:10:05.123456Z',
                        ],
                    ],
                    'created_at' => '2026-10-08T11:10:05.123456Z',
                    'updated_at' => '2026-10-08T11:10:05.123456Z',
                    'deleted_at' => null,
                ],
            ],
            'task_lists' => [
                [
                    'id' => $list->id,
                    'space_id' => $space->id,
                    'name' => 'Tugas',
                    'emoji' => null,
                    'color' => 'mawar',
                    'position' => 1024,
                    'archived_at' => null,
                    'created_by' => $user->id,
                    'created_at' => '2026-10-08T11:10:05.123456Z',
                    'updated_at' => '2026-10-08T11:10:05.123456Z',
                    'deleted_at' => null,
                ],
            ],
            'tasks' => [],
            'savings_goals' => [],
            'savings_entries' => [],
        ],
        'meta' => [
            'server_time' => '2026-10-08T11:10:05.123456Z',
            'has_more' => false,
            'next_cursor' => null,
        ],
    ]);
});

it('returns each row exactly as its own endpoint returned it', function () {
    $user = actingAsApiUser();
    $space = $user->personalSpace();
    $task = $this->putJson('/api/v1/tasks/'.newId(), [
        'task_list_id' => $space->taskLists()->sole()->id,
        'title' => 'Beli tiket',
        'due_date' => '2026-10-09',
        'due_time' => '17:00',
        'position' => 1024,
    ])->json('data');
    $goal = $this->putJson('/api/v1/savings-goals/'.newId(), [
        'space_id' => $space->id,
        'name' => 'Dana Liburan',
        'target_amount' => 10000000,
        'currency' => 'IDR',
    ])->json('data');
    $entry = $this->putJson('/api/v1/savings-entries/'.newId(), [
        'savings_goal_id' => $goal['id'],
        'amount' => 200000,
        'entry_date' => '2026-10-08',
    ])->json('data');

    $response = $this->getJson('/api/v1/sync');

    $response->assertOk()
        ->assertJsonPath('data.tasks', [$task])
        ->assertJsonPath('data.savings_goals', [$goal])
        ->assertJsonPath('data.savings_entries', [$entry]);
});

it('orders each collection by updated_at and then by ID', function () {
    $this->travelTo('2026-10-08 09:00:00');
    $user = actingAsApiUser();
    $space = $user->personalSpace();
    $first = $space->taskLists()->sole();
    $this->travelTo('2026-10-08 10:00:05');
    $last = TaskList::factory()->for($space)->create();
    $this->travelTo('2026-10-08 10:00:01');
    $second = TaskList::factory()->for($space)->create();
    $third = TaskList::factory()->for($space)->create();

    $response = $this->getJson('/api/v1/sync');

    $response->assertOk()->assertJsonPath('data.task_lists.*.id', [$first->id, $second->id, $third->id, $last->id]);
});

it('still sends archived lists and goals in a sync without since', function () {
    $user = actingAsApiUser();
    $list = TaskList::factory()->for($user->personalSpace())->archived()->create();
    $goal = SavingsGoal::factory()->for($user->personalSpace())->archived()->create();

    $response = $this->getJson('/api/v1/sync');

    $response->assertOk()
        ->assertJsonPath('data.task_lists.1.id', $list->id)
        ->assertJsonPath('data.savings_goals.0.id', $goal->id);
});

it('leaves deleted rows out of a sync without since', function (string $collection, Closure $create) {
    $user = actingAsApiUser();
    $deleted = $create($user->personalSpace());
    $deleted->delete();

    $response = $this->getJson('/api/v1/sync');

    $response->assertOk();
    expect($response->json("data.{$collection}.*.id"))->not->toContain($deleted->id);
})->with('content rows');

it('returns rows deleted since the last sync with deleted_at filled', function (string $collection, Closure $create) {
    $this->travelTo('2026-10-08 10:00:00');
    $user = actingAsApiUser();
    $deleted = $create($user->personalSpace());
    $this->travelTo('2026-10-08 11:00:00.250000');
    $deleted->delete();

    $response = $this->getJson('/api/v1/sync?'.http_build_query(['since' => '2026-10-08T10:30:00.000000Z']));

    $response->assertOk()
        ->assertJsonCount(1, "data.{$collection}")
        ->assertJsonPath("data.{$collection}.0.id", $deleted->id)
        ->assertJsonPath("data.{$collection}.0.deleted_at", '2026-10-08T11:00:00.250000Z');
})->with('content rows');

it('returns only what changed in the shared space since the last sync', function () {
    $this->travelTo('2026-10-08 10:00:00');
    [, $partner, $sharedSpace] = actingAsPartnered();
    $list = TaskList::factory()->for($sharedSpace)->create();
    Task::factory()->for($list)->create();
    $this->travelTo('2026-10-08 11:00:00');
    $added = Task::factory()->for($list)->create(['created_by' => $partner->id]);

    $response = $this->getJson('/api/v1/sync?'.http_build_query(['since' => '2026-10-08T10:30:00Z']));

    $response->assertOk()
        ->assertJsonPath('data.tasks.*.id', [$added->id])
        ->assertJsonCount(0, 'data.task_lists')
        ->assertJsonCount(0, 'data.spaces')
        ->assertJsonPath('meta.has_more', false)
        ->assertJsonPath('meta.next_cursor', null);
});

it('sends the shared space again when the partner changes their profile', function () {
    $this->travelTo('2026-10-08 10:00:00');
    [, $partner, $sharedSpace] = actingAsPartnered();
    $this->travelTo('2026-10-08 11:00:00');
    app(UpdateProfile::class)->handle($partner, ['name' => 'Nama Baru']);

    $response = $this->getJson('/api/v1/sync?'.http_build_query(['since' => '2026-10-08T10:30:00Z']));

    $response->assertOk()
        ->assertJsonPath('data.spaces.*.id', [$sharedSpace->id])
        ->assertJsonPath('data.spaces.0.members.1.name', 'Nama Baru');
});

it('reaches back 5 seconds before since', function (string $now, string $since, int $lists) {
    $this->travelTo('2026-10-08 10:00:00');
    actingAsApiUser();
    $this->travelTo($now);

    $response = $this->getJson('/api/v1/sync?'.http_build_query(['since' => $since]));

    $response->assertOk()->assertJsonCount($lists, 'data.task_lists');
})->with([
    'a list changed exactly 5 seconds before since is sent' => ['2026-10-08 10:01:00', '2026-10-08T10:00:05.000000Z', 1],
    'a list changed a microsecond earlier is not' => ['2026-10-08 10:01:00', '2026-10-08T10:00:05.000001Z', 0],
    'a since with an offset is read as the same moment' => ['2026-10-08 10:01:00', '2026-10-08T17:00:05+07:00', 1],
    'a since ahead of the server clock counts as now' => ['2026-10-08 10:00:04', '2030-01-01T00:00:00Z', 1],
]);

it('returns every row for a since older than the oldest row', function (string $since) {
    actingAsApiUser();

    $response = $this->getJson('/api/v1/sync?'.http_build_query(['since' => $since]));

    $response->assertOk()->assertJsonCount(1, 'data.spaces')->assertJsonCount(1, 'data.task_lists');
})->with([
    'the Unix epoch' => ['1970-01-01T00:00:00Z'],
    'the year 0000' => ['0000-01-01T00:00:00Z'],
]);

it('never sends the content of the personal space of the partner', function (array $query) {
    $this->travelTo('2026-10-08 10:00:00');
    [, $partner, $sharedSpace] = actingAsPartnered();
    $privateSpace = $partner->personalSpace();
    Task::factory()->for($privateSpace->taskLists()->sole())->create();
    SavingsEntry::factory()->for(SavingsGoal::factory()->for($privateSpace))->create();

    $response = $this->getJson('/api/v1/sync?'.http_build_query($query));

    $response->assertOk()->assertJsonPath('data.spaces.1.id', $sharedSpace->id);
    expect($response->getContent())->not->toContain($privateSpace->id);
})->with([
    'in a sync without since' => [[]],
    'in a sync with since' => [['since' => '2026-10-08T09:00:00Z']],
]);

it('leaves out the spaces and rows of other couples', function () {
    actingAsApiUser();
    $strangerSpace = sharedSpaceBetween(User::factory()->withPersonalSpace()->create(), User::factory()->create());
    Task::factory()->for(TaskList::factory()->for($strangerSpace))->create();
    SavingsEntry::factory()->for(SavingsGoal::factory()->for($strangerSpace))->create();

    $response = $this->getJson('/api/v1/sync');

    $response->assertOk()
        ->assertJsonCount(1, 'data.spaces')
        ->assertJsonCount(1, 'data.task_lists')
        ->assertJsonCount(0, 'data.tasks')
        ->assertJsonCount(0, 'data.savings_goals')
        ->assertJsonCount(0, 'data.savings_entries');
});

it('pages through rows that share one updated_at without repeating or skipping any', function () {
    $this->travelTo('2026-10-08 10:00:00');
    $user = actingAsApiUser();
    $space = $user->personalSpace();
    TaskList::factory()->count(600)->for($space)->create(['created_by' => $user->id]);
    $ids = $space->taskLists()->pluck('id')->sort()->values()->all();

    $firstPage = $this->getJson('/api/v1/sync');
    $secondPage = $this->getJson('/api/v1/sync?'.http_build_query(['cursor' => $firstPage->json('meta.next_cursor')]));

    $firstPage->assertOk()->assertJsonCount(500, 'data.task_lists')->assertJsonPath('meta.has_more', true);
    $secondPage->assertOk()->assertJsonCount(101, 'data.task_lists')->assertJsonPath('meta.has_more', false);
    expect([...$firstPage->json('data.task_lists.*.id'), ...$secondPage->json('data.task_lists.*.id')])->toBe($ids);
});

it('continues every collection from where the previous page stopped, with the same server time and mode', function () {
    $this->travelTo('2026-10-08 10:00:00');
    $user = actingAsApiUser();
    $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
    SavingsEntry::factory()->count(501)->for($goal)->create(['space_id' => $goal->space_id, 'user_id' => $user->id]);
    $goal->entries()->delete();
    $firstPage = $this->getJson('/api/v1/sync?'.http_build_query(['since' => '2026-10-08T09:00:00Z']));
    $this->travelTo('2026-10-08 10:00:30');

    $secondPage = $this->getJson('/api/v1/sync?'.http_build_query(['cursor' => $firstPage->json('meta.next_cursor')]));

    $secondPage->assertOk()
        ->assertJsonCount(1, 'data.savings_entries')
        ->assertJsonPath('data.savings_entries.0.deleted_at', '2026-10-08T10:00:00.000000Z')
        ->assertJsonCount(0, 'data.savings_goals')
        ->assertJsonCount(0, 'data.task_lists')
        ->assertJsonCount(0, 'data.spaces')
        ->assertJsonPath('meta.server_time', '2026-10-08T10:00:00.000000Z')
        ->assertJsonPath('meta.has_more', false)
        ->assertJsonPath('meta.next_cursor', null);
});

it('does not read since when a cursor is sent', function () {
    actingAsApiUser();
    $cursor = SyncCursor::encode([
        'server_time' => '2026-10-08 10:00:00.000000',
        'with_deleted' => false,
        'positions' => array_fill_keys(SyncCursor::COLLECTIONS, null),
    ]);

    $response = $this->getJson('/api/v1/sync?'.http_build_query(['cursor' => $cursor, 'since' => 'kemarin']));

    $response->assertOk()->assertJsonPath('meta.server_time', '2026-10-08T10:00:00.000000Z');
});

it('returns 422 with the validation_failed code for an invalid since or cursor', function (array $query, string $field, string $message) {
    actingAsApiUser();

    $response = $this->getJson('/api/v1/sync?'.http_build_query($query));

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath("errors.{$field}.0", $message)
        ->assertJsonMissingPath('current');
})->with([
    'a since without Z or an offset' => [['since' => '2026-10-08T10:00:00'], 'since', 'Since harus berupa waktu ISO 8601 dengan Z atau offset.'],
    'a since that is not a time' => [['since' => 'kemarin'], 'since', 'Since harus berupa waktu ISO 8601 dengan Z atau offset.'],
    'an empty since' => [['since' => ''], 'since', 'Since wajib diisi.'],
    'an empty cursor' => [['cursor' => ''], 'cursor', 'Cursor wajib diisi.'],
    'a cursor that is not base64' => [['cursor' => '!!!'], 'cursor', 'Cursor tidak berlaku, mulai sinkron dari awal.'],
    'a cursor that is not JSON' => [['cursor' => base64_encode('halo')], 'cursor', 'Cursor tidak berlaku, mulai sinkron dari awal.'],
    'a cursor with another shape' => [['cursor' => base64_encode('{"halo":"dunia"}')], 'cursor', 'Cursor tidak berlaku, mulai sinkron dari awal.'],
    'a cursor without one of the collections' => [
        ['cursor' => base64_encode(json_encode([
            'server_time' => '2026-10-08 10:00:00.000000',
            'with_deleted' => false,
            'positions' => ['spaces' => null, 'task_lists' => null, 'tasks' => null, 'savings_goals' => null],
        ]))],
        'cursor',
        'Cursor tidak berlaku, mulai sinkron dari awal.',
    ],
    'a cursor with a time that does not exist' => [
        ['cursor' => base64_encode(json_encode([
            'server_time' => '2026-02-30 10:00:00.000000',
            'with_deleted' => false,
            'positions' => ['spaces' => null, 'task_lists' => null, 'tasks' => null, 'savings_goals' => null, 'savings_entries' => null],
        ]))],
        'cursor',
        'Cursor tidak berlaku, mulai sinkron dari awal.',
    ],
    'a cursor with an uppercase ID' => [
        ['cursor' => base64_encode(json_encode([
            'server_time' => '2026-10-08 10:00:00.000000',
            'with_deleted' => true,
            'positions' => [
                'spaces' => ['2026-10-08 09:59:55.000000', ''],
                'task_lists' => ['2026-10-08 09:59:55.000000', ''],
                'tasks' => ['2026-10-08 10:00:00.000000', '01M4DG5JEGV6SP4XAV8EFQZCQE'],
                'savings_goals' => ['2026-10-08 09:59:55.000000', ''],
                'savings_entries' => ['2026-10-08 09:59:55.000000', ''],
            ],
        ]))],
        'cursor',
        'Cursor tidak berlaku, mulai sinkron dari awal.',
    ],
]);

it('returns 401 when no token is sent', function () {
    $response = $this->getJson('/api/v1/sync');

    $response->assertUnauthorized();
});

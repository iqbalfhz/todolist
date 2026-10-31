<?php

use App\Models\Comment;
use App\Models\DeviceToken;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Reaction;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\SpecialDate;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use App\Rules\SyncCursor;
use Illuminate\Support\Facades\Storage;

it('returns one JSON file with the account, its devices, and every collection', function () {
    $this->travelTo('2026-10-31 08:00:00');
    $user = actingAsApiUser();
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-rahasia', 'platform' => 'android']);

    $response = $this->getJson('/api/v1/me/export');

    $response->assertOk()
        ->assertJsonPath('app', 'Denly Days')
        ->assertJsonPath('format_version', 1)
        ->assertJsonPath('exported_at', '2026-10-31T08:00:00.000000Z')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('devices', [[
            'platform' => 'android',
            'created_at' => '2026-10-31T08:00:00.000000Z',
            'updated_at' => '2026-10-31T08:00:00.000000Z',
        ]]);
    expect(array_keys($response->json()))->toBe(['app', 'format_version', 'exported_at', 'user', 'devices', ...SyncCursor::COLLECTIONS])
        ->and($response->getContent())->not->toContain('fcm-rahasia')
        ->not->toContain('"password"');
});

it('names the file after the day in the timezone of the account', function (string $timezone, string $filename) {
    $this->travelTo('2026-10-31 18:00:00');
    $user = actingAsApiUser();
    $user->update(['timezone' => $timezone]);

    $response = $this->getJson('/api/v1/me/export');

    $response->assertOk()->assertHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
})->with([
    'already the next day in Jakarta' => ['Asia/Jakarta', 'denly-days-2026-11-01.json'],
    'still the same day in UTC' => ['UTC', 'denly-days-2026-10-31.json'],
]);

it('sends every row the way a full sync sends it, the partner\'s rows in the shared space included', function () {
    Storage::fake('r2');
    $this->travelTo('2026-10-31 08:00:00');
    [$user, $partner, $sharedSpace] = actingAsPartnered();
    $list = TaskList::factory()->for($sharedSpace)->create(['created_by' => $partner->id]);
    $task = Task::factory()->for($list)->hasAttached($user, [], 'assignees')->create(['created_by' => $partner->id]);
    SavingsEntry::factory()->for(SavingsGoal::factory()->for($sharedSpace))->create();
    SpecialDate::factory()->for($sharedSpace)->create();
    Trip::factory()->for($sharedSpace)->create();
    MemoryPhoto::factory()->for(Memory::factory()->for($sharedSpace))->create();
    Comment::factory()->on($task)->create(['user_id' => $partner->id]);
    Reaction::factory()->on($task)->create(['user_id' => $user->id]);
    Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
    $sync = $this->getJson('/api/v1/sync')->assertOk()->json('data');

    $response = $this->getJson('/api/v1/me/export');

    $response->assertOk();
    foreach (SyncCursor::COLLECTIONS as $collection) {
        $byId = fn (array $rows): array => collect($rows)->sortBy('id')->values()->all();

        expect($byId($response->json($collection)))->toBe($byId($sync[$collection]))
            ->and($response->json($collection))->not->toBeEmpty();
    }
});

it('leaves out what the app does not show', function (Closure $arrange, string $collection) {
    Storage::fake('r2');
    [$user, $partner] = actingAsPartnered();
    $hidden = $arrange($user, $partner);

    $response = $this->getJson('/api/v1/me/export');

    $response->assertOk();
    expect($response->json("{$collection}.*.id"))->not->toContain($hidden->id);
})->with([
    'a deleted task' => [function (User $user) {
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
        $task->delete();

        return $task;
    }, 'tasks'],
    'the personal space of the partner' => [fn (User $user, User $partner) => $partner->personalSpace(), 'spaces'],
    'a task in the personal space of the partner' => [fn (User $user, User $partner) => Task::factory()->for($partner->personalSpace()->taskLists()->sole())->create(), 'tasks'],
    'a shared space that was closed' => [function (User $user) {
        $closedSpace = sharedSpaceBetween($user, User::factory()->create());
        $closedSpace->delete();

        return $closedSpace;
    }, 'spaces'],
    'a list in a shared space that was closed' => [function (User $user) {
        $closedSpace = sharedSpaceBetween($user, User::factory()->create());
        $list = TaskList::factory()->for($closedSpace)->create();
        $closedSpace->delete();

        return $list;
    }, 'task_lists'],
]);

it('returns 429 after five exports by the same account within an hour', function () {
    actingAsApiUser();
    foreach (range(1, 5) as $attempt) {
        $this->getJson('/api/v1/me/export')->assertOk();
    }

    $response = $this->getJson('/api/v1/me/export');

    $response->assertTooManyRequests()->assertJsonPath('code', 'too_many_requests');
});

it('returns 401 when no token is sent', function () {
    $response = $this->getJson('/api/v1/me/export');

    $response->assertUnauthorized();
});

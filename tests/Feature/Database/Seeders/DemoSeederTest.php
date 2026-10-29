<?php

use App\Models\SavingsGoal;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoSeeder;

it('creates two accounts that can sign in to the app with the default password', function (string $email) {
    $this->seed(DemoSeeder::class);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => 'password',
        'device_name' => 'Pixel 9',
    ]);

    $response->assertOk()->assertJsonPath('data.user.email', $email);
})->with([
    'the one who invited' => ['aku@contoh.test'],
    'the partner' => ['pasangan@contoh.test'],
]);

it('pairs the two accounts through an invitation that the partner accepted', function () {
    $this->seed(DemoSeeder::class);

    $user = User::query()->where('email', 'aku@contoh.test')->sole();
    $partner = User::query()->where('email', 'pasangan@contoh.test')->sole();
    $sharedSpace = $user->sharedSpace();
    expect($sharedSpace->owner_id)->toBe($user->id)
        ->and($partner->sharedSpace()->id)->toBe($sharedSpace->id);
    $this->assertDatabaseHas('space_invitations', [
        'created_by' => $user->id,
        'accepted_by' => $partner->id,
        'space_id' => $sharedSpace->id,
    ]);
});

it('gives the first sync of the app spaces, lists, tasks, goals, deposits, comments, and reactions to show', function () {
    $this->seed(DemoSeeder::class);
    $user = User::query()->where('email', 'aku@contoh.test')->sole();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/sync');

    $response->assertOk()->assertJsonCount(2, 'data.spaces');
    expect($response->json('data.task_lists'))->not->toBeEmpty()
        ->and($response->json('data.tasks'))->not->toBeEmpty()
        ->and($response->json('data.savings_goals'))->not->toBeEmpty()
        ->and($response->json('data.savings_entries'))->not->toBeEmpty()
        ->and($response->json('data.comments'))->not->toBeEmpty()
        ->and($response->json('data.reactions'))->not->toBeEmpty();
});

it('includes a completed task and an achieved goal among the demo data', function () {
    $this->seed(DemoSeeder::class);

    expect(Task::query()->whereNotNull('completed_at')->exists())->toBeTrue()
        ->and(SavingsGoal::query()->whereNotNull('achieved_at')->exists())->toBeTrue();
});

it('creates the demo data only once', function () {
    $this->seed(DemoSeeder::class);

    $this->seed(DemoSeeder::class);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('space_invitations', 1);
});

it('refuses to run outside the local and testing environments', function () {
    $this->app->detectEnvironment(fn () => 'staging');

    expect(fn () => $this->seed(DemoSeeder::class))->toThrow(LogicException::class);

    $this->assertDatabaseEmpty('users');
});

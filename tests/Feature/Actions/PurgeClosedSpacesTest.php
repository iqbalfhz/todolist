<?php

use App\Actions\PurgeClosedSpaces;
use App\Models\Comment;
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
use Illuminate\Support\Facades\Storage;

it('deletes a shared space closed more than 30 days ago for good, with everything it held', function () {
    Storage::fake('r2');
    $this->travelTo('2026-09-01 08:00:00');
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    $space = sharedSpaceBetween($owner, $partner);
    $goal = SavingsGoal::factory()->for($space)->create();
    SavingsEntry::factory()->for($goal)->create();
    $trip = Trip::factory()->for($space)->create(['savings_goal_id' => $goal->id]);
    $checklist = TaskList::factory()->for($space)->create(['trip_id' => $trip->id]);
    $nextTask = Task::factory()->for($checklist)->create();
    $task = Task::factory()->for($checklist)->hasAttached($partner, [], 'assignees')->create(['next_task_id' => $nextTask->id]);
    Task::factory()->for($checklist)->create()->delete();
    SpecialDate::factory()->for($space)->create();
    $memory = Memory::factory()->for($space)->create(['trip_id' => $trip->id]);
    $photo = MemoryPhoto::factory()->for($memory)->create();
    Comment::factory()->on($memory)->create();
    Reaction::factory()->on($task)->create();
    $invitation = SpaceInvitation::factory()->accepted()->create(['space_id' => $space->id]);
    Storage::disk('r2')->put($photo->path, 'foto');
    Storage::disk('r2')->put($photo->thumb_path, 'foto');
    Storage::disk('r2')->put(MemoryPhoto::directoryFor($space->id).'/tanpa-baris.jpg', 'foto');
    Storage::disk('r2')->put('memories/ruang-lain/foto.jpg', 'foto');
    $this->travelTo('2026-09-02 08:00:00');
    $space->delete();
    $this->travelTo('2026-10-02 08:00:01');

    $purged = app(PurgeClosedSpaces::class)->handle();

    expect($purged)->toBe(1);
    $this->assertModelMissing($space);
    $this->assertDatabaseMissing('space_members', ['space_id' => $space->id]);
    foreach (['task_lists', 'tasks', 'savings_goals', 'savings_entries', 'special_dates', 'trips', 'memories', 'memory_photos', 'comments', 'reactions'] as $table) {
        $this->assertDatabaseMissing($table, ['space_id' => $space->id]);
    }
    $this->assertDatabaseMissing('task_assignees', ['task_id' => $task->id]);
    $this->assertDatabaseHas('space_invitations', ['id' => $invitation->id, 'space_id' => null]);
    Storage::disk('r2')->assertMissing([
        $photo->path,
        $photo->thumb_path,
        MemoryPhoto::directoryFor($space->id).'/tanpa-baris.jpg',
    ]);
    Storage::disk('r2')->assertExists('memories/ruang-lain/foto.jpg');
});

it('keeps a shared space that is open or was closed 30 days ago or less', function (Closure $close) {
    Storage::fake('r2');
    $this->travelTo('2026-09-01 08:00:00');
    $space = sharedSpaceBetween(User::factory()->create(), User::factory()->create());
    $list = TaskList::factory()->for($space)->create();
    $close($space);
    $this->travelTo('2026-10-02 08:00:00');

    $purged = app(PurgeClosedSpaces::class)->handle();

    expect($purged)->toBe(0);
    $this->assertModelExists($space);
    $this->assertModelExists($list);
})->with([
    'an open space' => [fn (Space $space) => null],
    'a space closed exactly 30 days ago' => [function (Space $space): void {
        test()->travelTo('2026-09-02 08:00:00');
        $space->delete();
    }],
]);

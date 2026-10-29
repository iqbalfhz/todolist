<?php

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Memory;
use App\Models\Reaction;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('store', function () {
    it('stores the reaction with the ID from the phone, in the space of its subject, and returns 201', function () {
        $this->travelTo('2027-06-26 09:06:00.123456');
        [$user, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $id = newId();

        $response = $this->putJson("/api/v1/reactions/{$id}", [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => '❤️',
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'space_id' => $space->id,
                'subject_type' => 'memory',
                'subject_id' => $memory->id,
                'user_id' => $user->id,
                'emoji' => '❤️',
                'created_at' => '2027-06-26T09:06:00.123456Z',
                'updated_at' => '2027-06-26T09:06:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
        $this->assertDatabaseHas('reactions', ['id' => $id, 'subject_type' => 'memory', 'subject_id' => $memory->id, 'user_id' => $user->id]);
    });

    it('accepts a reaction to a task, a deposit, and a memory', function (string $subjectType, Closure $subject) {
        [, , $space] = actingAsPartnered();
        $subject = $subject($space);

        $response = $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => $subjectType,
            'subject_id' => $subject->id,
            'emoji' => '👍',
        ]);

        $response->assertCreated()->assertJsonPath('data.subject_id', $subject->id);
    })->with([
        'a task' => ['task', fn (Space $space) => Task::factory()->for(TaskList::factory()->for($space))->create()],
        'a deposit' => ['savings_entry', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->create()],
        'a memory' => ['memory', fn (Space $space) => Memory::factory()->for($space)->create()],
    ]);

    it('replaces the earlier reaction of the user to the same subject and bumps its updated_at', function () {
        $this->travelTo('2027-06-26 09:06:00');
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $earlier = Reaction::factory()->on($memory)->for($user)->create(['emoji' => '❤️']);
        $partnerReaction = Reaction::factory()->on($memory)->for($partner)->create();
        $onAnotherSubject = Reaction::factory()->on(Memory::factory()->for($space)->create())->for($user)->create();
        $this->travelTo('2027-06-26 09:07:00.250000');
        $id = newId();

        $response = $this->putJson("/api/v1/reactions/{$id}", [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => '😂',
        ]);

        $response->assertCreated()->assertJsonPath('data.id', $id)->assertJsonPath('data.emoji', '😂');
        $this->assertSoftDeleted($earlier);
        expect($earlier->fresh()->updated_at->toJSON())->toBe('2027-06-26T09:07:00.250000Z');
        $this->assertNotSoftDeleted($partnerReaction);
        $this->assertNotSoftDeleted($onAnotherSubject);
    });

    it('accepts an emoji of 16 code points', function () {
        [, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();

        $response = $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => str_repeat('❤', 16),
        ]);

        $response->assertCreated();
    });

    it('returns 200 with the stored reaction and changes nothing when the PUT is repeated', function () {
        [$user, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $reaction = Reaction::factory()->on($memory)->for($user)->create(['emoji' => '❤️']);

        $response = $this->putJson("/api/v1/reactions/{$reaction->id}", [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => '😂',
        ]);

        $response->assertOk()->assertJsonPath('data.emoji', '❤️');
        $this->assertNotSoftDeleted($reaction);
        $this->assertDatabaseCount('reactions', 1);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a replaced or deleted reaction', function () {
        [$user, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $reaction = Reaction::factory()->on($memory)->for($user)->create();
        $reaction->delete();

        $response = $this->putJson("/api/v1/reactions/{$reaction->id}", [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => '❤️',
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existing) {
        [$user, , $space] = actingAsPartnered();
        $reaction = $existing($user);

        $response = $this->putJson("/api/v1/reactions/{$reaction->id}", [
            'subject_type' => 'memory',
            'subject_id' => Memory::factory()->for($space)->create()->id,
            'emoji' => '❤️',
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertNotSoftDeleted($reaction);
    })->with([
        'a reaction of another couple' => [fn () => Reaction::factory()->create()],
        'a reaction to a subject in the other space of the user' => [fn (User $user) => Reaction::factory()->on(Memory::factory()->for($user->personalSpace())->create())->for($user)->create()],
    ]);

    it('returns 404 when the subject is not one the user can reach', function (Closure $subjectId) {
        [, $partner] = actingAsPartnered();

        $response = $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $subjectId($partner),
            'emoji' => '❤️',
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseEmpty('reactions');
    })->with([
        'a memory in the space of another couple' => [fn () => Memory::factory()->for(sharedSpaceBetween(User::factory()->create(), User::factory()->create()))->create()->id],
        'a memory in the personal space of the partner' => [fn (User $partner) => Memory::factory()->for($partner->personalSpace())->create()->id],
        'a memory that was never created' => [fn () => newId()],
    ]);

    it('returns 409 with the parent_deleted code when the subject was deleted', function () {
        [, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $memory->delete();

        $response = $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => '❤️',
        ]);

        $response->assertConflict()->assertExactJson([
            'message' => 'Kenangan ini sudah dihapus.',
            'code' => 'parent_deleted',
            'current' => null,
        ]);
        $this->assertDatabaseEmpty('reactions');
    });

    it('returns 422 when the subject is in the personal space', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();

        $response = $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => '❤️',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subject_id' => 'Komentar dan reaksi hanya ada di Ruang Bersama.']);
        $this->assertDatabaseEmpty('reactions');
    });

    it('returns 422 for invalid values', function (array $overrides, string $field, string $message) {
        [, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();

        $response = $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'emoji' => '❤️',
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field => $message]);
        $this->assertDatabaseEmpty('reactions');
    })->with([
        'no emoji' => [['emoji' => null], 'emoji', 'Emoji wajib diisi.'],
        'an emoji longer than 16 code points' => [['emoji' => str_repeat('❤', 17)], 'emoji', 'Emoji maksimal berisi 16 karakter.'],
        'a subject type that cannot be reacted to' => [['subject_type' => 'memory_photo'], 'subject_type', 'Jenis subjek yang dipilih tidak valid.'],
        'an uppercase subject ID' => [['subject_id' => '01M4DR8C50JK2MT7XW3PQ9VB6E'], 'subject_id', 'Subjek harus berupa ULID huruf kecil.'],
    ]);

    it('queues a reaction_created push for the partner, titled with its subject', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($space)->state(['name' => 'Dana Liburan']))->create();

        $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => 'savings_entry',
            'subject_id' => $entry->id,
            'emoji' => '🎉',
        ]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::ReactionCreated
            && $job->title === 'Dana Liburan'
            && $job->body === 'Iqbal memberi reaksi 🎉'
            && $job->spaceId === $space->id
            && $job->subjectType === 'savings_entry'
            && $job->subjectId === $entry->id
            && $job->savingsGoalId === $entry->savings_goal_id);
    });

    it('pushes about reactions to one subject at most once every 10 minutes for each recipient', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2027-06-26 09:00:00');
        [$user, $partner, $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $partner->update(['name' => 'Chika']);
        $memory = Memory::factory()->for($space)->create();
        $otherMemory = Memory::factory()->for($space)->create();
        $react = fn (Memory $subject, string $emoji) => $this->putJson('/api/v1/reactions/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $subject->id,
            'emoji' => $emoji,
        ])->assertCreated();

        $react($memory, '❤️');
        $this->travelTo('2027-06-26 09:09:59');
        $react($memory, '😂');
        $react($otherMemory, '👍');
        $this->actingAs($partner, 'sanctum');
        $react($memory, '🥰');
        $this->actingAs($user, 'sanctum');
        $this->travelTo('2027-06-26 09:10:01');
        $react($memory, '😮');

        $pushes = Queue::pushed(SendPushNotification::class)->map(fn (SendPushNotification $job): string => "{$job->userId} {$job->body}");
        expect($pushes->all())->toBe([
            "{$partner->id} Iqbal memberi reaksi ❤️",
            "{$partner->id} Iqbal memberi reaksi 👍",
            "{$user->id} Chika memberi reaksi 🥰",
            "{$partner->id} Iqbal memberi reaksi 😮",
        ]);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/reactions/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the reaction, bumps its updated_at, and queues no push', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2027-06-26 09:06:00');
        [$user, , $space] = actingAsPartnered();
        $reaction = Reaction::factory()->on(Memory::factory()->for($space)->create())->for($user)->create();
        $this->travelTo('2027-06-26 09:07:00.250000');

        $response = $this->deleteJson("/api/v1/reactions/{$reaction->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($reaction);
        expect($reaction->fresh()->updated_at->toJSON())->toBe('2027-06-26T09:07:00.250000Z');
        Queue::assertNotPushed(SendPushNotification::class);
    });

    it('returns 204 again when the delete is repeated', function () {
        [$user, , $space] = actingAsPartnered();
        $reaction = Reaction::factory()->on(Memory::factory()->for($space)->create())->for($user)->create();
        $reaction->delete();

        $response = $this->deleteJson("/api/v1/reactions/{$reaction->id}");

        $response->assertNoContent();
    });

    it('returns 403 with the forbidden code and the stored reaction when the partner deletes it', function () {
        [, $partner, $space] = actingAsPartnered();
        $reaction = Reaction::factory()->on(Memory::factory()->for($space)->create())->for($partner)->create();

        $response = $this->deleteJson("/api/v1/reactions/{$reaction->id}");

        $response->assertForbidden()
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('current.id', $reaction->id);
        $this->assertNotSoftDeleted($reaction);
    });

    it('returns 404 for a reaction in the space of another couple', function () {
        actingAsPartnered();
        $reaction = Reaction::factory()->create();

        $response = $this->deleteJson("/api/v1/reactions/{$reaction->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($reaction);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/reactions/'.newId());

        $response->assertUnauthorized();
    });
});

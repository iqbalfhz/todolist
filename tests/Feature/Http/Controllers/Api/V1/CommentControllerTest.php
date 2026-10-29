<?php

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Comment;
use App\Models\Memory;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('store', function () {
    it('stores the comment with the ID from the phone, in the space of its subject, and returns 201', function () {
        $this->travelTo('2027-06-26 09:05:00.123456');
        [$user, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $id = newId();

        $response = $this->putJson("/api/v1/comments/{$id}", [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'body' => 'Sawahnya masih hijau banget!',
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'space_id' => $space->id,
                'subject_type' => 'memory',
                'subject_id' => $memory->id,
                'user_id' => $user->id,
                'body' => 'Sawahnya masih hijau banget!',
                'created_at' => '2027-06-26T09:05:00.123456Z',
                'updated_at' => '2027-06-26T09:05:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
        $this->assertDatabaseHas('comments', [
            'id' => $id,
            'space_id' => $space->id,
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'user_id' => $user->id,
        ]);
    });

    it('accepts a comment on a task, a deposit, a withdrawal, and a memory', function (string $subjectType, Closure $subject) {
        [, , $space] = actingAsPartnered();
        $subject = $subject($space);

        $response = $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => $subjectType,
            'subject_id' => $subject->id,
            'body' => 'Mantap!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subject_type', $subjectType)
            ->assertJsonPath('data.subject_id', $subject->id);
    })->with([
        'a task' => ['task', fn (Space $space) => Task::factory()->for(TaskList::factory()->for($space))->create()],
        'a deposit' => ['savings_entry', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->create()],
        'a withdrawal' => ['savings_entry', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->withdrawal()->create()],
        'a memory' => ['memory', fn (Space $space) => Memory::factory()->for($space)->create()],
    ]);

    it('still accepts a comment on a task in an archived list and on an entry of an archived goal', function (string $subjectType, Closure $subject) {
        [, , $space] = actingAsPartnered();

        $response = $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => $subjectType,
            'subject_id' => $subject($space)->id,
            'body' => 'Mantap!',
        ]);

        $response->assertCreated();
    })->with([
        'a task in an archived list' => ['task', fn (Space $space) => Task::factory()->for(TaskList::factory()->for($space)->archived())->create()],
        'an entry of an archived goal' => ['savings_entry', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space)->archived())->create()],
    ]);

    it('accepts a comment of 1,000 characters counted as code points', function () {
        [, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();

        $response = $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'body' => str_repeat('😀', 1000),
        ]);

        $response->assertCreated();
    });

    it('returns 200 with the stored comment and changes nothing when the PUT is repeated', function () {
        [$user, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $comment = Comment::factory()->on($memory)->for($user)->create(['body' => 'Asli']);

        $response = $this->putJson("/api/v1/comments/{$comment->id}", [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'body' => 'Kiriman ulang',
        ]);

        $response->assertOk()->assertJsonPath('data.body', 'Asli');
        $this->assertDatabaseCount('comments', 1);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted comment', function () {
        [$user, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $comment = Comment::factory()->on($memory)->for($user)->create();
        $comment->delete();

        $response = $this->putJson("/api/v1/comments/{$comment->id}", [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'body' => 'Kiriman ulang',
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existing) {
        [$user, , $space] = actingAsPartnered();
        $comment = $existing($user);

        $response = $this->putJson("/api/v1/comments/{$comment->id}", [
            'subject_type' => 'memory',
            'subject_id' => Memory::factory()->for($space)->create()->id,
            'body' => 'Mantap!',
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        expect($comment->fresh()->subject_id)->toBe($comment->subject_id);
    })->with([
        'a comment of another couple' => [fn () => Comment::factory()->create()],
        'a comment on a subject in the other space of the user' => [fn (User $user) => Comment::factory()->on(Memory::factory()->for($user->personalSpace())->create())->for($user)->create()],
    ]);

    it('returns 404 when the subject is not one the user can reach', function (string $subjectType, Closure $subjectId) {
        [, $partner] = actingAsPartnered();

        $response = $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId($partner),
            'body' => 'Mantap!',
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
        $this->assertDatabaseEmpty('comments');
    })->with([
        'a memory in the space of another couple' => ['memory', fn () => Memory::factory()->for(sharedSpaceBetween(User::factory()->create(), User::factory()->create()))->create()->id],
        'a task in the personal space of the partner' => ['task', fn (User $partner) => Task::factory()->for($partner->personalSpace()->taskLists()->sole())->create()->id],
        'a subject that was never created' => ['memory', fn () => newId()],
        'a memory named as a task' => ['task', fn (User $partner) => Memory::factory()->for($partner->spaces()->where('type', 'shared')->sole())->create()->id],
    ]);

    it('returns 409 with the parent_deleted code when the subject was deleted', function (string $subjectType, Closure $subject, string $message) {
        [, , $space] = actingAsPartnered();
        $subject = $subject($space);
        $subject->delete();

        $response = $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => $subjectType,
            'subject_id' => $subject->id,
            'body' => 'Mantap!',
        ]);

        $response->assertConflict()->assertExactJson([
            'message' => $message,
            'code' => 'parent_deleted',
            'current' => null,
        ]);
        $this->assertDatabaseEmpty('comments');
    })->with([
        'a task' => ['task', fn (Space $space) => Task::factory()->for(TaskList::factory()->for($space))->create(), 'Tugas ini sudah dihapus.'],
        'a deposit' => ['savings_entry', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->create(), 'Setoran ini sudah dihapus.'],
        'a withdrawal' => ['savings_entry', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->withdrawal()->create(), 'Penarikan ini sudah dihapus.'],
        'a memory' => ['memory', fn (Space $space) => Memory::factory()->for($space)->create(), 'Kenangan ini sudah dihapus.'],
    ]);

    it('returns 422 when the subject is in the personal space', function () {
        $user = actingAsApiUser();
        $memory = Memory::factory()->for($user->personalSpace())->create();

        $response = $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'body' => 'Catatan untuk diri sendiri',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['subject_id' => 'Komentar dan reaksi hanya ada di Ruang Bersama.']);
        $this->assertDatabaseEmpty('comments');
    });

    it('returns 422 for invalid values', function (array $overrides, string $field, string $message) {
        [, , $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();

        $response = $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'body' => 'Mantap!',
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field => $message]);
        $this->assertDatabaseEmpty('comments');
    })->with([
        'no subject type' => [['subject_type' => null], 'subject_type', 'Jenis subjek wajib diisi.'],
        'a subject type that cannot be commented on' => [['subject_type' => 'trip'], 'subject_type', 'Jenis subjek yang dipilih tidak valid.'],
        'no subject' => [['subject_id' => null], 'subject_id', 'Subjek wajib diisi.'],
        'an uppercase subject ID' => [['subject_id' => '01M4DR8C50JK2MT7XW3PQ9VB6E'], 'subject_id', 'Subjek harus berupa ULID huruf kecil.'],
        'no text' => [['body' => null], 'body', 'Komentar wajib diisi.'],
        'only spaces' => [['body' => '   '], 'body', 'Komentar wajib diisi.'],
        'more than 1,000 characters' => [['body' => str_repeat('a', 1001)], 'body', 'Komentar maksimal berisi 1000 karakter.'],
    ]);

    it('queues a comment_created push for the partner, titled with its subject', function (string $subjectType, Closure $subject, string $title, bool $carriesGoal) {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $subject = $subject($space);

        $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => $subjectType,
            'subject_id' => $subject->id,
            'body' => 'Mantap!',
        ]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::CommentCreated
            && $job->title === $title
            && $job->body === 'Iqbal: Mantap!'
            && $job->spaceId === $space->id
            && $job->subjectType === $subjectType
            && $job->subjectId === $subject->id
            && $job->savingsGoalId === ($carriesGoal ? $subject->savings_goal_id : null));
    })->with([
        'a task, by its title' => ['task', fn (Space $space) => Task::factory()->for(TaskList::factory()->for($space))->create(['title' => 'Beli tiket']), 'Beli tiket', false],
        'a deposit, by the name of its goal' => ['savings_entry', fn (Space $space) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($space)->state(['name' => 'Dana Liburan']))->create(), 'Dana Liburan', true],
        'a memory, by its title' => ['memory', fn (Space $space) => Memory::factory()->for($space)->create(['title' => 'Liburan ke Bali']), 'Liburan ke Bali', false],
    ]);

    it('shows the comment in the push on one line, cut after 80 characters', function (string $body, string $pushBody) {
        Queue::fake([SendPushNotification::class]);
        [$user, , $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $memory = Memory::factory()->for($space)->create();

        $this->putJson('/api/v1/comments/'.newId(), [
            'subject_type' => 'memory',
            'subject_id' => $memory->id,
            'body' => $body,
        ]);

        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === $pushBody);
    })->with([
        'a short comment on several lines' => ["Mantap!\n\nLanjut   terus", 'Iqbal: Mantap! Lanjut terus'],
        'exactly 80 characters' => [str_repeat('a', 80), 'Iqbal: '.str_repeat('a', 80)],
        'more than 80 characters' => [str_repeat('a', 81), 'Iqbal: '.str_repeat('a', 80).'…'],
        'a cut that ends on a space' => [str_repeat('a', 79).' bc', 'Iqbal: '.str_repeat('a', 79).'…'],
        'exactly 80 emoji, one character each' => [str_repeat('😀', 80), 'Iqbal: '.str_repeat('😀', 80)],
        'more than 80 emoji' => [str_repeat('😀', 81), 'Iqbal: '.str_repeat('😀', 80).'…'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/comments/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes the text of the comment, bumps its updated_at, and queues no push', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2027-06-26 09:05:00');
        [$user, , $space] = actingAsPartnered();
        $comment = Comment::factory()->on(Memory::factory()->for($space)->create())->for($user)->create(['body' => 'Asli']);
        $this->travelTo('2027-06-26 09:10:00.250000');

        $response = $this->patchJson("/api/v1/comments/{$comment->id}", ['body' => 'Sudah diedit']);

        $response->assertOk()
            ->assertJsonPath('data.body', 'Sudah diedit')
            ->assertJsonPath('data.created_at', '2027-06-26T09:05:00.000000Z')
            ->assertJsonPath('data.updated_at', '2027-06-26T09:10:00.250000Z');
        expect($comment->fresh()->body)->toBe('Sudah diedit');
        Queue::assertNotPushed(SendPushNotification::class);
    });

    it('ignores the subject and other read-only fields', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $memory = Memory::factory()->for($space)->create();
        $comment = Comment::factory()->on($memory)->for($user)->create();

        $response = $this->patchJson("/api/v1/comments/{$comment->id}", [
            'subject_type' => 'task',
            'subject_id' => newId(),
            'space_id' => $partner->personalSpace()->id,
            'user_id' => $partner->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.subject_type', 'memory')
            ->assertJsonPath('data.subject_id', $memory->id)
            ->assertJsonPath('data.space_id', $space->id)
            ->assertJsonPath('data.user_id', $user->id);
    });

    it('returns 403 with the forbidden code and the stored comment when the partner changes it', function () {
        [, $partner, $space] = actingAsPartnered();
        $comment = Comment::factory()->on(Memory::factory()->for($space)->create())->for($partner)->create(['body' => 'Asli']);

        $response = $this->patchJson("/api/v1/comments/{$comment->id}", ['body' => 'Diubah pasangan']);

        $response->assertForbidden()
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('message', 'Kamu tidak boleh melakukan ini.')
            ->assertJsonPath('current.id', $comment->id)
            ->assertJsonPath('current.body', 'Asli');
        expect($comment->fresh()->body)->toBe('Asli');
    });

    it('returns 404 with the not_found code for a comment the user cannot reach', function (Closure $id) {
        actingAsPartnered();

        $response = $this->patchJson('/api/v1/comments/'.$id(), ['body' => 'Diubah']);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
    })->with([
        'a comment in the space of another couple' => [fn () => Comment::factory()->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted comment', function () {
        [$user, , $space] = actingAsPartnered();
        $comment = Comment::factory()->on(Memory::factory()->for($space)->create())->for($user)->create();
        $comment->delete();

        $response = $this->patchJson("/api/v1/comments/{$comment->id}", ['body' => 'Diubah']);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 422 with the stored comment as current for invalid values', function (array $payload) {
        [$user, , $space] = actingAsPartnered();
        $comment = Comment::factory()->on(Memory::factory()->for($space)->create())->for($user)->create(['body' => 'Asli']);

        $response = $this->patchJson("/api/v1/comments/{$comment->id}", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body'])
            ->assertJsonPath('current.body', 'Asli');
    })->with([
        'no text' => [['body' => null]],
        'only spaces' => [['body' => '   ']],
        'more than 1,000 characters' => [['body' => str_repeat('a', 1001)]],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/comments/'.newId(), ['body' => 'Diubah']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the comment, bumps its updated_at, and queues no push', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2027-06-26 09:05:00');
        [$user, , $space] = actingAsPartnered();
        $comment = Comment::factory()->on(Memory::factory()->for($space)->create())->for($user)->create();
        $this->travelTo('2027-06-26 09:10:00.250000');

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($comment);
        expect($comment->fresh()->updated_at->toJSON())->toBe('2027-06-26T09:10:00.250000Z');
        Queue::assertNotPushed(SendPushNotification::class);
    });

    it('returns 204 again when the delete is repeated', function () {
        [$user, , $space] = actingAsPartnered();
        $comment = Comment::factory()->on(Memory::factory()->for($space)->create())->for($user)->create();
        $comment->delete();

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertNoContent();
    });

    it('returns 403 with the forbidden code and the stored comment when the partner deletes it', function () {
        [, $partner, $space] = actingAsPartnered();
        $comment = Comment::factory()->on(Memory::factory()->for($space)->create())->for($partner)->create();

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertForbidden()
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('current.id', $comment->id);
        $this->assertNotSoftDeleted($comment);
    });

    it('returns 404 for a comment in the space of another couple', function () {
        actingAsPartnered();
        $comment = Comment::factory()->create();

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($comment);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/comments/'.newId());

        $response->assertUnauthorized();
    });
});

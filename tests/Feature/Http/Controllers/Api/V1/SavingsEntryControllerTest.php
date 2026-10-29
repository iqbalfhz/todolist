<?php

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('store', function () {
    it('records the deposit with the ID from the phone and returns 201', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2026-10-08 11:05:00.123456');
        [$user, , $space] = actingAsPartnered();
        $goal = SavingsGoal::factory()->for($space)->create();
        $id = newId();

        $response = $this->putJson("/api/v1/savings-entries/{$id}", [
            'savings_goal_id' => $goal->id,
            'amount' => 200000,
            'entry_date' => '2026-10-08',
            'note' => 'Gajian',
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'savings_goal_id' => $goal->id,
                'space_id' => $space->id,
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => 200000,
                'entry_date' => '2026-10-08',
                'note' => 'Gajian',
                'created_at' => '2026-10-08T11:05:00.123456Z',
                'updated_at' => '2026-10-08T11:05:00.123456Z',
                'deleted_at' => null,
            ],
        ]);
    });

    it('records a withdrawal when the phone sends that type', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $id = newId();

        $response = $this->putJson("/api/v1/savings-entries/{$id}", [
            'savings_goal_id' => $goal->id,
            'type' => 'withdrawal',
            'amount' => 200000,
            'entry_date' => '2026-10-08',
        ]);

        $response->assertCreated()->assertJsonPath('data.type', 'withdrawal');
        $this->assertDatabaseHas('savings_entries', ['id' => $id, 'type' => 'withdrawal', 'amount' => 200000]);
    });

    it('still accepts a deposit for an archived goal', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->archived()->create();

        $response = $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200000,
            'entry_date' => '2026-10-08',
        ]);

        $response->assertCreated()->assertJsonPath('data.note', null);
    });

    it('returns 200 with the stored deposit and changes nothing when the PUT is repeated', function () {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create(['amount' => 200000]);

        $response = $this->putJson("/api/v1/savings-entries/{$entry->id}", [
            'savings_goal_id' => $entry->savings_goal_id,
            'amount' => 999,
            'entry_date' => '2026-01-01',
        ]);

        $response->assertOk()->assertJsonPath('data.amount', 200000);
        $this->assertDatabaseCount('savings_entries', 1);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted deposit', function () {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create();
        $entry->delete();

        $response = $this->putJson("/api/v1/savings-entries/{$entry->id}", [
            'savings_goal_id' => $entry->savings_goal_id,
            'amount' => 200000,
            'entry_date' => '2026-10-08',
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function () {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->create();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();

        $response = $this->putJson("/api/v1/savings-entries/{$entry->id}", [
            'savings_goal_id' => $goal->id,
            'amount' => 200000,
            'entry_date' => '2026-10-08',
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict')->assertJsonPath('current', null);
    });

    it('returns 404 when the goal is not one the user can reach', function (Closure $goalId) {
        actingAsApiUser();

        $response = $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goalId(),
            'amount' => 200000,
            'entry_date' => '2026-10-08',
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseEmpty('savings_entries');
    })->with([
        'a goal in the space of another couple' => [fn () => SavingsGoal::factory()->create()->id],
        'a goal that was never created' => [fn () => newId()],
    ]);

    it('returns 409 with the parent_deleted code when the goal was deleted', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();
        $goal->delete();

        $response = $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200000,
            'entry_date' => '2026-10-08',
        ]);

        $response->assertConflict()->assertExactJson([
            'message' => 'Target ini sudah dihapus.',
            'code' => 'parent_deleted',
            'current' => null,
        ]);
        $this->assertDatabaseEmpty('savings_entries');
    });

    it('returns 422 for invalid values', function (array $overrides, string $field) {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create();

        $response = $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200000,
            'entry_date' => '2026-10-08',
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseEmpty('savings_entries');
    })->with([
        'no goal' => [['savings_goal_id' => null], 'savings_goal_id'],
        'an amount of zero' => [['amount' => 0], 'amount'],
        'a negative amount' => [['amount' => -5], 'amount'],
        'an amount with decimals' => [['amount' => 100.5], 'amount'],
        'an amount above 10^15' => [['amount' => 1_000_000_000_000_001], 'amount'],
        'no entry date' => [['entry_date' => null], 'entry_date'],
        'an entry date in another format' => [['entry_date' => '08/10/2026'], 'entry_date'],
        'a note longer than 200 characters' => [['note' => str_repeat('a', 201)], 'note'],
        'an unknown type' => [['type' => 'loan'], 'type'],
        'an empty type' => [['type' => null], 'type'],
    ]);

    it('marks the goal achieved once the deposits reach the target', function () {
        $this->travelTo('2026-10-08 12:00:00');
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['target_amount' => 500_000]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 300_000]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200_000,
            'entry_date' => '2026-10-08',
        ]);

        expect($goal->fresh()->achieved_at->toJSON())->toBe('2026-10-08T12:00:00.000000Z');
    });

    it('leaves the goal unachieved while the deposits stay below the target', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['target_amount' => 500_000]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 499_999,
            'entry_date' => '2026-10-08',
        ]);

        expect($goal->fresh()->achieved_at)->toBeNull();
    });

    it('counts a withdrawal against the target', function (int $withdrawn, bool $achieved) {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create([
            'target_amount' => 500_000,
            'achieved_at' => now(),
            'achieved_notified_at' => now(),
        ]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 600_000]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'type' => 'withdrawal',
            'amount' => $withdrawn,
            'entry_date' => '2026-10-08',
        ]);

        expect($goal->fresh()->achieved_at !== null)->toBe($achieved);
    })->with([
        'leaving exactly the target' => [100_000, true],
        'taking the total below the target' => [100_001, false],
    ]);

    it('accepts a withdrawal larger than what was saved', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['target_amount' => 500_000]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 100_000]);

        $response = $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'type' => 'withdrawal',
            'amount' => 300_000,
            'entry_date' => '2026-10-08',
        ]);

        $response->assertCreated();
        expect($goal->savedAmount())->toBe(-200_000);
    });

    it('queues a savings_withdrawal push for the partner instead of a deposit push', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $goal = SavingsGoal::factory()->for($space)->create(['name' => 'Dana Liburan', 'target_amount' => 10_000_000]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 1_000_000]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'type' => 'withdrawal',
            'amount' => 200_000,
            'entry_date' => '2026-10-08',
        ]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::SavingsWithdrawal
            && $job->title === 'Dana Liburan'
            && $job->body === 'Iqbal mengambil Rp200.000'
            && $job->spaceId === $space->id
            && $job->savingsGoalId === $goal->id);
    });

    it('queues a savings_deposit push for the partner', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $goal = SavingsGoal::factory()->for($space)->create(['name' => 'Dana Liburan', 'target_amount' => 10_000_000]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200_000,
            'entry_date' => '2026-10-08',
        ]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::SavingsDeposit
            && $job->title === 'Dana Liburan'
            && $job->body === 'Iqbal menabung Rp200.000'
            && $job->spaceId === $space->id
            && $job->savingsGoalId === $goal->id);
    });

    it('queues only the savings_achieved push for the deposit that reaches the target', function () {
        Queue::fake([SendPushNotification::class]);
        [, $partner, $space] = actingAsPartnered();
        $goal = SavingsGoal::factory()->for($space)->create(['target_amount' => 200_000]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200_000,
            'entry_date' => '2026-10-08',
        ]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::SavingsAchieved);
    });

    it('does not celebrate a goal twice', function () {
        Queue::fake([SendPushNotification::class]);
        [, , $space] = actingAsPartnered();
        $goal = SavingsGoal::factory()->for($space)->create(['target_amount' => 200_000, 'achieved_notified_at' => now()]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200_000,
            'entry_date' => '2026-10-08',
        ]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->type === PushType::SavingsDeposit);
    });

    it('queues no push for a deposit in the personal space', function () {
        Queue::fake([SendPushNotification::class]);
        [$user] = actingAsPartnered();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['target_amount' => 200_000]);

        $this->putJson('/api/v1/savings-entries/'.newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => 200_000,
            'entry_date' => '2026-10-08',
        ]);

        Queue::assertNotPushed(SendPushNotification::class);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/savings-entries/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent', function () {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create([
            'amount' => 200_000,
            'entry_date' => '2026-10-08',
            'note' => 'Gajian',
        ]);

        $response = $this->patchJson("/api/v1/savings-entries/{$entry->id}", ['amount' => 250_000]);

        $response->assertOk()
            ->assertJsonPath('data.amount', 250_000)
            ->assertJsonPath('data.entry_date', '2026-10-08')
            ->assertJsonPath('data.note', 'Gajian');
    });

    it('lets the partner change a deposit the other one recorded', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($space))->create(['user_id' => $partner->id]);

        $response = $this->patchJson("/api/v1/savings-entries/{$entry->id}", ['note' => null, 'amount' => 50_000]);

        $response->assertOk()
            ->assertJsonPath('data.amount', 50_000)
            ->assertJsonPath('data.note', null)
            ->assertJsonPath('data.user_id', $partner->id);
    });

    it('recalculates the goal when the amount changes', function (int $newAmount, bool $achieved) {
        Queue::fake([SendPushNotification::class]);
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['target_amount' => 500_000]);
        $entry = SavingsEntry::factory()->for($goal)->create(['amount' => 300_000]);

        $this->patchJson("/api/v1/savings-entries/{$entry->id}", ['amount' => $newAmount]);

        expect($goal->fresh()->achieved_at !== null)->toBe($achieved);
    })->with([
        'up to the target' => [500_000, true],
        'still below the target' => [400_000, false],
    ]);

    it('clears the achievement when the amount drops below the target', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create([
            'target_amount' => 500_000,
            'achieved_at' => now(),
            'achieved_notified_at' => now(),
        ]);
        $entry = SavingsEntry::factory()->for($goal)->create(['amount' => 500_000]);

        $this->patchJson("/api/v1/savings-entries/{$entry->id}", ['amount' => 100_000]);

        expect($goal->fresh()->achieved_at)->toBeNull();
    });

    it('clears the achievement when a withdrawal grows past what is left above the target', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create([
            'target_amount' => 500_000,
            'achieved_at' => now(),
            'achieved_notified_at' => now(),
        ]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 600_000]);
        $withdrawal = SavingsEntry::factory()->for($goal)->withdrawal()->create(['amount' => 50_000]);

        $response = $this->patchJson("/api/v1/savings-entries/{$withdrawal->id}", ['amount' => 150_000]);

        $response->assertOk()->assertJsonPath('data.type', 'withdrawal');
        expect($goal->fresh()->achieved_at)->toBeNull();
    });

    it('ignores the goal and other read-only fields', function () {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create();
        $otherGoal = SavingsGoal::factory()->for($user->personalSpace())->create();

        $response = $this->patchJson("/api/v1/savings-entries/{$entry->id}", [
            'savings_goal_id' => $otherGoal->id,
            'type' => 'withdrawal',
            'user_id' => newId(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.savings_goal_id', $entry->savings_goal_id)
            ->assertJsonPath('data.type', 'deposit')
            ->assertJsonPath('data.user_id', $user->id);
    });

    it('returns 404 with the not_found code for a deposit the user cannot reach', function (Closure $id) {
        [, $partner] = actingAsPartnered();

        $response = $this->patchJson('/api/v1/savings-entries/'.$id($partner), ['amount' => 1]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found')->assertJsonPath('current', null);
    })->with([
        'a deposit in the space of another couple' => [fn () => SavingsEntry::factory()->create()->id],
        'a deposit in the personal space of the partner' => [fn (User $partner) => SavingsEntry::factory()->for(SavingsGoal::factory()->for($partner->personalSpace()))->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted deposit', function () {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create();
        $entry->delete();

        $response = $this->patchJson("/api/v1/savings-entries/{$entry->id}", ['amount' => 1]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 422 with the stored deposit as current for invalid values', function (array $payload, string $field) {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create(['amount' => 200_000]);

        $response = $this->patchJson("/api/v1/savings-entries/{$entry->id}", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath('current.amount', 200_000);
    })->with([
        'an amount of zero' => [['amount' => 0], 'amount'],
        'an entry date in another format' => [['entry_date' => '08/10/2026'], 'entry_date'],
        'a note longer than 200 characters' => [['note' => str_repeat('a', 201)], 'note'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/savings-entries/'.newId(), ['amount' => 1]);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the deposit and bumps its updated_at', function () {
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create();
        $this->travelTo('2026-10-08 11:00:00.250000');

        $response = $this->deleteJson("/api/v1/savings-entries/{$entry->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($entry);
        expect($entry->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z');
    });

    it('clears the achievement when the remaining deposits fall below the target', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create([
            'target_amount' => 500_000,
            'achieved_at' => now(),
            'achieved_notified_at' => now(),
        ]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 300_000]);
        $entry = SavingsEntry::factory()->for($goal)->create(['amount' => 200_000]);

        $this->deleteJson("/api/v1/savings-entries/{$entry->id}");

        expect($goal->fresh()->achieved_at)->toBeNull();
    });

    it('marks the goal achieved again when the withdrawal that took it below the target is deleted', function () {
        $user = actingAsApiUser();
        $goal = SavingsGoal::factory()->for($user->personalSpace())->create(['target_amount' => 500_000]);
        SavingsEntry::factory()->for($goal)->create(['amount' => 500_000]);
        $withdrawal = SavingsEntry::factory()->for($goal)->withdrawal()->create(['amount' => 100_000]);

        $this->deleteJson("/api/v1/savings-entries/{$withdrawal->id}");

        expect($goal->fresh()->achieved_at)->not->toBeNull();
    });

    it('deletes the comments and reactions on the entry, leaving those on other entries alone', function () {
        $this->travelTo('2026-10-08 10:00:00');
        [, $partner, $space] = actingAsPartnered();
        $goal = SavingsGoal::factory()->for($space)->create();
        $entry = SavingsEntry::factory()->for($goal)->create();
        $comment = Comment::factory()->on($entry)->for($partner)->create();
        $reaction = Reaction::factory()->on($entry)->for($partner)->create();
        $onAnotherEntry = Comment::factory()->on(SavingsEntry::factory()->for($goal)->create())->for($partner)->create();
        $this->travelTo('2026-10-08 11:00:00.250000');

        $this->deleteJson("/api/v1/savings-entries/{$entry->id}")->assertNoContent();

        $this->assertSoftDeleted($comment);
        $this->assertSoftDeleted($reaction);
        expect($reaction->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z');
        $this->assertNotSoftDeleted($onAnotherEntry);
    });

    it('returns 204 again when the delete is repeated', function () {
        $user = actingAsApiUser();
        $entry = SavingsEntry::factory()->for(SavingsGoal::factory()->for($user->personalSpace()))->create();
        $entry->delete();

        $response = $this->deleteJson("/api/v1/savings-entries/{$entry->id}");

        $response->assertNoContent();
    });

    it('returns 404 for a deposit in the space of another couple', function () {
        actingAsApiUser();
        $entry = SavingsEntry::factory()->create();

        $response = $this->deleteJson("/api/v1/savings-entries/{$entry->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($entry);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/savings-entries/'.newId());

        $response->assertUnauthorized();
    });
});

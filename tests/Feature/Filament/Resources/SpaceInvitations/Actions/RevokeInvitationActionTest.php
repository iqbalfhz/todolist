<?php

use App\Filament\Resources\SpaceInvitations\Pages\ListSpaceInvitations;
use App\Models\SpaceInvitation;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('revokes the active invitation and leaves the others alone', function () {
    $this->travelTo('2026-10-08 10:00:00');
    actingAsAdmin();
    $invitation = SpaceInvitation::factory()->create();
    $otherInvitation = SpaceInvitation::factory()->create();

    $page = Livewire::test(ListSpaceInvitations::class)
        ->callAction(TestAction::make('revoke')->table($invitation));

    $page->assertNotified('Undangan dicabut');
    expect($invitation->fresh()->revoked_at->toJSON())->toBe('2026-10-08T10:00:00.000000Z')
        ->and($otherInvitation->fresh()->revoked_at)->toBeNull();
});

it('offers no revoke action for an invitation that is no longer active', function (Closure $invitation) {
    actingAsAdmin();
    $invitation = $invitation();

    $page = Livewire::test(ListSpaceInvitations::class);

    $page->assertActionHidden(TestAction::make('revoke')->table($invitation));
})->with([
    'an accepted invitation' => [fn () => SpaceInvitation::factory()->accepted()->create()],
    'a revoked invitation' => [fn () => SpaceInvitation::factory()->revoked()->create()],
    'an expired invitation' => [fn () => SpaceInvitation::factory()->expired()->create()],
]);

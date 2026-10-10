<?php

use App\Enums\InvitationStatus;
use App\Models\SpaceInvitation;

dataset('invitation states', [
    'active until the moment it expires' => [fn () => SpaceInvitation::factory()->create(['expires_at' => '2026-10-08 10:00:00.000001']), InvitationStatus::Active],
    'expired from its expiry time on' => [fn () => SpaceInvitation::factory()->create(['expires_at' => '2026-10-08 10:00:00']), InvitationStatus::Expired],
    'accepted, also after its expiry time' => [fn () => SpaceInvitation::factory()->accepted()->expired()->create(), InvitationStatus::Accepted],
    'revoked, also after its expiry time' => [fn () => SpaceInvitation::factory()->revoked()->expired()->create(), InvitationStatus::Revoked],
]);

it('tells where the invitation stands', function (Closure $invitation, InvitationStatus $expected) {
    $this->travelTo('2026-10-08 10:00:00');
    $invitation = $invitation();

    $status = $invitation->status;

    expect($status)->toBe($expected);
})->with('invitation states');

it('keeps the active scope to the invitations whose status is active', function (Closure $invitation, InvitationStatus $status) {
    $this->travelTo('2026-10-08 10:00:00');
    $invitation();

    $found = SpaceInvitation::query()->active()->exists();

    expect($found)->toBe($status === InvitationStatus::Active);
})->with('invitation states');

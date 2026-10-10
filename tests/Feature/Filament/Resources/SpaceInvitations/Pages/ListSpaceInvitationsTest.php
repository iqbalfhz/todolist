<?php

use App\Filament\Resources\SpaceInvitations\Pages\ListSpaceInvitations;
use App\Models\SpaceInvitation;
use App\Models\User;
use Livewire\Livewire;

it('lists every invitation with its status and who used it', function () {
    actingAsAdmin();
    $partner = User::factory()->create(['name' => 'Pasangan']);
    $activeInvitation = SpaceInvitation::factory()->create();
    $acceptedInvitation = SpaceInvitation::factory()->accepted()->create(['accepted_by' => $partner->id]);
    $revokedInvitation = SpaceInvitation::factory()->revoked()->create();
    $expiredInvitation = SpaceInvitation::factory()->expired()->create();

    $page = Livewire::test(ListSpaceInvitations::class);

    $page->assertOk()
        ->assertCanSeeTableRecords([$activeInvitation, $acceptedInvitation, $revokedInvitation, $expiredInvitation])
        ->assertTableColumnFormattedStateSet('status', 'Aktif', record: $activeInvitation)
        ->assertTableColumnFormattedStateSet('status', 'Terpakai', record: $acceptedInvitation)
        ->assertTableColumnFormattedStateSet('status', 'Dicabut', record: $revokedInvitation)
        ->assertTableColumnFormattedStateSet('status', 'Kedaluwarsa', record: $expiredInvitation)
        ->assertTableColumnStateSet('acceptor.name', 'Pasangan', record: $acceptedInvitation);
});

it('finds an invitation by its code', function () {
    actingAsAdmin();
    $matchingInvitation = SpaceInvitation::factory()->create(['code' => 'K7M2QX']);
    $otherInvitation = SpaceInvitation::factory()->create(['code' => 'ABCDEF']);

    $page = Livewire::test(ListSpaceInvitations::class)->searchTable('K7M2QX');

    $page->assertCanSeeTableRecords([$matchingInvitation])->assertCanNotSeeTableRecords([$otherInvitation]);
});

it('filters the invitations down to the active ones', function () {
    actingAsAdmin();
    $activeInvitation = SpaceInvitation::factory()->create();
    $inactiveInvitations = [
        SpaceInvitation::factory()->accepted()->create(),
        SpaceInvitation::factory()->revoked()->create(),
        SpaceInvitation::factory()->expired()->create(),
    ];

    $page = Livewire::test(ListSpaceInvitations::class)->filterTable('active');

    $page->assertCanSeeTableRecords([$activeInvitation])->assertCanNotSeeTableRecords($inactiveInvitations);
});

it('shows the invitations page to the admin', function () {
    actingAsAdmin();

    $response = $this->get('/admin/space-invitations');

    $response->assertOk();
});

it('returns 403 when the signed-in user is not the admin', function () {
    config(['admin.email' => 'admin@contoh.test']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/space-invitations');

    $response->assertForbidden();
});

it('returns 404 for a create page because invitations come from the app', function () {
    actingAsAdmin();

    $response = $this->get('/admin/space-invitations/create');

    $response->assertNotFound();
});

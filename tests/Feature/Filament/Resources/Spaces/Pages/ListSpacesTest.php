<?php

use App\Filament\Resources\Spaces\Pages\ListSpaces;
use App\Models\Space;
use App\Models\User;
use Livewire\Livewire;

it('lists every space with its type, owner, and number of members', function () {
    actingAsAdmin();
    $owner = User::factory()->create(['name' => 'Iqbal']);
    $personalSpace = Space::factory()->for($owner, 'owner')->create();
    $sharedSpace = sharedSpaceBetween($owner, User::factory()->create());

    $page = Livewire::test(ListSpaces::class);

    $page->assertOk()
        ->assertCanSeeTableRecords([$personalSpace, $sharedSpace])
        ->assertTableColumnFormattedStateSet('type', 'Pribadi', record: $personalSpace)
        ->assertTableColumnFormattedStateSet('type', 'Bersama', record: $sharedSpace)
        ->assertTableColumnStateSet('owner.name', 'Iqbal', record: $sharedSpace)
        ->assertTableColumnStateSet('members_count', 2, record: $sharedSpace);
});

it('finds a space by the email of its owner', function () {
    actingAsAdmin();
    $matchingSpace = Space::factory()->for(User::factory()->state(['email' => 'dicari@contoh.test']), 'owner')->create();
    $otherSpace = Space::factory()->create();

    $page = Livewire::test(ListSpaces::class)->searchTable('dicari@contoh.test');

    $page->assertCanSeeTableRecords([$matchingSpace])->assertCanNotSeeTableRecords([$otherSpace]);
});

it('filters the spaces by type', function () {
    actingAsAdmin();
    $personalSpace = Space::factory()->create();
    $sharedSpace = sharedSpaceBetween(User::factory()->create(), User::factory()->create());

    $page = Livewire::test(ListSpaces::class)->filterTable('type', 'shared');

    $page->assertCanSeeTableRecords([$sharedSpace])->assertCanNotSeeTableRecords([$personalSpace]);
});

it('shows the spaces page to the admin', function () {
    actingAsAdmin();

    $response = $this->get('/admin/spaces');

    $response->assertOk();
});

it('returns 403 when the signed-in user is not the admin', function () {
    config(['admin.email' => 'admin@contoh.test']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/spaces');

    $response->assertForbidden();
});

it('returns 404 for the pages that would create or edit a space', function (Closure $url) {
    actingAsAdmin();
    $space = Space::factory()->create();

    $response = $this->get($url($space));

    $response->assertNotFound();
})->with([
    'the create page' => [fn () => '/admin/spaces/create'],
    'the edit page' => [fn (Space $space) => "/admin/spaces/{$space->id}/edit"],
]);

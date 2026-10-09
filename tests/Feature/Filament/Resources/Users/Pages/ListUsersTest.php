<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

it('lists every user', function () {
    actingAsAdmin();
    $users = User::factory()->count(3)->create();

    $page = Livewire::test(ListUsers::class);

    $page->assertOk()->assertCanSeeTableRecords($users);
});

it('finds a user by email', function () {
    actingAsAdmin();
    $matchingUser = User::factory()->create(['email' => 'dicari@contoh.test']);
    $otherUser = User::factory()->create();

    $page = Livewire::test(ListUsers::class)->searchTable('dicari@contoh.test');

    $page->assertCanSeeTableRecords([$matchingUser])->assertCanNotSeeTableRecords([$otherUser]);
});

it('shows the users page to the admin', function () {
    actingAsAdmin();

    $response = $this->get('/admin/users');

    $response->assertOk();
});

it('returns 403 when the signed-in user is not the admin', function () {
    config(['admin.email' => 'admin@contoh.test']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/users');

    $response->assertForbidden();
});

it('returns 404 for a create page because users are view-only', function () {
    actingAsAdmin();

    $response = $this->get('/admin/users/create');

    $response->assertNotFound();
});

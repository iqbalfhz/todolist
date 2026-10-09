<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Livewire\Livewire;

it('shows the name and email of the user', function () {
    actingAsAdmin();
    $user = User::factory()->create();

    $page = Livewire::test(ViewUser::class, ['record' => $user->id]);

    $page->assertOk()->assertSchemaStateSet([
        'name' => $user->name,
        'email' => $user->email,
    ]);
});

it('offers the reset password action', function () {
    actingAsAdmin();
    $user = User::factory()->create();

    $page = Livewire::test(ViewUser::class, ['record' => $user->id]);

    $page->assertActionVisible('resetPassword');
});

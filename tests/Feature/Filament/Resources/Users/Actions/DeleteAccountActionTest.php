<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('deletes the account with everything it holds from the list of users', function () {
    Storage::fake('r2');
    actingAsAdmin();
    $user = User::factory()->withPersonalSpace()->create();
    $personalSpace = $user->personalSpace();
    $otherUser = User::factory()->create();

    $page = Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('deleteAccount')->table($user));

    $page->assertNotified('Akun dihapus');
    $this->assertModelMissing($user);
    $this->assertModelMissing($personalSpace);
    $this->assertModelExists($otherUser);
});

it('deletes the account from its own page and goes back to the list', function () {
    Storage::fake('r2');
    actingAsAdmin();
    $user = User::factory()->withPersonalSpace()->create();

    $page = Livewire::test(ViewUser::class, ['record' => $user->id])
        ->callAction('deleteAccount');

    $page->assertRedirect(UserResource::getUrl('index'));
    $this->assertModelMissing($user);
});

it('does not offer to delete the admin account', function () {
    $admin = actingAsAdmin();

    $page = Livewire::test(ListUsers::class);

    $page->assertActionHidden(TestAction::make('deleteAccount')->table($admin));
    $this->assertModelExists($admin);
});

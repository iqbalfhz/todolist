<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('sets the new password and revokes every token of that user only', function () {
    actingAsAdmin();
    $user = User::factory()->create();
    $user->createToken('ponsel');
    $user->createToken('tablet');
    $otherUser = User::factory()->create();
    $otherUser->createToken('ponsel');

    $page = Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('resetPassword')->table($user), data: ['password' => 'kata-sandi-baru']);

    $page->assertHasNoFormErrors()->assertNotified('Kata sandi diatur ulang');
    $this->assertCredentials(['email' => $user->email, 'password' => 'kata-sandi-baru']);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $otherUser->id]);
});

it('rejects a password shorter than 8 characters and changes nothing', function () {
    actingAsAdmin();
    $user = User::factory()->create();
    $user->createToken('ponsel');

    $page = Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('resetPassword')->table($user), data: ['password' => 'pendek']);

    $page->assertHasFormErrors(['password' => 'Kata sandi baru minimal berisi 8 karakter.']);
    $this->assertCredentials(['email' => $user->email, 'password' => 'password']);
    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
});

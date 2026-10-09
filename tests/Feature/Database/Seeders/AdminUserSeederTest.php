<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

it('creates an admin who can sign in to the admin panel with the default password', function () {
    config(['admin.email' => 'admin@contoh.test']);

    $this->seed(AdminUserSeeder::class);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@contoh.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();
    $this->assertAuthenticatedAs(User::query()->where('email', 'admin@contoh.test')->sole());
});

it('resets the password of an existing admin without creating a second user', function () {
    config(['admin.email' => 'admin@contoh.test']);
    User::factory()->create(['email' => 'admin@contoh.test', 'password' => 'kata-sandi-lama']);

    $this->seed(AdminUserSeeder::class);

    $this->assertDatabaseCount('users', 1);
    $this->assertCredentials(['email' => 'admin@contoh.test', 'password' => 'password']);
});

it('creates no user while no admin email is configured', function () {
    config(['admin.email' => null]);

    $this->seed(AdminUserSeeder::class);

    $this->assertDatabaseEmpty('users');
});

it('refuses to run outside the local and testing environments', function () {
    config(['admin.email' => 'admin@contoh.test']);
    $this->app->detectEnvironment(fn () => 'staging');

    expect(fn () => (new AdminUserSeeder)->run())->toThrow(LogicException::class);

    $this->assertDatabaseEmpty('users');
});

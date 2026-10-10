<?php

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function () {
        Http::preventStrayRequests();
        Sleep::fake(syncWithCarbon: true);
        Exceptions::fake();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a user, make them the configured admin, and sign them in.
 */
function actingAsAdmin(): User
{
    $admin = User::factory()->create();

    config(['admin.email' => $admin->email]);

    test()->actingAs($admin);

    return $admin;
}

/**
 * Create a registered user, with their personal space, and sign them in to the API.
 */
function actingAsApiUser(): User
{
    $user = User::factory()->withPersonalSpace()->create();

    test()->actingAs($user, 'sanctum');

    return $user;
}

/**
 * Create the space that two partners share, owned by the one who sent the invitation.
 */
function sharedSpaceBetween(User $owner, User $partner): Space
{
    return Space::factory()->for($owner, 'owner')->sharedWith($partner)->create();
}

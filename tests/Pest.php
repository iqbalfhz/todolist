<?php

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Messaging;
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

        // FCM is called through Guzzle, which the HTTP guard above does not cover.
        $this->app->bind(Messaging::class, fn () => throw new LogicException(
            'A push reached FCM in a test. Fake the queue for SendPushNotification or mock Messaging.',
        ));
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
 * Sign in a registered user who shares a space with a partner.
 *
 * @return array{0: User, 1: User, 2: Space} The signed-in user, the partner, and their shared space.
 */
function actingAsPartnered(): array
{
    $user = actingAsApiUser();
    $partner = User::factory()->withPersonalSpace()->create();

    return [$user, $partner, sharedSpaceBetween($user, $partner)];
}

/**
 * Generate an ID the way the phone does: a lowercase ULID.
 */
function newId(): string
{
    return Str::lower((string) Str::ulid());
}

/**
 * Create the space that two partners share, owned by the one who sent the invitation.
 */
function sharedSpaceBetween(User $owner, User $partner): Space
{
    return Space::factory()->for($owner, 'owner')->sharedWith($partner)->create();
}

/**
 * Build a 64 by 48 JPEG that carries the given text in its EXIF "Make" tag.
 */
function jpegWithExif(string $make): string
{
    $value = $make."\0";

    return jpegWithExifEntry(pack('vvVV', 0x010F, 2, strlen($value), 26), $value);
}

/**
 * Build a 64 by 48 JPEG whose EXIF tells viewers to turn it, as a phone held upright writes it.
 *
 * Orientation 6 means the picture must be turned 90 degrees clockwise.
 */
function jpegWithOrientation(int $orientation): string
{
    return jpegWithExifEntry(pack('vvVvv', 0x0112, 3, 1, $orientation, 0));
}

/**
 * Build a 64 by 48 JPEG with one EXIF entry, followed by the data the entry points to.
 */
function jpegWithExifEntry(string $entry, string $data = ''): string
{
    ob_start();
    imagejpeg(imagecreatetruecolor(64, 48));
    $jpeg = ob_get_clean();

    $tiff = "II*\0".pack('V', 8).pack('v', 1).$entry.pack('V', 0).$data;
    $payload = "Exif\0\0".$tiff;

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2);
}

<?php

use App\Enums\DevicePlatform;
use App\Filament\Resources\DeviceTokens\Pages\ListDeviceTokens;
use App\Models\DeviceToken;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('lists every device token with its user and platform', function () {
    actingAsAdmin();
    $user = User::factory()->create(['name' => 'Iqbal']);
    $androidToken = DeviceToken::factory()->for($user)->create();
    $iosToken = DeviceToken::factory()->for($user)->create(['platform' => DevicePlatform::Ios]);

    $page = Livewire::test(ListDeviceTokens::class);

    $page->assertOk()
        ->assertCanSeeTableRecords([$androidToken, $iosToken])
        ->assertTableColumnStateSet('user.name', 'Iqbal', record: $androidToken)
        ->assertTableColumnFormattedStateSet('platform', 'Android', record: $androidToken)
        ->assertTableColumnFormattedStateSet('platform', 'iOS', record: $iosToken);
});

it('finds a device token by the email of its user', function () {
    actingAsAdmin();
    $matchingToken = DeviceToken::factory()->for(User::factory()->state(['email' => 'dicari@contoh.test']))->create();
    $otherToken = DeviceToken::factory()->create();

    $page = Livewire::test(ListDeviceTokens::class)->searchTable('dicari@contoh.test');

    $page->assertCanSeeTableRecords([$matchingToken])->assertCanNotSeeTableRecords([$otherToken]);
});

it('deletes the device token and leaves the others alone', function () {
    actingAsAdmin();
    $token = DeviceToken::factory()->create();
    $otherToken = DeviceToken::factory()->create();

    $page = Livewire::test(ListDeviceTokens::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($token));

    $page->assertNotified();
    $this->assertModelMissing($token);
    $this->assertModelExists($otherToken);
});

it('shows the device tokens page to the admin', function () {
    actingAsAdmin();

    $response = $this->get('/admin/device-tokens');

    $response->assertOk();
});

it('returns 403 when the signed-in user is not the admin', function () {
    config(['admin.email' => 'admin@contoh.test']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/device-tokens');

    $response->assertForbidden();
});

it('returns 404 for a create page because devices register from the app', function () {
    actingAsAdmin();

    $response = $this->get('/admin/device-tokens/create');

    $response->assertNotFound();
});

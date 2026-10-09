<?php

use App\Models\User;

it('redirects guests to the admin login page', function () {
    $response = $this->get('/admin');

    $response->assertRedirectToRoute('filament.admin.auth.login');
});

it('returns 403 when the signed-in user is not the admin', function () {
    config(['admin.email' => 'admin@contoh.test']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertForbidden();
});

it('shows the dashboard to the admin', function () {
    $admin = User::factory()->create();
    config(['admin.email' => $admin->email]);

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
});

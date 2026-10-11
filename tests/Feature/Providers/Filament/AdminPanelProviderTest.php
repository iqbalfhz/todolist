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

it('calls the home page of the panel Dashboard, not Dasbor', function () {
    actingAsAdmin();

    $response = $this->get('/admin');

    $response->assertOk()->assertSee('Dashboard')->assertDontSee('Dasbor');
});

it('returns 404 for what users keep in their spaces, which the panel never shows', function (string $url) {
    actingAsAdmin();

    $response = $this->get($url);

    $response->assertNotFound();
})->with([
    'task lists' => ['/admin/task-lists'],
    'tasks' => ['/admin/tasks'],
    'savings goals' => ['/admin/savings-goals'],
    'savings entries' => ['/admin/savings-entries'],
]);

it('stores the admin session in the database with the user id', function () {
    config(['session.driver' => 'database']);
    $admin = User::factory()->create();
    config(['admin.email' => $admin->email]);

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
    $this->assertDatabaseHas('sessions', ['user_id' => $admin->id]);
});

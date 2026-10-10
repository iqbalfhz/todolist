<?php

use Illuminate\Support\Facades\Route;

it('returns 401 with the unauthenticated code when no token is sent', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized()->assertExactJson([
        'message' => 'Sesi kamu sudah berakhir. Silakan masuk lagi.',
        'code' => 'unauthenticated',
    ]);
});

it('answers in JSON even when the Accept header is missing', function () {
    $response = $this->get('/api/v1/me');

    $response->assertUnauthorized()->assertJsonPath('code', 'unauthenticated');
});

it('returns 404 with the route_not_found code for an unknown endpoint', function () {
    $response = $this->getJson('/api/v1/tidak-ada');

    $response->assertNotFound()->assertExactJson([
        'message' => 'Alamat ini tidak dikenal. Coba perbarui aplikasi.',
        'code' => 'route_not_found',
    ]);
});

it('returns 404 with the route_not_found code for a known URL with the wrong method', function () {
    $response = $this->deleteJson('/api/v1/auth/login');

    $response->assertNotFound()->assertJsonPath('code', 'route_not_found');
});

it('returns 500 with the server_error code and hides the cause when debug is off', function () {
    config(['app.debug' => false]);
    Route::get('/api/v1/_rusak', fn () => throw new RuntimeException('rahasia internal'));

    $response = $this->getJson('/api/v1/_rusak');

    $response->assertInternalServerError()->assertExactJson([
        'message' => 'Terjadi kesalahan di server. Coba lagi nanti.',
        'code' => 'server_error',
    ]);
});

it('adds the cause to a server error when debug is on', function () {
    config(['app.debug' => true]);
    Route::get('/api/v1/_rusak', fn () => throw new RuntimeException('rahasia internal'));

    $response = $this->getJson('/api/v1/_rusak');

    $response->assertInternalServerError()
        ->assertJsonPath('code', 'server_error')
        ->assertJsonPath('debug.message', 'rahasia internal');
});

it('leaves errors outside the API to the default rendering', function () {
    $response = $this->get('/tidak-ada');

    $response->assertNotFound()->assertHeader('Content-Type', 'text/html; charset=utf-8');
});

<?php

it('adds the security headers to pages and API answers alike', function (string $uri) {
    $response = $this->get($uri, ['Accept' => 'application/json']);

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
})->with([
    'the admin login page' => ['/admin/login'],
    'an API error' => ['/api/v1/me'],
    'an unknown route' => ['/api/v1/tidak-ada'],
]);

it('sends HSTS over HTTPS only, so local development over HTTP is left alone', function () {
    $secure = $this->get('https://localhost/up');
    $plain = $this->get('http://localhost/up');

    $secure->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    $plain->assertHeaderMissing('Strict-Transport-Security');
});

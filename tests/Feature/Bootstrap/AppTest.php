<?php

it('builds HTTPS links for a request the proxy received over HTTPS', function () {
    $response = $this->get('/admin', ['X-Forwarded-Proto' => 'https']);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://')->toEndWith('/admin/login');
});

it('ignores a forwarded host, port, or prefix a client forged, so redirects stay on the real host', function (array $forged) {
    $response = $this->get('http://denlydays.test/admin', ['X-Forwarded-Proto' => 'https', ...$forged]);

    $response->assertRedirect('https://denlydays.test/admin/login');
})->with([
    'a host' => [['X-Forwarded-Host' => 'evil.example']],
    'a port' => [['X-Forwarded-Port' => '8443']],
    'a prefix' => [['X-Forwarded-Prefix' => '/jebakan']],
]);

it('counts the registration limit per client behind the proxy, not for the proxy as a whole', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/register', [], ['X-Forwarded-For' => '203.0.113.7']);
    }

    $response = $this->postJson('/api/v1/auth/register', [], ['X-Forwarded-For' => '198.51.100.9']);

    $response->assertUnprocessable();
});

it('returns 429 to a client that forges X-Forwarded-For entries in front of the address the proxy added', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/register', [], ['X-Forwarded-For' => "192.0.2.{$attempt}, 203.0.113.7"]);
    }

    $response = $this->postJson('/api/v1/auth/register', [], ['X-Forwarded-For' => '192.0.2.99, 203.0.113.7']);

    $response->assertTooManyRequests();
});

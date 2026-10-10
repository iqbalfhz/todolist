<?php

it('builds HTTPS links for a request the proxy received over HTTPS', function () {
    $response = $this->get('/admin', ['X-Forwarded-Proto' => 'https']);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://')->toEndWith('/admin/login');
});

it('counts the registration limit per client behind the proxy, not for the proxy as a whole', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/register', [], ['X-Forwarded-For' => '203.0.113.7']);
    }

    $response = $this->postJson('/api/v1/auth/register', [], ['X-Forwarded-For' => '198.51.100.9']);

    $response->assertUnprocessable();
});

<?php

test('the root names the API instead of showing the default Laravel page', function () {
    $response = $this->get('/');

    $response->assertOk()->assertExactJson(['name' => 'Denly Days API']);
});

<?php

test('the root sends a browser to the admin login', function () {
    $this->get('/')->assertFound()->assertRedirectToRoute('filament.admin.auth.login');
});

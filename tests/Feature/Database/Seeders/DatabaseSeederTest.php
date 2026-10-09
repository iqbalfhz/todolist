<?php

it('seeds the admin user together with the default data', function () {
    config(['admin.email' => 'admin@contoh.test']);

    $this->seed();

    $this->assertDatabaseHas('users', ['email' => 'admin@contoh.test']);
});

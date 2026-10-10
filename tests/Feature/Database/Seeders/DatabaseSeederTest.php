<?php

it('seeds the admin user and the demo accounts', function () {
    config(['admin.email' => 'admin@contoh.test']);

    $this->seed();

    $this->assertDatabaseHas('users', ['email' => 'admin@contoh.test']);
    $this->assertDatabaseHas('users', ['email' => 'aku@contoh.test']);
    $this->assertDatabaseHas('users', ['email' => 'pasangan@contoh.test']);
});

it('can be run again without creating anything twice', function () {
    config(['admin.email' => 'admin@contoh.test']);
    $this->seed();

    $this->seed();

    $this->assertDatabaseCount('users', 3);
});

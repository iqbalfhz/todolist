<?php

use Illuminate\Support\Facades\Artisan;

// docker/start-container runs `php artisan optimize` with `set -e`, so a step
// that fails stops the container and takes the site down. Deleting the last
// view once removed resources/views from the repository, and view:cache then
// failed on the server.
it('caches config, events, routes, and views the way the container does when it starts', function () {
    try {
        $this->artisan('optimize')->assertSuccessful();
    } finally {
        Artisan::call('optimize:clear');
    }
});

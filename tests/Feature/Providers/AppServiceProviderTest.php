<?php

use App\Providers\AppServiceProvider;

it('refuses to boot when the application timezone is not UTC', function () {
    config(['app.timezone' => 'Asia/Jakarta']);
    $provider = new AppServiceProvider($this->app);

    expect(fn () => $provider->boot())->toThrow(LogicException::class, 'APP_TIMEZONE harus UTC.');
});

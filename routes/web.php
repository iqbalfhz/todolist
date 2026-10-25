<?php

use Illuminate\Support\Facades\Route;

// The app talks only to /api/v1, so whoever opens the root in a browser is
// looking for the admin panel. Its login page sends a signed-in admin on to
// the dashboard.
Route::get('/', fn () => redirect()->route('filament.admin.auth.login'));

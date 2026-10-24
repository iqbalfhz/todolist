<?php

use Illuminate\Support\Facades\Route;

// The only client is the app, which talks to /api/v1. The root says what
// this is, without the default Laravel page that advertises the framework.
Route::get('/', fn () => response()->json(['name' => 'Denly Days API']));

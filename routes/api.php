<?php

use App\Http\Controllers\Api\V1\AccessTokenController;
use App\Http\Controllers\Api\V1\AvatarController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')
        ->name('auth.register');

    Route::post('/auth/login', [AccessTokenController::class, 'store'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::post('/auth/logout', [AccessTokenController::class, 'destroy'])->name('auth.logout');

        Route::get('/me', [MeController::class, 'show'])->name('me.show');
        Route::patch('/me', [MeController::class, 'update'])->name('me.update');
        Route::post('/me/avatar', [AvatarController::class, 'store'])->name('me.avatar.store');
        Route::delete('/me/avatar', [AvatarController::class, 'destroy'])->name('me.avatar.destroy');

        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::delete('/devices/{token}', [DeviceController::class, 'destroy'])->name('devices.destroy');
    });
});

<?php

use App\Http\Controllers\Api\V1\AcceptedInvitationController;
use App\Http\Controllers\Api\V1\AccessTokenController;
use App\Http\Controllers\Api\V1\AvatarController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\RegisteredUserController;
use App\Http\Controllers\Api\V1\SavingsEntryController;
use App\Http\Controllers\Api\V1\SavingsGoalController;
use App\Http\Controllers\Api\V1\SpaceController;
use App\Http\Controllers\Api\V1\SpecialDateController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TaskListController;
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

        Route::get('/spaces', [SpaceController::class, 'index'])->name('spaces.index');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::post('/invitations/accept', [AcceptedInvitationController::class, 'store'])
            ->middleware('throttle:invitation-accept')
            ->name('invitations.accept');

        Route::get('/sync', [SyncController::class, 'show'])->name('sync.show');

        Route::put('/lists/{id}', [TaskListController::class, 'store'])->name('lists.store');
        Route::patch('/lists/{id}', [TaskListController::class, 'update'])->name('lists.update');
        Route::delete('/lists/{id}', [TaskListController::class, 'destroy'])->name('lists.destroy');

        Route::put('/tasks/{id}', [TaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        Route::put('/savings-goals/{id}', [SavingsGoalController::class, 'store'])->name('savings-goals.store');
        Route::patch('/savings-goals/{id}', [SavingsGoalController::class, 'update'])->name('savings-goals.update');
        Route::delete('/savings-goals/{id}', [SavingsGoalController::class, 'destroy'])->name('savings-goals.destroy');

        Route::put('/savings-entries/{id}', [SavingsEntryController::class, 'store'])->name('savings-entries.store');
        Route::patch('/savings-entries/{id}', [SavingsEntryController::class, 'update'])->name('savings-entries.update');
        Route::delete('/savings-entries/{id}', [SavingsEntryController::class, 'destroy'])->name('savings-entries.destroy');

        Route::put('/special-dates/{id}', [SpecialDateController::class, 'store'])->name('special-dates.store');
        Route::patch('/special-dates/{id}', [SpecialDateController::class, 'update'])->name('special-dates.update');
        Route::delete('/special-dates/{id}', [SpecialDateController::class, 'destroy'])->name('special-dates.destroy');
    });
});

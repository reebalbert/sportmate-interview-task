<?php

use App\Http\Controllers\FailedJobController;
use App\Http\Controllers\SyncTargetController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// Synchronization Targets routes

Route::get('/sync-targets', [SyncTargetController::class, 'index'])->name('sync-targets.index');

Route::post('/sync-targets', [SyncTargetController::class, 'store'])->name('sync-targets.store');

Route::post('/sync-targets/{syncTarget}/sync', [SyncTargetController::class, 'sync'])->name('sync-targets.sync');

// Failed Jobs routes

Route::get('/failed-jobs', [FailedJobController::class, 'index'])->name('failed-jobs.index');

Route::post('/failed-jobs/{failedJob}/retry', [FailedJobController::class, 'retry'])->name('failed-jobs.retry');
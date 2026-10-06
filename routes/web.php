<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncTargetController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::post('/sync-targets', [SyncTargetController::class, 'store'])->name('sync-targets.store');

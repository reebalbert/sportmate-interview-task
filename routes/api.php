<?php

use App\Http\Controllers\Api\V1\SyncTargetController;
use App\Http\Controllers\Api\V1\RepositoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    Route::get('/sync-targets', [SyncTargetController::class, 'index']);

    Route::post('/sync-targets', [SyncTargetController::class, 'store']);

    Route::get('/sync-targets/{syncTarget}', [SyncTargetController::class, 'show']);

    Route::post('/sync-targets/{syncTarget}/sync', [SyncTargetController::class, 'sync']);

    Route::get('/sync-targets/{syncTarget}/repositories', [RepositoryController::class, 'index']);
});
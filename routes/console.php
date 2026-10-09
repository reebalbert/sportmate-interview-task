<?php

use App\Enums\SyncLogTrigger;
use App\Models\SyncTarget;
use App\Services\SyncTargetService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (SyncTargetService $syncTargetService) {
    SyncTarget::query()->each(function (SyncTarget $target) use ($syncTargetService) {
        $syncTargetService->sync($target, SyncLogTrigger::Scheduled);
    });
})->everySixHours();

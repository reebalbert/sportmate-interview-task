<?php

namespace App\Http\Controllers;

use App\Models\FailedJob;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class FailedJobController extends Controller
{
    /**
     * Display the failed queue jobs.
     */
    public function index(): Response
    {
        $failedJobs = FailedJob::query()->latest('failed_at')->get();

        return Inertia::render('failed-jobs/Index', [
            'failedJobs' => $failedJobs,
        ]);
    }

    /**
     * Retry the given failed queue job.
     * 
     * @param  \App\Models\FailedJob  $failedJob
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry(FailedJob $failedJob): RedirectResponse
    {
        Artisan::call('queue:retry', [
            'id' => [$failedJob->uuid],
        ]);

        return back();
    }
}
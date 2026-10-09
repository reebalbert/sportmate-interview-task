<?php

namespace App\Jobs;

use App\Models\Repository;
use App\Services\GitHub\GitHubClient;
use App\Models\SyncLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use DateTimeInterface;

class SyncGitHubRepositoryReadme implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 100;

    /**
     * Create a new job instance.
     * 
     * @param Repository $repository
     * @param int $syncLogId
     * 
     * @return void
     */
    public function __construct(public Repository $repository, public int $syncLogId)
    {
    }

    /**
     * Synchronize the repository README content.
     * 
     * @param GitHubClient $github
     * 
     * @return void
     */
    public function handle(GitHubClient $github): void
    {
        $readmeContent = $github->readme($this->repository->full_name);

        DB::transaction(function () use ($readmeContent) {
            $this->repository->update([
                'readme_content' => $readmeContent,
            ]);

            SyncLog::whereKey($this->syncLogId)->increment('readmes_synced');
        });
    }

    /**
     * Determine the retry delays in seconds.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * Get the middleware the job should pass through.
     * 
     * @return array<int, \Illuminate\Queue\Middleware\RateLimited>
     */
    public function middleware(): array
    {
        return [new RateLimited('github-readme')];
    }

    /**
     * Determine when the job should stop being retried.
     * 
     * @return DateTimeInterface
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addHour();
    }

    /**
     * Handle a permanently failed README synchronization.
     * 
     * @param Throwable $exception
     * 
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('GitHub README synchronization failed.', [
            'repository_id' => $this->repository->id,
            'sync_log_id' => $this->syncLogId,
            'exception' => $exception,
        ]);

        SyncLog::whereKey($this->syncLogId)->increment('readmes_failed');
    }
}
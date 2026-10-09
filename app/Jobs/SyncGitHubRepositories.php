<?php

namespace App\Jobs;

use App\Enums\SyncLogStatus;
use App\Enums\SyncStatus;
use App\Models\SyncLog;
use App\Models\SyncTarget;
use App\Services\GitHub\GitHubClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGitHubRepositories implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     * 
     * @param SyncTarget $target
     * @return void
     */
    public function __construct(public SyncTarget $target, public int $syncLogId)
    {
    }

    /**
     * Execute the job.
     * 
     * @param GitHubClient $github
     * @return void
     */
    public function handle(GitHubClient $github): void
    {
        $syncLog = SyncLog::findOrFail($this->syncLogId);

        $this->target->update([
            'status' => SyncStatus::Syncing,
            'last_error' => null,
        ]);

        $repositories = $github->repositories($this->target);

        $created = 0;
        $updated = 0;

        foreach ($repositories as $repository) {
            $savedRepository = $this->target->repositories()->updateOrCreate(
                ['github_id' => $repository['id']],
                [
                    'name' => $repository['name'],
                    'full_name' => $repository['full_name'],
                    'description' => $repository['description'],
                    'html_url' => $repository['html_url'],
                    'language' => $repository['language'],
                    'stargazers_count' => $repository['stargazers_count'],
                    'open_issues_count' => $repository['open_issues_count'],
                    'archived' => $repository['archived'],
                    'github_updated_at' => $repository['updated_at'],
                ]
            );

            if ($savedRepository->wasRecentlyCreated) {
                $created++;
            } elseif ($savedRepository->wasChanged()) {
                $updated++;
            }

            if ($savedRepository->wasRecentlyCreated || $savedRepository->wasChanged('github_updated_at')) {
                SyncGitHubRepositoryReadme::dispatch($savedRepository, $this->syncLogId);
                $syncLog->increment('readmes_queued');
            }
        }

        $githubRepositoryIds = collect($repositories)->pluck('id');

        $deleted = $this->target->repositories()
            ->whereNotIn('github_id', $githubRepositoryIds)
            ->delete();

        $this->target->update([
            'status' => SyncStatus::Synced,
            'last_synced_at' => now(),
            'last_error' => null,
        ]);

        $syncLog->update([
            'status' => SyncLogStatus::Success,
            'finished_at' => now(),
            'repositories_created' => $created,
            'repositories_updated' => $updated,
            'repositories_deleted' => $deleted,
            'error_message' => null,
        ]);
    }

    /**
     * Prevent concurrent synchronization of the same target.
     *
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("github-sync-target-{$this->target->id}"))
                ->releaseAfter(10)
                ->expireAfter(120),
        ];
    }

    /**
     * Determine the delay before retrying the job.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    /**
     * Handle a permanently failed synchronization.
     * 
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('GitHub repository synchronization failed.', [
            'sync_target_id' => $this->target->id,
            'target' => $this->target->name,
            'exception' => $exception,
        ]);

        $this->target->update([
            'status' => SyncStatus::Failed,
            'last_error' => $exception->getMessage(),
        ]);

        SyncLog::whereKey($this->syncLogId)->update([
            'status' => SyncLogStatus::Failed,
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}
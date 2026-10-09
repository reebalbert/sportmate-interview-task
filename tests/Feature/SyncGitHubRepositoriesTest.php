<?php

namespace Tests\Feature;

use App\Enums\SyncLogStatus;
use App\Enums\SyncLogTrigger;
use App\Enums\SyncStatus;
use App\Enums\SyncTargetType;
use App\Jobs\SyncGitHubRepositories;
use App\Jobs\SyncGitHubRepositoryReadme;
use App\Models\Repository;
use App\Models\SyncTarget;
use App\Services\GitHub\GitHubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncGitHubRepositoriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that repositories are synchronized and the target is marked as synced.
     * 
     * @return void
     */
    public function test_it_synchronizes_repositories(): void
    {
        Queue::fake();

        Http::fake([
            'api.github.com/users/test-user/repos*' => Http::response([
                [
                    'id' => 123,
                    'name' => 'example-repository',
                    'full_name' => 'test-user/example-repository',
                    'description' => 'Example repository',
                    'html_url' => 'https://github.com/test-user/example-repository',
                    'language' => 'PHP',
                    'stargazers_count' => 42,
                    'open_issues_count' => 3,
                    'archived' => false,
                    'updated_at' => '2026-10-06T12:00:00Z',
                ],
            ]),
        ]);

        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $syncLog = $target->syncLogs()->create([
            'status' => SyncLogStatus::Running,
            'trigger' => SyncLogTrigger::Manual,
            'started_at' => now(),
        ]);

        $job = new SyncGitHubRepositories($target, $syncLog->id);
        $job->handle(app(GitHubClient::class));

        $target->refresh();

        $this->assertSame(SyncStatus::Synced, $target->status);
        $this->assertNotNull($target->last_synced_at);
        $this->assertNull($target->last_error);

        $this->assertDatabaseHas('repositories', [
            'sync_target_id' => $target->id,
            'github_id' => 123,
            'name' => 'example-repository',
            'stargazers_count' => 42,
        ]);

        $this->assertSame(1, Repository::count());

        $syncLog->refresh();

        $this->assertSame(SyncLogStatus::Success, $syncLog->status);
        $this->assertSame(1, $syncLog->repositories_created);
        $this->assertSame(0, $syncLog->repositories_updated);
        $this->assertSame(0, $syncLog->repositories_deleted);
        $this->assertSame(1, $syncLog->readmes_queued);
        $this->assertNotNull($syncLog->finished_at);

        Queue::assertPushed(SyncGitHubRepositoryReadme::class, function ($job) use ($syncLog) {
            return $job->syncLogId === $syncLog->id;
        });
    }

    /**
     * Test that an existing repository is updated instead of duplicated.
     * 
     * @return void
     */
    public function test_it_updates_existing_repository_without_creating_duplicate(): void
    {
        Queue::fake();

        Http::fake([
            'api.github.com/users/test-user/repos*' => Http::response([
                [
                    'id' => 123,
                    'name' => 'updated-repository',
                    'full_name' => 'test-user/updated-repository',
                    'description' => 'Updated description',
                    'html_url' => 'https://github.com/test-user/updated-repository',
                    'language' => 'PHP',
                    'stargazers_count' => 100,
                    'open_issues_count' => 5,
                    'archived' => false,
                    'updated_at' => '2026-10-06T14:00:00Z',
                ],
            ]),
        ]);

        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $syncLog = $target->syncLogs()->create([
            'status' => SyncLogStatus::Running,
            'trigger' => SyncLogTrigger::Manual,
            'started_at' => now(),
        ]);

        $target->repositories()->create([
            'github_id' => 123,
            'name' => 'old-repository',
            'full_name' => 'test-user/old-repository',
            'html_url' => 'https://github.com/test-user/old-repository',
        ]);

        $job = new SyncGitHubRepositories($target, $syncLog->id);
        $job->handle(app(GitHubClient::class));

        $this->assertSame(1, Repository::count());
        $this->assertDatabaseHas('repositories', [
            'github_id' => 123,
            'name' => 'updated-repository',
            'stargazers_count' => 100,
        ]);

        $syncLog->refresh();

        $this->assertSame(SyncLogStatus::Success, $syncLog->status);
        $this->assertSame(0, $syncLog->repositories_created);
        $this->assertSame(1, $syncLog->repositories_updated);
        $this->assertSame(0, $syncLog->repositories_deleted);
        $this->assertSame(1, $syncLog->readmes_queued);

        Queue::assertPushed(SyncGitHubRepositoryReadme::class, function ($job) use ($syncLog) {
            return $job->syncLogId === $syncLog->id;
        });
    }

    /**
     * Test that a failed synchronization updates the target status and error.
     * 
     * @return void
     */
    public function test_it_marks_target_as_failed_when_synchronization_fails(): void
    {
        Http::fake([
            'api.github.com/users/test-user/repos*' => Http::response(['message' => 'GitHub API error'], 500),
        ]);

        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $syncLog = $target->syncLogs()->create([
            'status' => SyncLogStatus::Running,
            'trigger' => SyncLogTrigger::Manual,
            'started_at' => now(),
        ]);

        $job = new SyncGitHubRepositories($target, $syncLog->id);

        $exception = null;

        try {
            $job->handle(app(GitHubClient::class));
        } catch (\Throwable $caughtException) {
            $exception = $caughtException;
        }

        $this->assertNotNull($exception, 'Expected GitHub synchronization to fail.');

        $job->failed($exception);

        $syncLog->refresh();

        $this->assertSame(SyncLogStatus::Failed, $syncLog->status);
        $this->assertNotNull($syncLog->error_message);
        $this->assertNotNull($syncLog->finished_at);
        $this->assertSame(0, $syncLog->readmes_queued);

        $target->refresh();

        $this->assertSame(SyncStatus::Failed, $target->status);
        $this->assertNotNull($target->last_error);
        $this->assertNull($target->last_synced_at);
        $this->assertSame(0, Repository::count());
    }
}
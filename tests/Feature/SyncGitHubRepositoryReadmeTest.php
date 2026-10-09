<?php

namespace Tests\Feature;

use App\Enums\SyncLogStatus;
use App\Enums\SyncLogTrigger;
use App\Enums\SyncTargetType;
use App\Jobs\SyncGitHubRepositoryReadme;
use App\Models\SyncTarget;
use App\Services\GitHub\GitHubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SyncGitHubRepositoryReadmeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that README content is saved and the success counter is incremented.
     * 
     * @return void
     */
    public function test_it_synchronizes_repository_readme(): void
    {
        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $repository = $target->repositories()->create([
            'github_id' => 123,
            'name' => 'example-repository',
            'full_name' => 'test-user/example-repository',
            'html_url' => 'https://github.com/test-user/example-repository',
        ]);

        $syncLog = $target->syncLogs()->create([
            'status' => SyncLogStatus::Running,
            'trigger' => SyncLogTrigger::Manual,
            'started_at' => now(),
            'readmes_queued' => 1,
        ]);

        $github = Mockery::mock(GitHubClient::class);

        $github->shouldReceive('readme')
            ->once()
            ->with('test-user/example-repository')
            ->andReturn('# Example README');

        $job = new SyncGitHubRepositoryReadme($repository, $syncLog->id);
        $job->handle($github);

        $this->assertSame('# Example README', $repository->fresh()->readme_content);
        $this->assertSame(1, $syncLog->fresh()->readmes_synced);
        $this->assertSame(0, $syncLog->fresh()->readmes_failed);
    }

    /**
     * Test that a permanently failed README synchronization increments the failure counter.
     */
    public function test_it_records_failed_readme_synchronization(): void
    {
        Log::spy();

        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $repository = $target->repositories()->create([
            'github_id' => 123,
            'name' => 'example-repository',
            'full_name' => 'test-user/example-repository',
            'html_url' => 'https://github.com/test-user/example-repository',
        ]);

        $syncLog = $target->syncLogs()->create([
            'status' => SyncLogStatus::Running,
            'trigger' => SyncLogTrigger::Manual,
            'started_at' => now(),
            'readmes_queued' => 1,
        ]);

        $exception = new RuntimeException('GitHub API error');

        $job = new SyncGitHubRepositoryReadme($repository, $syncLog->id);
        $job->failed($exception);

        $this->assertSame(0, $syncLog->fresh()->readmes_synced);
        $this->assertSame(1, $syncLog->fresh()->readmes_failed);
        $this->assertNull($repository->fresh()->readme_content);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('GitHub README synchronization failed.', [
                'repository_id' => $repository->id,
                'sync_log_id' => $syncLog->id,
                'exception' => $exception,
            ]);
    }

    /**
     * Test that README synchronization updates only the associated sync log.
     */
    public function test_it_updates_only_the_associated_sync_log(): void
    {
        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $repository = $target->repositories()->create([
            'github_id' => 123,
            'name' => 'example-repository',
            'full_name' => 'test-user/example-repository',
            'html_url' => 'https://github.com/test-user/example-repository',
        ]);

        $firstSyncLog = $target->syncLogs()->create([
            'status' => SyncLogStatus::Success,
            'trigger' => SyncLogTrigger::Manual,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour(),
            'readmes_queued' => 1,
            'readmes_synced' => 1,
        ]);

        $secondSyncLog = $target->syncLogs()->create([
            'status' => SyncLogStatus::Running,
            'trigger' => SyncLogTrigger::Manual,
            'started_at' => now(),
            'readmes_queued' => 1,
        ]);

        $github = Mockery::mock(GitHubClient::class);

        $github->shouldReceive('readme')
            ->once()
            ->with('test-user/example-repository')
            ->andReturn('# Updated README');

        $job = new SyncGitHubRepositoryReadme($repository, $secondSyncLog->id);
        $job->handle($github);

        $this->assertSame(1, $firstSyncLog->fresh()->readmes_synced);
        $this->assertSame(0, $firstSyncLog->fresh()->readmes_failed);
        $this->assertSame(1, $secondSyncLog->fresh()->readmes_synced);
        $this->assertSame(0, $secondSyncLog->fresh()->readmes_failed);
        $this->assertSame('# Updated README', $repository->fresh()->readme_content);
    }
}

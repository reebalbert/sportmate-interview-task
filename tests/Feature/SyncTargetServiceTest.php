<?php

namespace Tests\Feature;

use App\Enums\SyncLogStatus;
use App\Enums\SyncLogTrigger;
use App\Enums\SyncStatus;
use App\Enums\SyncTargetType;
use App\Jobs\SyncGitHubRepositories;
use App\Models\SyncLog;
use App\Models\SyncTarget;
use App\Services\SyncTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncTargetServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that synchronization creates a log and dispatches a job.
     * 
     * @return void
     */
    public function test_it_queues_synchronization(): void
    {
        Queue::fake();

        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $result = app(SyncTargetService::class)->sync($target, SyncLogTrigger::Manual);

        $this->assertTrue($result);
        $this->assertSame(SyncStatus::Syncing, $target->fresh()->status);
        $this->assertSame(1, SyncLog::count());

        $syncLog = SyncLog::firstOrFail();

        $this->assertSame($target->id, $syncLog->sync_target_id);
        $this->assertSame(SyncLogStatus::Running, $syncLog->status);
        $this->assertSame(SyncLogTrigger::Manual, $syncLog->trigger);
        $this->assertNotNull($syncLog->started_at);

        Queue::assertPushed(SyncGitHubRepositories::class, function ($job) use ($target, $syncLog) {
            return $job->target->id === $target->id && $job->syncLogId === $syncLog->id;
        });
    }

    /**
     * Test that an already syncing target cannot be queued again.
     * 
     * @return void
     */
    public function test_it_prevents_duplicate_synchronization(): void
    {
        Queue::fake();

        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $service = app(SyncTargetService::class);

        $this->assertTrue($service->sync($target, SyncLogTrigger::Manual));
        $this->assertFalse($service->sync($target, SyncLogTrigger::Manual));

        $this->assertSame(1, SyncLog::count());
        Queue::assertPushed(SyncGitHubRepositories::class, 1);
    }

    /**
     * Test that different targets can be queued independently.
     * 
     * @return void
     */
    public function test_it_allows_synchronization_of_different_targets(): void
    {
        Queue::fake();

        $firstTarget = SyncTarget::create([
            'name' => 'first-user',
            'type' => SyncTargetType::User,
        ]);

        $secondTarget = SyncTarget::create([
            'name' => 'second-user',
            'type' => SyncTargetType::User,
        ]);

        $service = app(SyncTargetService::class);

        $this->assertTrue($service->sync($firstTarget, SyncLogTrigger::Manual));
        $this->assertTrue($service->sync($secondTarget, SyncLogTrigger::Manual));

        $this->assertSame(2, SyncLog::count());
        $this->assertSame(SyncStatus::Syncing, $firstTarget->fresh()->status);
        $this->assertSame(SyncStatus::Syncing, $secondTarget->fresh()->status);

        Queue::assertPushed(SyncGitHubRepositories::class, 2);
    }

    /**
     * Test that scheduled synchronization uses the correct trigger.
     * 
     * @return void
     */
    public function test_it_records_scheduled_synchronization(): void
    {
        Queue::fake();

        $target = SyncTarget::create([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $result = app(SyncTargetService::class)->sync($target, SyncLogTrigger::Scheduled);

        $this->assertTrue($result);
        $this->assertSame(SyncLogTrigger::Scheduled, SyncLog::firstOrFail()->trigger);

        Queue::assertPushed(SyncGitHubRepositories::class, 1);
    }
}

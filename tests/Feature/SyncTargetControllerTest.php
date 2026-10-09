<?php

namespace Tests\Feature;

use App\Enums\SyncTargetType;
use App\Jobs\SyncGitHubRepositories;
use App\Models\SyncTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncTargetControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that synchronization can be queued for a target.
     */
    public function test_it_queues_synchronization_for_target(): void
    {
        Queue::fake();

        $target = SyncTarget::create([
            'name' => 'laravel',
            'type' => SyncTargetType::Organization,
        ]);

        $response = $this->post(route('sync-targets.sync', $target));

        $response->assertRedirect();

        Queue::assertPushed(
            SyncGitHubRepositories::class,
            fn (SyncGitHubRepositories $job) => $job->target->is($target)
        );
        
    }

    /**
     * Test that a synchronization target can be created.
     * 
     * @return void
     */
    public function test_it_creates_a_synchronization_target(): void
    {
        $response = $this->post(route('sync-targets.store'), [
            'name' => 'laravel',
            'type' => SyncTargetType::Organization->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('sync_targets', [
            'name' => 'laravel',
            'type' => SyncTargetType::Organization->value,
        ]);
    }
}
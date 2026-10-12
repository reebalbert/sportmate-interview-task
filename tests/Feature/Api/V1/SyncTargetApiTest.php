<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SyncGitHubRepositories;
use App\Models\SyncTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncTargetApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure unauthenticated users cannot access synchronization targets.
     */
    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/v1/sync-targets')
            ->assertUnauthorized();
    }

    /**
     * Ensure authenticated users can retrieve synchronization targets.
     */
    public function test_it_lists_synchronization_targets(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/sync-targets')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Ensure authenticated users can retrieve a specific synchronization target.
     */
    public function test_it_shows_a_synchronization_target(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $syncTarget = SyncTarget::factory()->create();

        $this->getJson("/api/v1/sync-targets/{$syncTarget->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $syncTarget->id)
            ->assertJsonPath('data.name', $syncTarget->name);
    }

    /**
     * Ensure authenticated users can create synchronization targets.
     */
    public function test_it_creates_a_synchronization_target(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/sync-targets', [
            'name' => 'laravel',
            'type' => 'organization',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'laravel')
            ->assertJsonPath('data.type', 'organization');

        $this->assertDatabaseHas('sync_targets', [
            'name' => 'laravel',
            'type' => 'organization',
        ]);
    }

    /**
     * Ensure synchronization targets cannot have duplicate names.
     */
    public function test_it_rejects_duplicate_synchronization_target_names(): void
    {
        Sanctum::actingAs(User::factory()->create());

        SyncTarget::factory()->create([
            'name' => 'laravel',
        ]);

        $this->postJson('/api/v1/sync-targets', [
            'name' => 'laravel',
            'type' => 'organization',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('sync_targets', 1);
    }

    /**
     * Ensure authenticated users can queue synchronization for a target.
     */
    public function test_it_queues_synchronization_for_a_target(): void
    {
        Queue::fake();

        Sanctum::actingAs(User::factory()->create());

        $syncTarget = SyncTarget::factory()->create();

        $this->postJson("/api/v1/sync-targets/{$syncTarget->id}/sync")
            ->assertAccepted()
            ->assertJsonPath('message', 'Synchronization has been queued.');

        Queue::assertPushed(SyncGitHubRepositories::class, 1);

        $this->assertDatabaseHas('sync_targets', [
            'id' => $syncTarget->id,
            'status' => 'syncing',
        ]);
    }

    /**
     * Ensure the API returns only repositories belonging to the specified target.
     */
    public function test_it_lists_repositories_for_a_target(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $syncTarget = SyncTarget::factory()->create();
        $otherTarget = SyncTarget::factory()->create();

        $repository = $syncTarget->repositories()->create([
            'github_id' => 123456,
            'name' => 'test-repository',
            'full_name' => 'test-user/test-repository',
            'html_url' => 'https://github.com/test-user/test-repository',
        ]);

        $otherTarget->repositories()->create([
            'github_id' => 654321,
            'name' => 'other-repository',
            'full_name' => 'other-user/other-repository',
            'html_url' => 'https://github.com/other-user/other-repository',
        ]);

        $this->getJson("/api/v1/sync-targets/{$syncTarget->id}/repositories")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $repository->id)
            ->assertJsonPath('data.0.name', 'test-repository')
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }
}

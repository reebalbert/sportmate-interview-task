<?php

namespace Tests\Feature;

use App\Enums\SyncTargetType;
use App\Models\SyncTarget;
use App\Services\GitHub\GitHubClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubClientTest extends TestCase
{
    /**
     * Test that repositories can be fetched for a GitHub user.
     */
    public function test_it_fetches_repositories_for_user(): void
    {
        Http::fake([
            'api.github.com/users/laravel/repos*' => Http::response([
                ['id' => 1, 'name' => 'framework'],
                ['id' => 2, 'name' => 'docs'],
            ]),
        ]);

        $target = new SyncTarget([
            'name' => 'laravel',
            'type' => SyncTargetType::User,
        ]);

        $repositories = app(GitHubClient::class)->repositories($target);

        $this->assertCount(2, $repositories);
        $this->assertSame('framework', $repositories[0]['name']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/users/laravel/repos?per_page=100&page=1');
    }

    /**
     * Test that repositories can be fetched for a GitHub organization.
     */
    public function test_it_fetches_repositories_for_organization(): void
    {
        Http::fake([
            'api.github.com/orgs/laravel/repos*' => Http::response([
                ['id' => 1, 'name' => 'framework'],
                ['id' => 2, 'name' => 'docs'],
            ]),
        ]);

        $target = new SyncTarget([
            'name' => 'laravel',
            'type' => SyncTargetType::Organization,
        ]);

        $repositories = app(GitHubClient::class)->repositories($target);

        $this->assertCount(2, $repositories);
        $this->assertSame('framework', $repositories[0]['name']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/orgs/laravel/repos?per_page=100&page=1');
    }

    /**
     * Test that all repository pages are fetched.
     */
    public function test_it_fetches_paginated_repositories(): void
    {
        $firstPage = array_map(fn ($id) => ['id' => $id, 'name' => "repo-{$id}"], range(1, 100));

        Http::fake([
            'api.github.com/users/test-user/repos?per_page=100&page=1' => Http::response($firstPage),
            'api.github.com/users/test-user/repos?per_page=100&page=2' => Http::response([
                ['id' => 101, 'name' => 'repo-101'],
            ]),
        ]);

        $target = new SyncTarget([
            'name' => 'test-user',
            'type' => SyncTargetType::User,
        ]);

        $repositories = app(GitHubClient::class)->repositories($target);

        $this->assertCount(101, $repositories);
        $this->assertSame('repo-1', $repositories[0]['name']);
        $this->assertSame('repo-101', $repositories[100]['name']);

        Http::assertSentCount(2);
    }
}
<?php

namespace App\Services\GitHub;

use App\Enums\SyncTargetType;
use App\Models\SyncTarget;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GitHubClient
{
    /**
     * Get the repositories for the given synchronization target.
     * 
     * @param SyncTarget $target The synchronization target (user or organization).
     * 
     * @return array
     */
    public function repositories(SyncTarget $target): array
    {
        $endpoint = match ($target->type) {
            SyncTargetType::User => "/users/{$target->name}/repos",
            SyncTargetType::Organization => "/orgs/{$target->name}/repos",
        };

        $repositories = [];
        $page = 1;

        do {
            $response = $this->client()->get($endpoint, [
                'per_page' => 100,
                'page' => $page,
            ])->throw();

            $items = $response->json();
            $repositories = array_merge($repositories, $items);
            $page++; 
        } while (count($items) === 100);

        return $repositories;
    }

    /**
     * Create the configured GitHub API client.
     * 
     * @return PendingRequest
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.github.url'))
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->when(config('services.github.token'), fn (PendingRequest $request, string $token) => $request->withToken($token))
            ->timeout(15);
    }

    /**
     * Retrieve the README content of a GitHub repository.
     * 
     * @param string $fullName The full name of the repository (e.g. `owner/name`).
     * 
     * @return string|null
     */
    public function readme(string $fullName): ?string
    {
        $response = $this->client()->get("/repos/{$fullName}/readme");

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        $content = $response->json('content');

        if (! is_string($content)) {
            return null;
        }

        $decoded = base64_decode($content, true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * Check whether the GitHub target exists and matches the selected type.
     *
     * @param string $name The name of the target.
     * @param SyncTargetType $type The expected account type.
     *
     * @return bool
     */
    public function targetExists(string $name, SyncTargetType $type): bool
    {
        $response = $this->client()->get("/users/{$name}");

        if ($response->notFound()) {
            return false;
        }

        $response->throw();

        return $response->json('type') === match ($type) {
            SyncTargetType::User => 'User',
            SyncTargetType::Organization => 'Organization',
        };
    }
}
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
     * Check whether the GitHub target exists.
     * 
     * @param string $name The name of the target (user or organization).
     * @param SyncTargetType $type The type of the target (user or organization).
     * 
     * @return bool
     */
    public function targetExists(string $name, SyncTargetType $type): bool
    {
        $endpoint = match ($type) {
            SyncTargetType::User => "/users/{$name}",
            SyncTargetType::Organization => "/orgs/{$name}",
        };

        $response = $this->client()->get($endpoint);

        if ($response->notFound()) {
            return false;
        }

        $response->throw();

        return true;
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepositoryResource extends JsonResource
{
    /**
     * Transform the repository into an array.
     * 
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'github_id' => $this->github_id,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'description' => $this->description,
            'html_url' => $this->html_url,
            'language' => $this->language,
            'stargazers_count' => $this->stargazers_count,
            'open_issues_count' => $this->open_issues_count,
            'archived' => $this->archived,
            'github_updated_at' => $this->github_updated_at?->toISOString(),
            'readme_content' => $this->readme_content,
        ];
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository extends Model
{
    protected $fillable = [
        'github_id',
        'name',
        'full_name',
        'description',
        'readme_content',
        'html_url',
        'language',
        'stargazers_count',
        'open_issues_count',
        'archived',
        'github_updated_at',
    ];

    /**
     * Get the synchronization target that owns the repository.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function syncTarget(): BelongsTo
    {
        return $this->belongsTo(SyncTarget::class);
    }

    /**
     * Get the attributes that should be cast.
     * 
     * @return array
     */
    protected function casts(): array
    {
        return [
            'archived' => 'boolean',
            'github_updated_at' => 'datetime',
        ];
    }
}
<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Enums\SyncTargetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncTarget extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'last_synced_at',
        'last_error',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'type' => SyncTargetType::class,
            'status' => SyncStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the repositories belonging to this synchronization target.
     */
    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    /**
     * Get the synchronization history for this target.
     * 
     * @return HasMany
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }
}
<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Enums\SyncTargetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SyncTarget extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'status',
        'last_synced_at',
        'last_error',
    ];

    /**
     * Get the attributes that should be cast.
     * 
     * @return array
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
     * 
     * @return HasMany
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
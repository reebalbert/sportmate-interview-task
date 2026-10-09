<?php

namespace App\Models;

use App\Enums\SyncLogStatus;
use App\Enums\SyncLogTrigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sync_target_id',
        'status',
        'trigger',
        'started_at',
        'finished_at',
        'repositories_created',
        'repositories_updated',
        'repositories_deleted',
        'error_message',
        'readmes_queued',
        'readmes_synced',
        'readmes_failed',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected function casts(): array
    {
        return [
            'status' => SyncLogStatus::class,
            'trigger' => SyncLogTrigger::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Get the synchronization target associated with this log.
     */
    public function syncTarget(): BelongsTo
    {
        return $this->belongsTo(SyncTarget::class);
    }
}
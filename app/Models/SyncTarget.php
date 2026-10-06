<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Enums\SyncTargetType;
use Illuminate\Database\Eloquent\Model;

class SyncTarget extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => SyncTargetType::class,
            'status' => SyncStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }
}
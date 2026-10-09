<?php

namespace App\Services;

use App\Enums\SyncLogStatus;
use App\Enums\SyncLogTrigger;
use App\Enums\SyncStatus;
use App\Jobs\SyncGitHubRepositories;
use App\Models\SyncTarget;
use Illuminate\Support\Facades\DB;

class SyncTargetService
{
    /**
     * Queue synchronization if the target is not already syncing.
     */
    public function sync(SyncTarget $syncTarget, SyncLogTrigger $trigger): bool
    {
        return DB::transaction(function () use ($syncTarget, $trigger) {
            // Lock the target to prevent concurrent synchronization requests.
            $target = SyncTarget::query()->lockForUpdate()->findOrFail($syncTarget->id);

            // Skip targets that are already being synchronized.
            if ($target->status === SyncStatus::Syncing) {
                return false;
            }

            $syncLog = $target->syncLogs()->create([
                'status' => SyncLogStatus::Running,
                'trigger' => $trigger,
                'started_at' => now(),
            ]);

            $target->update([
                'status' => SyncStatus::Syncing,
                'last_error' => null,
            ]);

            // Dispatch the job only after the transaction is committed.
            SyncGitHubRepositories::dispatch($target, $syncLog->id)->afterCommit();

            return true;
        });
    }
}

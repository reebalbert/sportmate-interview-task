<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SyncTargetResource;
use App\Models\SyncTarget;
use App\Enums\SyncLogTrigger;
use App\Services\SyncTargetService;
use App\Http\Requests\StoreSyncTargetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SyncTargetController extends Controller
{
    /**
     * Display a listing of synchronization targets.
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return SyncTargetResource::collection(
            SyncTarget::query()->latest()->get()
        );
    }

    /**
     * Display the specified synchronization target.
     * 
     * @param SyncTarget $syncTarget
     * 
     * @return SyncTargetResource
     */
    public function show(SyncTarget $syncTarget): SyncTargetResource
    {
        return new SyncTargetResource($syncTarget);
    }

    /**
     * Store a newly created synchronization target.
     * 
     * @param StoreSyncTargetRequest $request
     * 
     * @return JsonResponse
     */
    public function store(StoreSyncTargetRequest $request): JsonResponse
    {
        $syncTarget = SyncTarget::create($request->validated());

        return (new SyncTargetResource($syncTarget))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Queue synchronization for the specified target.
     * 
     * @param SyncTarget $syncTarget
     * @param SyncTargetService $syncTargetService
     * 
     * @return JsonResponse
     */
    public function sync(SyncTarget $syncTarget, SyncTargetService $syncTargetService): JsonResponse
    {
        if (! $syncTargetService->sync($syncTarget, SyncLogTrigger::Manual)) {
            return response()->json(['message' => 'Synchronization is already in progress.'], 409);
        }

        return response()->json(['message' => 'Synchronization has been queued.'], 202);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RepositoryResource;
use App\Models\SyncTarget;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RepositoryController extends Controller
{
    /**
     * Display a paginated listing of repositories for the specified target.
     */
    public function index(SyncTarget $syncTarget): AnonymousResourceCollection
    {
        $repositories = $syncTarget->repositories()
            ->orderBy('name')
            ->paginate(20);

        return RepositoryResource::collection($repositories);
    }
}
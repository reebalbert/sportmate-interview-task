<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSyncTargetRequest;
use App\Models\SyncTarget;
use Illuminate\Http\RedirectResponse;

class SyncTargetController extends Controller
{
    public function store(StoreSyncTargetRequest $request): RedirectResponse
    {
        SyncTarget::create($request->validated());

        return back();
    }
}
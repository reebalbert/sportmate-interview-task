<?php

namespace App\Http\Controllers;

use App\Enums\SyncStatus;
use App\Http\Requests\StoreSyncTargetRequest;
use App\Jobs\SyncGitHubRepositories;
use App\Models\SyncTarget;
use App\Enums\SyncLogStatus;
use App\Enums\SyncLogTrigger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\SyncTargetService;
use App\Enums\SyncTargetType;
use App\Services\GitHub\GitHubClient;
use Illuminate\Validation\ValidationException;

class SyncTargetController extends Controller
{
    /**
     * Display the synchronization targets.
     * 
     * @return \Inertia\Response
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $targetId = $request->integer('target_id');

        $targets = SyncTarget::query()
            ->withCount('repositories')
            ->withSum('repositories as total_stars', 'stargazers_count')
            ->withSum('repositories as total_issues', 'open_issues_count')
            ->with(['repositories' => function ($query) use ($search, $targetId) {
                $query->select([
                    'id',
                    'sync_target_id',
                    'github_id',
                    'name',
                    'full_name',
                    'description',
                    'html_url',
                    'language',
                    'stargazers_count',
                    'open_issues_count',
                    'archived',
                    'github_updated_at',
                ]);

                if ($search !== '' && $targetId > 0) {
                    $query->where(function ($query) use ($search, $targetId) {
                        $query->where('sync_target_id', '!=', $targetId);

                        if (DB::getDriverName() === 'mysql') {
                            $query->orWhereFullText(
                                ['name', 'full_name', 'description', 'readme_content'],
                                $search
                            );
                        } else {
                            $query->orWhere(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('full_name', 'like', "%{$search}%")
                                    ->orWhere('description', 'like', "%{$search}%")
                                    ->orWhere('readme_content', 'like', "%{$search}%");
                            });
                        }
                    });
                }

                $query->orderByDesc('stargazers_count');
            }])
            ->latest()
            ->get();

        return Inertia::render('sync-targets/Index', [
            'targets' => $targets,
            'filters' => [
                'search' => $search,
                'target_id' => $targetId,
            ],
        ]);
    }

    /**
     * Store a newly created synchronization target.
     * 
     * @param  \App\Http\Requests\StoreSyncTargetRequest  $request
     * @param  \App\Services\GitHub\GitHubClient  $github
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreSyncTargetRequest $request, GitHubClient $github): RedirectResponse
    {
        $data = $request->validated();

        if (! $github->targetExists($data['name'], SyncTargetType::from($data['type']))) {
            throw ValidationException::withMessages([
                'name' => 'The GitHub account does not exist or does not match the selected type.',
            ]);
        }

        SyncTarget::create($data);

        return back();
    }

    /**
     * Queue the synchronization of the given target.
     * 
     * @param  SyncTarget $syncTarget
     * @param  \SyncTargetService $syncTargetService
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sync(SyncTarget $syncTarget, SyncTargetService $syncTargetService): RedirectResponse
    {
        $syncTargetService->sync($syncTarget, SyncLogTrigger::Manual);

        return back();
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProjectsRequest;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * The cache key whose value is part of every cached list's key.
     */
    protected const string LIST_CACHE_VERSION_KEY = 'projects.list.version';

    /**
     * Display a page of projects, optionally searched, filtered and sorted.
     *
     * Each page is cached as its JSON body, with no expiry, until a project is
     * created, updated or deleted. The cache stores plain arrays because it refuses to
     * unserialize objects such as models.
     */
    public function index(ListProjectsRequest $request): JsonResponse
    {
        $page = Cache::rememberForever(
            $this->listCacheKey($request),
            function () use ($request): array {
                $projects = Project::query()
                    ->when($request->validated('search'), fn (Builder $query, string $term) => $query->search($term))
                    ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
                    ->when($request->validated('priority'), fn (Builder $query, string $priority) => $query->where('priority', $priority))
                    ->sortBy($request->validated('sort') ?? '-created_at')
                    ->paginate($request->perPage())
                    ->withQueryString();

                return ProjectResource::collection($projects)->response($request)->getData(true);
            },
        );

        return response()->json($page);
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated());
        $this->forgetCachedLists();

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): ProjectResource
    {
        return new ProjectResource($project);
    }

    /**
     * Update the specified project.
     */
    public function update(ProjectRequest $request, Project $project): ProjectResource
    {
        $project->update($request->validated());
        $this->forgetCachedLists();

        return new ProjectResource($project);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): Response
    {
        $project->delete();
        $this->forgetCachedLists();

        return response()->noContent();
    }

    /**
     * Build the cache key for one page of the list from the query string.
     */
    protected function listCacheKey(Request $request): string
    {
        $version = Cache::rememberForever(self::LIST_CACHE_VERSION_KEY, fn (): string => Str::uuid()->toString());
        $query = $request->query();
        ksort($query);

        return 'projects.list.'.$version.'.'.md5(http_build_query($query));
    }

    /**
     * Make every cached page of the list stale by starting a new version.
     */
    protected function forgetCachedLists(): void
    {
        Cache::forget(self::LIST_CACHE_VERSION_KEY);
    }
}

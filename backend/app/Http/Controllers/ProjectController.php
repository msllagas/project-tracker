<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProjectsRequest;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    /**
     * Display a page of projects, optionally searched, filtered and sorted.
     */
    public function index(ListProjectsRequest $request): AnonymousResourceCollection
    {
        $projects = Project::query()
            ->when($request->validated('search'), fn (Builder $query, string $term) => $query->search($term))
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->validated('priority'), fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->sortBy($request->validated('sort') ?? '-created_at')
            ->paginate($request->perPage())
            ->withQueryString();

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated());

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

        return new ProjectResource($project);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }
}

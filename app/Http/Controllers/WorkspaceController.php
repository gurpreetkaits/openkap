<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Managers\WorkspaceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function __construct(
        protected WorkspaceManager $workspaceManager
    ) {}

    /**
     * List user's workspaces
     */
    public function index(): JsonResponse
    {
        $workspaces = $this->workspaceManager->getUserWorkspaces(Auth::user());

        return response()->json([
            'workspaces' => $workspaces,
        ]);
    }

    /**
     * Create a new workspace
     */
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->workspaceManager->createWorkspace(
            Auth::user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Workspace created successfully',
            'workspace' => $this->workspaceManager->getWorkspaceDetails($workspace, Auth::user()),
        ], 201);
    }

    /**
     * Show workspace details
     */
    public function show(string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Check if user has access
        if (! $workspace->hasMember(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'workspace' => $this->workspaceManager->getWorkspaceDetails($workspace, Auth::user()),
        ]);
    }

    /**
     * Update workspace
     */
    public function update(UpdateWorkspaceRequest $request, string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only owner can update workspace
        if (! $workspace->isOwner(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $workspace = $this->workspaceManager->updateWorkspace($workspace, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Workspace updated successfully',
            'workspace' => $this->workspaceManager->getWorkspaceDetails($workspace, Auth::user()),
        ]);
    }

    /**
     * Delete workspace
     */
    public function destroy(string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only owner can delete workspace
        if (! $workspace->isOwner(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->workspaceManager->deleteWorkspace($workspace);

        return response()->json([
            'message' => 'Workspace deleted successfully',
        ]);
    }

    /**
     * Leave workspace
     */
    public function leave(string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        try {
            $this->workspaceManager->leaveWorkspace($workspace, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'You have left the workspace',
        ]);
    }

    /**
     * Get workspace videos
     */
    public function videos(string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Check if user has access
        if (! $workspace->hasMember(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $videos = $workspace->videos()->with('user:id,name,avatar_url')->latest()->get();

        return response()->json([
            'videos' => $videos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'duration' => $video->duration,
                    'thumbnail_url' => $video->getThumbnailUrl(),
                    'share_url' => $video->is_public ? $video->getShareUrl() : null,
                    'is_public' => $video->is_public,
                    'conversion_status' => $video->conversion_status,
                    'created_at' => $video->created_at->toISOString(),
                    'user' => [
                        'id' => $video->user->id,
                        'name' => $video->user->name,
                        'avatar_url' => $video->user->avatar_url,
                    ],
                ];
            }),
        ]);
    }
}

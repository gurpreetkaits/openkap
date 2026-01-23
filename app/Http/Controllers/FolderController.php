<?php

namespace App\Http\Controllers;

use App\Managers\FolderManager;
use App\Repositories\FolderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    public function __construct(
        protected FolderManager $folderManager,
        protected FolderRepository $folders
    ) {}

    /**
     * List user's folders.
     */
    public function index(): JsonResponse
    {
        $folders = $this->folderManager->getUserFolders(Auth::user());

        return response()->json([
            'folders' => $folders->map(fn ($folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'color' => $folder->color,
                'videos_count' => $folder->videos_count,
                'created_at' => $folder->created_at->toISOString(),
            ]),
        ]);
    }

    /**
     * Create a new folder.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        try {
            $folder = $this->folderManager->createFolder(Auth::user(), $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['name' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'message' => 'Folder created successfully',
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'color' => $folder->color,
                'videos_count' => 0,
                'created_at' => $folder->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Update a folder.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $folder = $this->folders->find($id);

        if (! $folder || $folder->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        try {
            $folder = $this->folderManager->updateFolder($folder, $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['name' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'message' => 'Folder updated successfully',
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'color' => $folder->color,
                'videos_count' => $folder->videos()->count(),
                'created_at' => $folder->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Delete a folder.
     */
    public function destroy(int $id): JsonResponse
    {
        $folder = $this->folders->find($id);

        if (! $folder || $folder->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->folderManager->deleteFolder($folder);

        return response()->json([
            'message' => 'Folder deleted successfully',
        ]);
    }

    /**
     * Get videos in a folder.
     */
    public function videos(int $id): JsonResponse
    {
        $folder = $this->folders->find($id);

        if (! $folder || $folder->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $videos = $this->folderManager->getFolderVideos($folder);

        return response()->json([
            'videos' => $videos,
        ]);
    }

    /**
     * Add videos to a folder.
     */
    public function addVideos(Request $request, int $id): JsonResponse
    {
        $folder = $this->folders->find($id);

        if (! $folder || $folder->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'integer|exists:videos,id',
        ]);

        $count = $this->folderManager->addVideosToFolder($folder, $validated['video_ids']);

        return response()->json([
            'message' => "{$count} video(s) added to folder",
            'added_count' => $count,
        ]);
    }

    /**
     * Remove a video from a folder.
     */
    public function removeVideo(int $folderId, int $videoId): JsonResponse
    {
        $folder = $this->folders->find($folderId);

        if (! $folder || $folder->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->folderManager->removeVideoFromFolder($folder, $videoId);

        return response()->json([
            'message' => 'Video removed from folder',
        ]);
    }
}

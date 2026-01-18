<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaylistRequest;
use App\Managers\PlaylistManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
{
    public function __construct(
        protected PlaylistManager $playlistManager
    ) {}

    /**
     * List all playlists for the authenticated user.
     */
    public function index()
    {
        $playlists = $this->playlistManager->getUserPlaylists(Auth::id());

        return response()->json([
            'playlists' => $playlists,
        ]);
    }

    /**
     * Create a new playlist.
     */
    public function store(StorePlaylistRequest $request)
    {
        $playlist = $this->playlistManager->createPlaylist(
            Auth::user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Playlist created successfully',
            'playlist' => $this->playlistManager->getPlaylistDetails($playlist),
        ], 201);
    }

    /**
     * Show a specific playlist with videos.
     */
    public function show($id)
    {
        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'playlist' => $this->playlistManager->getPlaylistDetails($playlist),
        ]);
    }

    /**
     * Update a playlist.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $playlist = $this->playlistManager->updatePlaylist(
            $playlist,
            $request->only(['title', 'description'])
        );

        return response()->json([
            'message' => 'Playlist updated successfully',
            'playlist' => $this->playlistManager->getPlaylistDetails($playlist),
        ]);
    }

    /**
     * Delete a playlist.
     */
    public function destroy($id)
    {
        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->playlistManager->deletePlaylist($playlist, Auth::user());

        return response()->json([
            'message' => 'Playlist deleted successfully',
        ]);
    }

    /**
     * Toggle playlist sharing (public/private).
     */
    public function toggleSharing($id)
    {
        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $playlist = $this->playlistManager->toggleSharing($playlist);

        return response()->json([
            'message' => $playlist->is_public ? 'Playlist is now public' : 'Playlist is now private',
            'is_public' => $playlist->is_public,
            'share_url' => $playlist->is_public ? $playlist->getShareUrl() : null,
        ]);
    }

    /**
     * Update sort order for a playlist.
     */
    public function updateSortBy(Request $request, $id)
    {
        $request->validate([
            'sort_by' => 'required|string|in:manual,date_added',
        ]);

        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $playlist = $this->playlistManager->updateSortBy($playlist, $request->sort_by);

        return response()->json([
            'message' => 'Sort order updated',
            'sort_by' => $playlist->sort_by,
        ]);
    }

    /**
     * Add a video to a playlist.
     */
    public function addVideo(Request $request, $id)
    {
        $request->validate([
            'video_id' => 'required|integer|exists:videos,id',
        ]);

        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $this->playlistManager->addVideoToPlaylist(
                $playlist,
                $request->video_id,
                Auth::user()
            );

            return response()->json([
                'message' => 'Video added to playlist',
                'playlist' => $this->playlistManager->getPlaylistDetails($playlist->fresh()),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove a video from a playlist.
     */
    public function removeVideo($id, $videoId)
    {
        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->playlistManager->removeVideoFromPlaylist($playlist, $videoId);

        return response()->json([
            'message' => 'Video removed from playlist',
            'playlist' => $this->playlistManager->getPlaylistDetails($playlist->fresh()),
        ]);
    }

    /**
     * Reorder videos in a playlist.
     */
    public function reorder(Request $request, $id)
    {
        $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'integer',
        ]);

        $playlist = $this->playlistManager->findPlaylist($id);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        if ($playlist->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->playlistManager->reorderPlaylistVideos($playlist, $request->video_ids);

        return response()->json([
            'message' => 'Videos reordered',
            'playlist' => $this->playlistManager->getPlaylistDetails($playlist->fresh()),
        ]);
    }

    /**
     * View a shared playlist (public access).
     */
    public function showShared($token)
    {
        $playlist = $this->playlistManager->findByShareToken($token);

        if (! $playlist) {
            return response()->json(['message' => 'Playlist not found'], 404);
        }

        $playlistDetails = $this->playlistManager->getSharedPlaylistDetails($playlist);

        if ($playlistDetails === null) {
            return response()->json([
                'message' => 'This playlist is no longer available for sharing',
            ], 403);
        }

        return response()->json([
            'playlist' => $playlistDetails,
        ]);
    }
}

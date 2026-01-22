<?php

namespace App\Managers;

use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use App\Repositories\PlaylistRepository;
use App\Repositories\VideoRepository;

class PlaylistManager
{
    public function __construct(
        protected PlaylistRepository $playlists,
        protected VideoRepository $videos
    ) {}

    /**
     * Create a new playlist.
     */
    public function createPlaylist(User $user, array $data): Playlist
    {
        return $this->playlists->createPlaylist([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_public' => $data['is_public'] ?? false,
            'sort_by' => $data['sort_by'] ?? 'manual',
        ]);
    }

    /**
     * Get all playlists for a user.
     */
    public function getUserPlaylists(int $userId): array
    {
        $playlists = $this->playlists->findByUserId($userId);

        return $playlists->map(function ($playlist) {
            return $this->formatPlaylist($playlist);
        })->toArray();
    }

    /**
     * Get playlist details with videos.
     */
    public function getPlaylistDetails(Playlist $playlist): array
    {
        $playlist = $this->playlists->findWithVideos($playlist->id);

        return $this->formatPlaylistWithVideos($playlist);
    }

    /**
     * Update a playlist.
     */
    public function updatePlaylist(Playlist $playlist, array $data): Playlist
    {
        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (! empty($updateData)) {
            $this->playlists->updatePlaylist($playlist, $updateData);
        }

        return $playlist->fresh();
    }

    /**
     * Delete a playlist.
     */
    public function deletePlaylist(Playlist $playlist, User $user): void
    {
        if ($playlist->user_id !== $user->id) {
            throw new \RuntimeException('Unauthorized to delete this playlist');
        }

        $this->playlists->deletePlaylist($playlist);
    }

    /**
     * Add a video to a playlist.
     */
    public function addVideoToPlaylist(Playlist $playlist, int $videoId, User $user): Playlist
    {
        // Verify the video exists and belongs to the user
        $video = $this->videos->findWithMediaAndCounts($videoId);

        if (! $video) {
            throw new \RuntimeException('Video not found');
        }

        if ($video->user_id !== $user->id) {
            throw new \RuntimeException('Unauthorized to add this video');
        }

        // Check if video is already in playlist
        if ($this->playlists->hasVideo($playlist, $videoId)) {
            throw new \RuntimeException('Video is already in this playlist');
        }

        // Add video at the end
        $position = $this->playlists->getNextPosition($playlist);
        $this->playlists->addVideo($playlist, $videoId, $position);

        return $playlist->fresh();
    }

    /**
     * Add multiple videos to a playlist.
     *
     * @param  array<int>  $videoIds
     * @return array{added: int, skipped: int, errors: array}
     */
    public function addMultipleVideosToPlaylist(Playlist $playlist, array $videoIds, User $user): array
    {
        $added = 0;
        $skipped = 0;
        $errors = [];

        foreach ($videoIds as $videoId) {
            try {
                // Verify the video exists and belongs to the user
                $video = $this->videos->findWithMediaAndCounts($videoId);

                if (! $video) {
                    $errors[] = ['video_id' => $videoId, 'error' => 'Video not found'];

                    continue;
                }

                if ($video->user_id !== $user->id) {
                    $errors[] = ['video_id' => $videoId, 'error' => 'Unauthorized'];

                    continue;
                }

                // Check if video is already in playlist
                if ($this->playlists->hasVideo($playlist, $videoId)) {
                    $skipped++;

                    continue;
                }

                // Add video at the end
                $position = $this->playlists->getNextPosition($playlist);
                $this->playlists->addVideo($playlist, $videoId, $position);
                $added++;
            } catch (\Exception $e) {
                $errors[] = ['video_id' => $videoId, 'error' => $e->getMessage()];
            }
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Remove a video from a playlist.
     */
    public function removeVideoFromPlaylist(Playlist $playlist, int $videoId): Playlist
    {
        $this->playlists->removeVideo($playlist, $videoId);

        return $playlist->fresh();
    }

    /**
     * Reorder videos in a playlist.
     */
    public function reorderPlaylistVideos(Playlist $playlist, array $videoIds): Playlist
    {
        $this->playlists->reorderVideos($playlist, $videoIds);

        return $playlist->fresh();
    }

    /**
     * Toggle playlist sharing (public/private).
     */
    public function toggleSharing(Playlist $playlist): Playlist
    {
        return $this->playlists->togglePublicStatus($playlist);
    }

    /**
     * Update the sort order for a playlist.
     */
    public function updateSortBy(Playlist $playlist, string $sortBy): Playlist
    {
        if (! in_array($sortBy, ['manual', 'date_added'])) {
            throw new \InvalidArgumentException('Invalid sort_by value');
        }

        return $this->playlists->updateSortBy($playlist, $sortBy);
    }

    /**
     * Get shared playlist details (for public access).
     */
    public function getSharedPlaylistDetails(Playlist $playlist): ?array
    {
        if (! $playlist->isShareLinkValid()) {
            return null;
        }

        $playlist = $this->playlists->findWithVideos($playlist->id);

        return $this->formatSharedPlaylist($playlist);
    }

    /**
     * Find a playlist by ID.
     */
    public function findPlaylist(int $id): ?Playlist
    {
        return $this->playlists->findById($id);
    }

    /**
     * Find a playlist by share token.
     */
    public function findByShareToken(string $token): ?Playlist
    {
        return $this->playlists->findByShareToken($token);
    }

    /**
     * Format a playlist for API response.
     */
    protected function formatPlaylist(Playlist $playlist): array
    {
        return [
            'id' => $playlist->id,
            'title' => $playlist->title,
            'description' => $playlist->description,
            'is_public' => $playlist->is_public,
            'share_url' => $playlist->is_public ? $playlist->getShareUrl() : null,
            'sort_by' => $playlist->sort_by,
            'videos_count' => $playlist->videos_count ?? $playlist->video_count,
            'created_at' => $playlist->created_at->toISOString(),
            'updated_at' => $playlist->updated_at->toISOString(),
        ];
    }

    /**
     * Format a playlist with videos for API response.
     */
    protected function formatPlaylistWithVideos(Playlist $playlist): array
    {
        $videos = $playlist->getOrderedVideos();

        return [
            'id' => $playlist->id,
            'title' => $playlist->title,
            'description' => $playlist->description,
            'is_public' => $playlist->is_public,
            'share_url' => $playlist->is_public ? $playlist->getShareUrl() : null,
            'sort_by' => $playlist->sort_by,
            'videos_count' => $videos->count(),
            'videos' => $videos->map(function ($video) {
                return $this->formatVideo($video);
            })->toArray(),
            'created_at' => $playlist->created_at->toISOString(),
            'updated_at' => $playlist->updated_at->toISOString(),
        ];
    }

    /**
     * Format a shared playlist for API response.
     */
    protected function formatSharedPlaylist(Playlist $playlist): array
    {
        $videos = $playlist->getOrderedVideos();

        // Only include public videos in shared playlists
        $publicVideos = $videos->filter(function ($video) {
            return $video->isShareLinkValid();
        });

        return [
            'id' => $playlist->id,
            'title' => $playlist->title,
            'description' => $playlist->description,
            'sort_by' => $playlist->sort_by,
            'videos_count' => $publicVideos->count(),
            'videos' => $publicVideos->map(function ($video) {
                return $this->formatSharedVideo($video);
            })->values()->toArray(),
            'created_at' => $playlist->created_at->toISOString(),
        ];
    }

    /**
     * Format a video for API response (owner view).
     */
    protected function formatVideo(Video $video): array
    {
        $thumbnail = $video->media->where('collection_name', 'thumbnails')->first();
        $thumbnailUrl = $thumbnail ? $thumbnail->getUrl() : null;

        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'duration' => $video->duration,
            'url' => url("/api/share/video/{$video->share_token}/stream"),
            'hls_url' => $video->getHlsUrl(),
            'thumbnail' => $thumbnailUrl,
            'share_url' => $video->getShareUrl(),
            'is_public' => $video->is_public,
            'position' => $video->pivot->position ?? 0,
            'added_at' => $video->pivot->created_at?->toISOString(),
            'created_at' => $video->created_at->toISOString(),
        ];
    }

    /**
     * Format a video for shared playlist response.
     */
    protected function formatSharedVideo(Video $video): array
    {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'duration' => $video->duration,
            'url' => url("/api/share/video/{$video->share_token}/stream"),
            'hls_url' => $video->getHlsUrl(),
            'thumbnail' => $video->getThumbnailUrl(),
            'share_url' => $video->getShareUrl(),
            'position' => $video->pivot->position ?? 0,
            'created_at' => $video->created_at->toISOString(),
        ];
    }
}

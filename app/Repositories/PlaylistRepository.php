<?php

namespace App\Repositories;

use App\Models\Playlist;
use Illuminate\Database\Eloquent\Collection;

class PlaylistRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Playlist);
    }

    /**
     * Find all playlists for a user.
     */
    public function findByUserId(int $userId): Collection
    {
        return Playlist::where('user_id', $userId)
            ->withCount('videos')
            ->latest()
            ->get();
    }

    /**
     * Find a playlist by ID.
     */
    public function findById(int $id): ?Playlist
    {
        return Playlist::find($id);
    }

    /**
     * Find a playlist by share token.
     */
    public function findByShareToken(string $token): ?Playlist
    {
        return Playlist::where('share_token', $token)->first();
    }

    /**
     * Create a new playlist.
     */
    public function createPlaylist(array $data): Playlist
    {
        return Playlist::create($data);
    }

    /**
     * Update a playlist.
     */
    public function updatePlaylist(Playlist $playlist, array $data): bool
    {
        return $playlist->update($data);
    }

    /**
     * Delete a playlist.
     */
    public function deletePlaylist(Playlist $playlist): bool
    {
        return $playlist->delete();
    }

    /**
     * Add a video to a playlist at a specific position.
     */
    public function addVideo(Playlist $playlist, int $videoId, int $position): void
    {
        // Shift existing videos to make room
        $playlist->videos()
            ->wherePivot('position', '>=', $position)
            ->each(function ($video) use ($playlist) {
                $playlist->videos()->updateExistingPivot($video->id, [
                    'position' => $video->pivot->position + 1,
                ]);
            });

        // Attach the video
        $playlist->videos()->attach($videoId, ['position' => $position]);
    }

    /**
     * Remove a video from a playlist.
     */
    public function removeVideo(Playlist $playlist, int $videoId): void
    {
        $video = $playlist->videos()->where('video_id', $videoId)->first();
        if (! $video) {
            return;
        }

        $removedPosition = $video->pivot->position;

        // Detach the video
        $playlist->videos()->detach($videoId);

        // Reorder remaining videos
        $playlist->videos()
            ->wherePivot('position', '>', $removedPosition)
            ->each(function ($video) use ($playlist) {
                $playlist->videos()->updateExistingPivot($video->id, [
                    'position' => $video->pivot->position - 1,
                ]);
            });
    }

    /**
     * Reorder videos in a playlist.
     */
    public function reorderVideos(Playlist $playlist, array $videoIds): void
    {
        foreach ($videoIds as $position => $videoId) {
            $playlist->videos()->updateExistingPivot($videoId, [
                'position' => $position,
            ]);
        }
    }

    /**
     * Toggle the public status of a playlist.
     */
    public function togglePublicStatus(Playlist $playlist): Playlist
    {
        $playlist->is_public = ! $playlist->is_public;
        $playlist->save();

        return $playlist;
    }

    /**
     * Update the sort_by setting for a playlist.
     */
    public function updateSortBy(Playlist $playlist, string $sortBy): Playlist
    {
        $playlist->sort_by = $sortBy;
        $playlist->save();

        return $playlist;
    }

    /**
     * Get playlist with videos eager loaded.
     */
    public function findWithVideos(int $id): ?Playlist
    {
        return Playlist::with(['videos' => function ($query) {
            $query->with('media')->orderByPivot('position', 'asc');
        }])->find($id);
    }

    /**
     * Get the next position for a new video in a playlist.
     */
    public function getNextPosition(Playlist $playlist): int
    {
        $maxPosition = $playlist->videos()->max('playlist_video.position');

        return ($maxPosition ?? -1) + 1;
    }

    /**
     * Check if a video is already in a playlist.
     */
    public function hasVideo(Playlist $playlist, int $videoId): bool
    {
        return $playlist->videos()->where('video_id', $videoId)->exists();
    }

    /**
     * Set or clear the share password on a playlist.
     */
    public function setPassword(Playlist $playlist, ?string $hashedPassword): Playlist
    {
        $playlist->share_password = $hashedPassword;
        $playlist->save();

        return $playlist;
    }
}

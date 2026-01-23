<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FolderRepository
{
    /**
     * Create a new folder.
     */
    public function create(array $data): Folder
    {
        return Folder::create($data);
    }

    /**
     * Find a folder by ID.
     */
    public function find(int $id): ?Folder
    {
        return Folder::find($id);
    }

    /**
     * Find a folder by ID or fail.
     */
    public function findOrFail(int $id): Folder
    {
        return Folder::findOrFail($id);
    }

    /**
     * Get all folders for a user.
     */
    public function getByUser(User $user): Collection
    {
        return Folder::where('user_id', $user->id)
            ->withCount('videos')
            ->orderBy('name')
            ->get();
    }

    /**
     * Update a folder.
     */
    public function update(Folder $folder, array $data): Folder
    {
        $folder->update($data);

        return $folder->fresh();
    }

    /**
     * Delete a folder.
     */
    public function delete(Folder $folder): bool
    {
        return $folder->delete();
    }

    /**
     * Check if folder name is available for a user.
     */
    public function isNameAvailableForUser(string $name, User $user, ?int $excludeId = null): bool
    {
        $query = Folder::where('user_id', $user->id)
            ->where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Add videos to a folder.
     */
    public function addVideos(Folder $folder, array $videoIds): int
    {
        return $folder->user->videos()
            ->whereIn('id', $videoIds)
            ->update(['folder_id' => $folder->id]);
    }

    /**
     * Remove a video from a folder.
     */
    public function removeVideo(Folder $folder, int $videoId): bool
    {
        return $folder->videos()
            ->where('id', $videoId)
            ->update(['folder_id' => null]) > 0;
    }
}

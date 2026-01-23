<?php

namespace App\Managers;

use App\Models\Folder;
use App\Models\User;
use App\Repositories\FolderRepository;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class FolderManager
{
    public function __construct(
        protected FolderRepository $folders
    ) {}

    /**
     * Create a new folder for a user.
     */
    public function createFolder(User $user, array $data): Folder
    {
        // Check for duplicate name
        if (! $this->folders->isNameAvailableForUser($data['name'], $user)) {
            throw new InvalidArgumentException('You already have a folder with this name.');
        }

        return $this->folders->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
        ]);
    }

    /**
     * Get all folders for a user.
     */
    public function getUserFolders(User $user): Collection
    {
        return $this->folders->getByUser($user);
    }

    /**
     * Update a folder.
     */
    public function updateFolder(Folder $folder, array $data): Folder
    {
        // Check for duplicate name if name is being changed
        if (isset($data['name']) && $data['name'] !== $folder->name) {
            if (! $this->folders->isNameAvailableForUser($data['name'], $folder->user, $folder->id)) {
                throw new InvalidArgumentException('You already have a folder with this name.');
            }
        }

        $updates = [];
        if (isset($data['name'])) {
            $updates['name'] = $data['name'];
        }
        if (array_key_exists('color', $data)) {
            $updates['color'] = $data['color'];
        }

        return $this->folders->update($folder, $updates);
    }

    /**
     * Delete a folder.
     * Videos in the folder will have their folder_id set to null.
     */
    public function deleteFolder(Folder $folder): bool
    {
        // Videos will automatically have folder_id set to null due to nullOnDelete
        return $this->folders->delete($folder);
    }

    /**
     * Add videos to a folder.
     */
    public function addVideosToFolder(Folder $folder, array $videoIds): int
    {
        return $this->folders->addVideos($folder, $videoIds);
    }

    /**
     * Remove a video from a folder.
     */
    public function removeVideoFromFolder(Folder $folder, int $videoId): bool
    {
        return $this->folders->removeVideo($folder, $videoId);
    }

    /**
     * Get videos in a folder.
     */
    public function getFolderVideos(Folder $folder): Collection
    {
        return $folder->videos()->orderByDesc('created_at')->get();
    }
}

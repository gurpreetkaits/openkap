<?php

namespace App\Managers;

use App\Models\Screenshot;
use App\Models\User;
use App\Repositories\ScreenshotRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ScreenshotManager
{
    public function __construct(
        protected ScreenshotRepository $screenshots,
    ) {}

    /**
     * Get all screenshots for a user.
     */
    public function getUserScreenshots(int $userId): array
    {
        return $this->screenshots->findByUserId($userId)
            ->map(fn ($screenshot) => $this->formatScreenshot($screenshot))
            ->toArray();
    }

    /**
     * Create a new screenshot.
     */
    public function createScreenshot(User $user, UploadedFile $imageFile, ?string $title = null): Screenshot
    {
        Log::info('ScreenshotManager::createScreenshot started', [
            'user_id' => $user->id,
            'title' => $title ?? 'Screenshot',
            'file_size' => $imageFile->getSize(),
            'mime_type' => $imageFile->getMimeType(),
        ]);

        $screenshot = $this->screenshots->createScreenshot([
            'user_id' => $user->id,
            'title' => $title ?? 'Screenshot '.now()->format('Y-m-d H:i:s'),
            'file_size_bytes' => $imageFile->getSize(),
            'is_public' => true,
        ]);

        $screenshot->addMedia($imageFile)->toMediaCollection('screenshots');

        Log::info('ScreenshotManager::createScreenshot completed', [
            'screenshot_id' => $screenshot->id,
            'user_id' => $user->id,
        ]);

        return $screenshot;
    }

    /**
     * Find a screenshot by ID.
     */
    public function findScreenshot(int $id): ?Screenshot
    {
        return $this->screenshots->findWithMedia($id);
    }

    /**
     * Find a screenshot by ID or fail.
     */
    public function findScreenshotOrFail(int $id): Screenshot
    {
        return $this->screenshots->findWithMediaOrFail($id);
    }

    /**
     * Find a screenshot by share token.
     */
    public function findByShareToken(string $token): ?Screenshot
    {
        return $this->screenshots->findByShareToken($token);
    }

    /**
     * Update a screenshot.
     */
    public function updateScreenshot(Screenshot $screenshot, array $data): Screenshot
    {
        return $this->screenshots->updateScreenshot($screenshot, $data);
    }

    /**
     * Delete a screenshot.
     */
    public function deleteScreenshot(Screenshot $screenshot, User $user): void
    {
        Log::info('ScreenshotManager::deleteScreenshot', [
            'screenshot_id' => $screenshot->id,
            'user_id' => $user->id,
        ]);

        // Clear media library files
        $screenshot->clearMediaCollection('screenshots');
        $screenshot->clearMediaCollection('thumbnails');

        $this->screenshots->deleteScreenshot($screenshot);
    }

    /**
     * Toggle public sharing for a screenshot.
     */
    public function toggleSharing(Screenshot $screenshot): Screenshot
    {
        $screenshot->is_public = ! $screenshot->is_public;

        return $this->screenshots->save($screenshot);
    }

    /**
     * Get screenshot details formatted for API response.
     */
    public function getScreenshotDetails(Screenshot $screenshot): array
    {
        return $this->formatScreenshot($screenshot);
    }

    /**
     * Format a screenshot for API response.
     */
    protected function formatScreenshot(Screenshot $screenshot): array
    {
        return [
            'id' => $screenshot->id,
            'title' => $screenshot->title,
            'image_url' => $screenshot->getImageUrl(),
            'thumbnail_url' => $screenshot->getThumbnailUrl(),
            'share_url' => $screenshot->getFrontendShareUrl(),
            'share_token' => $screenshot->share_token,
            'is_public' => $screenshot->is_public,
            'file_size_bytes' => $screenshot->file_size_bytes,
            'file_size_formatted' => $screenshot->getFileSizeFormatted(),
            'folder_id' => $screenshot->folder_id,
            'workspace_id' => $screenshot->workspace_id,
            'created_at' => $screenshot->created_at->toISOString(),
            'updated_at' => $screenshot->updated_at->toISOString(),
        ];
    }
}

<?php

namespace App\Repositories;

use App\Models\Screenshot;
use Illuminate\Database\Eloquent\Collection;

class ScreenshotRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Screenshot);
    }

    public function findByUserId(int $userId): Collection
    {
        return Screenshot::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->with('media')
            ->get();
    }

    public function createScreenshot(array $data): Screenshot
    {
        return Screenshot::create($data);
    }

    public function findWithMedia(int $id): ?Screenshot
    {
        return Screenshot::with('media')->find($id);
    }

    public function findWithMediaOrFail(int $id): Screenshot
    {
        return Screenshot::with('media')->findOrFail($id);
    }

    public function findByShareToken(string $token): ?Screenshot
    {
        return Screenshot::where('share_token', $token)
            ->with('media')
            ->first();
    }

    public function updateScreenshot(Screenshot $screenshot, array $data): Screenshot
    {
        $screenshot->update($data);

        return $screenshot->fresh();
    }

    public function deleteScreenshot(Screenshot $screenshot): bool
    {
        return (bool) $screenshot->delete();
    }

    public function save(Screenshot $screenshot): Screenshot
    {
        $screenshot->save();

        return $screenshot;
    }
}

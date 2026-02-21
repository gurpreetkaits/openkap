<?php

namespace App\Repositories;

use App\Models\VideoEdit;

class VideoEditRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new VideoEdit);
    }

    public function createEdit(array $data): VideoEdit
    {
        return VideoEdit::create($data);
    }

    public function updateEdit(VideoEdit $edit, array $data): bool
    {
        return $edit->update($data);
    }

    public function findLatestForVideo(int $videoId): ?VideoEdit
    {
        return VideoEdit::where('video_id', $videoId)
            ->latest()
            ->first();
    }

    public function findProcessingForVideo(int $videoId): ?VideoEdit
    {
        return VideoEdit::where('video_id', $videoId)
            ->whereIn('status', ['pending', 'processing'])
            ->first();
    }
}

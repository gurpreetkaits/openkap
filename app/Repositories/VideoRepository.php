<?php

namespace App\Repositories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;

class VideoRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Video);
    }

    public function findByUserId(int $userId): Collection
    {
        return Video::with('media')
            ->where('user_id', $userId)
            ->latest()
            ->withCount(['views', 'comments', 'reactions'])
            ->get();
    }

    public function findWithMediaAndCounts(int $id): ?Video
    {
        return Video::with('media')
            ->withCount(['views', 'comments', 'reactions'])
            ->find($id);
    }

    public function findByShareToken(string $token): ?Video
    {
        return Video::where('share_token', $token)->first();
    }

    public function findByShareTokenOrFail(string $token): Video
    {
        return Video::where('share_token', $token)->firstOrFail();
    }

    public function createVideo(array $data): Video
    {
        return Video::create($data);
    }

    public function updateVideo(Video $video, array $data): bool
    {
        return $video->update($data);
    }

    public function deleteVideo(Video $video): bool
    {
        return $video->delete();
    }

    public function togglePublicStatus(Video $video): Video
    {
        $video->is_public = ! $video->is_public;
        $video->save();

        return $video;
    }

    public function getReactionCounts(Video $video): array
    {
        return $video->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    public function getCommentsWithUser(Video $video): Collection
    {
        return $video->comments()
            ->with('user:id,name,avatar_url')
            ->get();
    }

    public function findFavouritesByUserId(int $userId): Collection
    {
        return Video::with('media')
            ->where('user_id', $userId)
            ->where('is_favourite', true)
            ->latest()
            ->withCount(['views', 'comments', 'reactions'])
            ->get();
    }

    public function toggleFavourite(Video $video): Video
    {
        $video->is_favourite = ! $video->is_favourite;
        $video->save();

        return $video;
    }

    public function setFavourite(Video $video, bool $isFavourite): Video
    {
        $video->is_favourite = $isFavourite;
        $video->save();

        return $video;
    }

    public function updateTranscriptionStatus(Video $video, string $status, ?int $progress = null, ?string $error = null): bool
    {
        $data = ['transcription_status' => $status];

        if ($progress !== null) {
            $data['transcription_progress'] = $progress;
        }

        if ($error !== null) {
            $data['transcription_error'] = $error;
        }

        if ($status === 'completed') {
            $data['transcription_generated_at'] = now();
        }

        return $video->update($data);
    }

    public function updateSummaryStatus(Video $video, string $status, ?string $error = null): bool
    {
        $data = ['summary_status' => $status];

        if ($error !== null) {
            $data['summary_error'] = $error;
        }

        if ($status === 'completed') {
            $data['summary_generated_at'] = now();
        }

        return $video->update($data);
    }

    public function saveTranscription(Video $video, string $transcription): bool
    {
        return $video->update([
            'transcription' => $transcription,
            'transcription_status' => 'completed',
            'transcription_progress' => 100,
            'transcription_error' => null,
            'transcription_generated_at' => now(),
        ]);
    }

    public function saveSummary(Video $video, string $summary): bool
    {
        return $video->update([
            'summary' => $summary,
            'summary_status' => 'completed',
            'summary_error' => null,
            'summary_generated_at' => now(),
        ]);
    }

    public function findPendingTranscription(): Collection
    {
        return Video::where('transcription_status', 'pending')
            ->where('conversion_status', 'completed')
            ->latest()
            ->get();
    }
}

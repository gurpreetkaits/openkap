<?php

namespace App\Repositories;

use App\Models\Video;
use App\Models\VideoZoomSetting;

class VideoZoomSettingRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new VideoZoomSetting);
    }

    public function findByVideoId(int $videoId): ?VideoZoomSetting
    {
        return VideoZoomSetting::where('video_id', $videoId)->first();
    }

    public function findByVideo(Video $video): ?VideoZoomSetting
    {
        return $video->zoomSettings;
    }

    public function createForVideo(Video $video, array $data): VideoZoomSetting
    {
        return VideoZoomSetting::create([
            'video_id' => $video->id,
            'enabled' => $data['enabled'] ?? true,
            'zoom_level' => $data['zoom_level'] ?? 2.0,
            'duration_ms' => $data['duration_ms'] ?? 500,
            'events' => $data['events'] ?? null,
            'recording_resolution' => $data['recording_resolution'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'progress' => $data['progress'] ?? 0,
            'error' => $data['error'] ?? null,
        ]);
    }

    public function updateSettings(VideoZoomSetting $settings, array $data): bool
    {
        return $settings->update($data);
    }

    public function updateStatus(VideoZoomSetting $settings, string $status, ?int $progress = null, ?string $error = null): bool
    {
        $data = ['status' => $status];

        if ($progress !== null) {
            $data['progress'] = $progress;
        }

        if ($error !== null) {
            $data['error'] = $error;
        }

        return $settings->update($data);
    }

    public function updateEvents(VideoZoomSetting $settings, array $events, ?array $resolution = null): bool
    {
        $data = ['events' => $events, 'status' => 'pending'];

        if ($resolution !== null) {
            $data['recording_resolution'] = $resolution;
        }

        return $settings->update($data);
    }

    public function deleteForVideo(Video $video): bool
    {
        return VideoZoomSetting::where('video_id', $video->id)->delete();
    }

    public function findPendingProcessing(): \Illuminate\Database\Eloquent\Collection
    {
        return VideoZoomSetting::where('status', 'pending')
            ->where('enabled', true)
            ->whereNotNull('events')
            ->with('video')
            ->get();
    }
}

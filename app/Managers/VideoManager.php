<?php

namespace App\Managers;

use App\Data\VideoEditData;
use App\Jobs\ApplyVideoEditsJob;
use App\Jobs\ConvertVideoToMp4ForDownloadJob;
use App\Jobs\GenerateSummaryJob;
use App\Jobs\GenerateTranscriptionJob;
use App\Jobs\RemuxWebmJob;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Video;
use App\Repositories\UserSettingRepository;
use App\Repositories\VideoEditRepository;
use App\Repositories\VideoRepository;
use App\Repositories\VideoZoomSettingRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\BunnyStreamService;
use App\Services\CaptionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class VideoManager
{
    public function __construct(
        protected VideoRepository $videos,
        protected VideoZoomSettingRepository $zoomSettings,
        protected BunnyStreamService $bunnyService,
        protected UserSettingRepository $userSettings,
        protected WorkspaceRepository $workspaces,
        protected VideoEditRepository $videoEdits,
        protected CaptionService $captionService
    ) {}

    public function getUserVideos(int $userId): array
    {
        $videos = $this->videos->findByUserId($userId);

        return $videos->map(function ($video) {
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
                'is_favourite' => $video->is_favourite ?? false,
                'views_count' => $video->views_count ?? 0,
                'comments_count' => $video->comments_count ?? 0,
                'reactions_count' => $video->reactions_count ?? 0,
                'conversion_status' => $video->conversion_status,
                'conversion_progress' => $video->conversion_progress,
                'is_converting' => in_array($video->conversion_status, ['pending', 'processing']),
                'hls_status' => $video->hls_status ?? 'pending',
                'hls_progress' => $video->hls_progress ?? 0,
                'is_hls_ready' => $video->isReadyForPlayback(),
                'transcription_status' => $video->transcription_status ?? 'pending',
                'transcription_progress' => $video->transcription_progress ?? 0,
                'is_transcription_ready' => $video->isTranscriptionReady(),
                'summary_status' => $video->summary_status ?? 'pending',
                'is_summary_ready' => $video->isSummaryReady(),
                'created_at' => $video->created_at->toISOString(),
                'updated_at' => $video->updated_at->toISOString(),
                // Bunny Stream fields
                'storage_type' => $video->storage_type,
                'bunny_status' => $video->bunny_status,
                'bunny_video_id' => $video->bunny_video_id,
            ];
        })->toArray();
    }

    public function canUserRecordVideo(User $user): bool
    {
        return $user->canRecordVideo();
    }

    public function createVideo(User $user, array $data, UploadedFile $videoFile): Video
    {
        Log::info('VideoManager::createVideo started', [
            'user_id' => $user->id,
            'title' => $data['title'] ?? 'no title',
        ]);

        $workspace = $user->ownedWorkspaces()->first();
        $video = $this->videos->createVideo([
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration' => $data['duration'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);

        $video->addMedia($videoFile)->toMediaCollection('videos');

        $media = $video->getFirstMedia('videos');
        $originalExtension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        if ($data['duration'] ?? null) {
            $this->videos->updateVideo($video, ['duration' => $data['duration']]);
        }

        $video->generateThumbnailFromMidpoint();

        // Remux WebM to fix missing Duration and Cues (seek index)
        RemuxWebmJob::dispatch($video);

        // TODO: MP4/HLS conversion skipped — serve remuxed WebM directly.
        // Revisit with Cloudflare Stream or Bunny CDN once we reach 10-15 users.

        // Auto-transcribe (has built-in rescheduling that waits for conversion)
        Log::info('Dispatching GenerateTranscriptionJob', ['video_id' => $video->id]);
        GenerateTranscriptionJob::dispatch($video, generateSummary: true, generateTitle: true);

        $user->increment('videos_count');

        return $video;
    }

    public function getVideoDetails(Video $video): array
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
            'is_public' => $video->is_public,
            'is_favourite' => $video->is_favourite ?? false,
            'views_count' => $video->views_count ?? 0,
            'comments_count' => $video->comments_count ?? 0,
            'reactions_count' => $video->reactions_count ?? 0,
            'conversion_status' => $video->conversion_status,
            'conversion_progress' => $video->conversion_progress,
            'is_converting' => $video->isConverting(),
            'hls_status' => $video->hls_status ?? 'pending',
            'hls_progress' => $video->hls_progress ?? 0,
            'is_hls_ready' => $video->isReadyForPlayback(),
            'transcription_status' => $video->transcription_status ?? 'pending',
            'transcription_progress' => $video->transcription_progress ?? 0,
            'is_transcription_ready' => $video->isTranscriptionReady(),
            'summary_status' => $video->summary_status ?? 'pending',
            'is_summary_ready' => $video->isSummaryReady(),
            'created_at' => $video->created_at->toISOString(),
            'updated_at' => $video->updated_at->toISOString(),
            // Bunny Stream fields
            'storage_type' => $video->storage_type,
            'bunny_status' => $video->bunny_status,
            'bunny_video_id' => $video->bunny_video_id,
        ];
    }

    public function getConversionStatus(Video $video): array
    {
        return [
            'conversion_status' => $video->conversion_status,
            'conversion_progress' => $video->conversion_progress,
            'conversion_error' => $video->conversion_error,
            'is_converting' => $video->isConverting(),
            'is_complete' => $video->isConversionComplete(),
            'is_failed' => $video->isConversionFailed(),
            'message' => $video->getConversionStatusMessage(),
            'converted_at' => $video->converted_at?->toISOString(),
            'hls_status' => $video->hls_status,
            'hls_progress' => $video->hls_progress,
            'hls_error' => $video->hls_error,
            'is_hls_ready' => $video->isHlsReady(),
            'hls_url' => $video->getHlsUrl(),
        ];
    }

    public function updateVideo(Video $video, array $data): Video
    {
        $this->videos->updateVideo($video, $data);

        return $video->fresh();
    }

    public function deleteVideo(Video $video, User $user): void
    {
        $workspace = $video->workspace;
        $this->videos->deleteVideo($video);
        $user->decrement('videos_count');

        if ($workspace) {
            $this->workspaces->recalculateStorage($workspace);
        }
    }

    /**
     * Bulk delete videos for a user
     *
     * @param  array<int>  $videoIds
     * @return array{deleted: int, failed: int, errors: array}
     */
    public function bulkDeleteVideos(array $videoIds, User $user): array
    {
        $videos = $this->videos->findByIdsForUser($videoIds, $user->id);

        $deleted = 0;
        $failed = 0;
        $errors = [];
        $affectedWorkspaceIds = [];

        foreach ($videos as $video) {
            try {
                if ($video->workspace_id) {
                    $affectedWorkspaceIds[$video->workspace_id] = true;
                }

                // Delete from Bunny CDN if it's a Bunny video
                if ($video->storage_type === 'bunny' && $video->bunny_video_id) {
                    try {
                        $this->bunnyService->deleteVideo($video->bunny_video_id);
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete video from Bunny CDN', [
                            'video_id' => $video->id,
                            'bunny_video_id' => $video->bunny_video_id,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with local deletion even if Bunny deletion fails
                    }
                }

                // Delete from database (Spatie Media Library handles local file cleanup)
                $this->videos->deleteVideo($video);
                $user->decrement('videos_count');
                $deleted++;
            } catch (\Exception $e) {
                Log::error('Failed to delete video', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
                $errors[] = [
                    'video_id' => $video->id,
                    'title' => $video->title,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Recalculate storage for all affected workspaces
        foreach (array_keys($affectedWorkspaceIds) as $workspaceId) {
            $workspace = $this->workspaces->find($workspaceId);
            if ($workspace) {
                $this->workspaces->recalculateStorage($workspace);
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function toggleSharing(Video $video): Video
    {
        return $this->videos->togglePublicStatus($video);
    }

    public function regenerateShareToken(Video $video): Video
    {
        $video->regenerateShareToken();

        return $video;
    }

    public function getSharedVideoDetails(Video $video): ?array
    {
        if (! $video->isShareLinkValid()) {
            return null;
        }

        $reactionCounts = $this->videos->getReactionCounts($video);

        $reactions = [];
        foreach (Reaction::TYPES as $type => $emoji) {
            $reactions[$type] = [
                'count' => $reactionCounts[$type] ?? 0,
                'emoji' => $emoji,
            ];
        }

        $comments = $this->videos->getCommentsWithUser($video)->map(function ($comment) {
            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'author_name' => $comment->author_display_name,
                'author_avatar' => $comment->user?->avatar_url,
                'timestamp_seconds' => $comment->timestamp_seconds,
                'created_at' => $comment->created_at->toISOString(),
            ];
        });

        // Get video owner's branding
        $branding = $this->userSettings->getBrandingForUser($video->user);

        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'duration' => $video->duration,
            'url' => url("/api/share/video/{$video->share_token}/stream"),
            'hls_url' => $video->getHlsUrl(),
            'thumbnail' => $video->getThumbnailUrl(),
            'created_at' => $video->created_at->toISOString(),
            'views_count' => $video->views_count ?? 0,
            'reactions' => $reactions,
            'comments' => $comments,
            'is_hls_ready' => $video->isReadyForPlayback(),
            'transcription' => $video->isTranscriptionReady() ? $video->transcription : null,
            'transcription_segments' => $video->isTranscriptionReady() ? $video->transcription_segments : null,
            'summary' => $video->isSummaryReady() ? $video->summary : null,
            // Bunny Stream fields
            'storage_type' => $video->storage_type,
            'bunny_status' => $video->bunny_status,
            'bunny_video_id' => $video->bunny_video_id,
            // Creator identity
            'user_name' => $video->user?->name,
            'user_avatar' => $video->user?->avatar_url,
            // Owner branding
            'branding' => $branding,
        ];
    }

    public function trimVideo(Video $video, float $startTime, float $endTime): array
    {
        $media = $video->getFirstMedia('videos');
        if (! $media) {
            throw new \Exception('Video file not found');
        }

        $newDuration = $endTime - $startTime;

        if ($endTime > $video->duration + 1) {
            throw new \InvalidArgumentException('End time exceeds video duration');
        }

        $originalPath = $media->getPath();
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $tempPath = storage_path('app/temp_trimmed_'.uniqid().'.'.$extension);

        if (! is_dir(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        Log::info('Starting video trim', [
            'video_id' => $video->id,
            'original_path' => $originalPath,
            'temp_path' => $tempPath,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $newDuration,
        ]);

        $isWebM = strtolower($extension) === 'webm';
        $this->executeTrimCommand($originalPath, $tempPath, $startTime, $newDuration, $isWebM);

        $trimmedSize = filesize($tempPath);
        if ($trimmedSize < 1000) {
            @unlink($tempPath);
            throw new \Exception('Trimmed video file is invalid');
        }

        $video->clearMediaCollection('videos');
        $video->addMedia($tempPath)->toMediaCollection('videos');
        $this->videos->updateVideo($video, ['duration' => (int) round($newDuration)]);
        $video->refresh();

        $video->generateThumbnailFromMidpoint();

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return [
            'id' => $video->id,
            'title' => $video->title,
            'duration' => $video->duration,
            'url' => url("/api/share/video/{$video->share_token}/stream").'?v='.time(),
            'thumbnail' => $video->getThumbnailUrl().'?v='.time(),
        ];
    }

    protected function executeTrimCommand(string $originalPath, string $tempPath, float $startTime, float $duration, bool $isWebM): void
    {
        if ($isWebM) {
            $ffmpegCommand = sprintf(
                'ffmpeg -y -i %s -ss %s -t %s -c:v libvpx-vp9 -crf 30 -b:v 0 -c:a libopus -b:a 128k %s 2>&1',
                escapeshellarg($originalPath),
                escapeshellarg(number_format($startTime, 3, '.', '')),
                escapeshellarg(number_format($duration, 3, '.', '')),
                escapeshellarg($tempPath)
            );
        } else {
            $ffmpegCommand = sprintf(
                'ffmpeg -y -ss %s -i %s -t %s -c copy -avoid_negative_ts make_zero %s 2>&1',
                escapeshellarg(number_format($startTime, 3, '.', '')),
                escapeshellarg($originalPath),
                escapeshellarg(number_format($duration, 3, '.', '')),
                escapeshellarg($tempPath)
            );
        }

        Log::info('FFmpeg command', ['command' => $ffmpegCommand]);

        exec($ffmpegCommand, $output, $returnCode);

        Log::info('FFmpeg result', [
            'return_code' => $returnCode,
            'output' => implode("\n", $output),
            'file_exists' => file_exists($tempPath),
        ]);

        if ($returnCode !== 0 || ! file_exists($tempPath)) {
            if (! $isWebM) {
                $output = [];
                $ffmpegCommand = sprintf(
                    'ffmpeg -y -i %s -ss %s -t %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k %s 2>&1',
                    escapeshellarg($originalPath),
                    escapeshellarg(number_format($startTime, 3, '.', '')),
                    escapeshellarg(number_format($duration, 3, '.', '')),
                    escapeshellarg($tempPath)
                );

                Log::info('FFmpeg fallback command', ['command' => $ffmpegCommand]);

                exec($ffmpegCommand, $output, $returnCode);

                Log::info('FFmpeg fallback result', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output),
                    'file_exists' => file_exists($tempPath),
                ]);
            }

            if ($returnCode !== 0 || ! file_exists($tempPath)) {
                Log::error('FFmpeg trim failed', [
                    'command' => $ffmpegCommand,
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode,
                ]);
                throw new \Exception('Failed to trim video: '.implode(' ', array_slice($output, -3)));
            }
        }
    }

    /**
     * Apply blur effect to a region of the video.
     */
    public function applyBlur(Video $video, array $blurRegion, ?float $startTime = null, ?float $endTime = null): void
    {
        // Update video status
        $this->videos->updateVideo($video, [
            'blur_status' => 'pending',
            'blur_progress' => 0,
            'blur_error' => null,
            'blur_region' => $blurRegion,
            'blur_start_time' => $startTime,
            'blur_end_time' => $endTime,
        ]);

        // Dispatch the blur job
        \App\Jobs\ApplyBlurJob::dispatch($video)->delay(now()->addSeconds(2));

        Log::info('Blur job dispatched', [
            'video_id' => $video->id,
            'blur_region' => $blurRegion,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    public function streamVideo(Video $video, ?string $rangeHeader = null): array
    {
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            throw new \Exception('Video file not found');
        }

        $filePath = $media->getPath();

        if (! file_exists($filePath)) {
            throw new \Exception('Video file not found on disk');
        }

        $fileSize = filesize($filePath);
        $mimeType = $media->mime_type;
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $isWebM = $extension === 'webm' || $mimeType === 'video/webm';
        $isSmallFile = $fileSize < 50 * 1024 * 1024;

        return [
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'is_webm' => $isWebM,
            'is_small_file' => $isSmallFile,
            'range_header' => $rangeHeader,
        ];
    }

    public function canAccessSharedVideo(Video $video, ?int $userId = null): bool
    {
        $isOwner = $userId !== null && $userId === $video->user_id;

        return $isOwner || $video->isShareLinkValid();
    }

    public function findVideo(int $id): ?Video
    {
        return $this->videos->findWithMediaAndCounts($id);
    }

    public function findVideoOrFail(int $id): Video
    {
        return $this->videos->findOrFail($id);
    }

    public function findByShareToken(string $token): ?Video
    {
        return $this->videos->findByShareToken($token);
    }

    public function findByShareTokenOrFail(string $token): Video
    {
        return $this->videos->findByShareTokenOrFail($token);
    }

    public function getFavouriteVideos(int $userId): array
    {
        $videos = $this->videos->findFavouritesByUserId($userId);

        return $videos->map(function ($video) {
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
                'is_favourite' => $video->is_favourite ?? false,
                'views_count' => $video->views_count ?? 0,
                'comments_count' => $video->comments_count ?? 0,
                'reactions_count' => $video->reactions_count ?? 0,
                'conversion_status' => $video->conversion_status,
                'conversion_progress' => $video->conversion_progress,
                'is_converting' => in_array($video->conversion_status, ['pending', 'processing']),
                'hls_status' => $video->hls_status ?? 'pending',
                'hls_progress' => $video->hls_progress ?? 0,
                'is_hls_ready' => $video->isReadyForPlayback(),
                'transcription_status' => $video->transcription_status ?? 'pending',
                'transcription_progress' => $video->transcription_progress ?? 0,
                'is_transcription_ready' => $video->isTranscriptionReady(),
                'summary_status' => $video->summary_status ?? 'pending',
                'is_summary_ready' => $video->isSummaryReady(),
                'created_at' => $video->created_at->toISOString(),
                'updated_at' => $video->updated_at->toISOString(),
                // Bunny Stream fields
                'storage_type' => $video->storage_type,
                'bunny_status' => $video->bunny_status,
                'bunny_video_id' => $video->bunny_video_id,
            ];
        })->toArray();
    }

    public function toggleFavourite(Video $video): Video
    {
        return $this->videos->toggleFavourite($video);
    }

    /**
     * Bulk add videos to favourites
     *
     * @param  array<int>  $videoIds
     * @return array{added: int, failed: int}
     */
    public function bulkAddToFavourites(array $videoIds, User $user): array
    {
        $videos = $this->videos->findByIdsForUser($videoIds, $user->id);
        $added = $this->videos->setFavouriteForMany($videos, true);

        return [
            'added' => $added,
            'failed' => count($videoIds) - $added,
        ];
    }

    /**
     * Bulk remove videos from favourites
     *
     * @param  array<int>  $videoIds
     * @return array{removed: int, failed: int}
     */
    public function bulkRemoveFromFavourites(array $videoIds, User $user): array
    {
        $videos = $this->videos->findByIdsForUser($videoIds, $user->id);
        $removed = $this->videos->setFavouriteForMany($videos, false);

        return [
            'removed' => $removed,
            'failed' => count($videoIds) - $removed,
        ];
    }

    public function requestTranscription(Video $video, bool $generateSummary = true, bool $generateTitle = true): array
    {
        // Check if video conversion is complete
        if (! $video->isConversionComplete()) {
            return [
                'success' => false,
                'message' => 'Video is still being processed. Please wait for conversion to complete.',
                'status' => $video->conversion_status,
            ];
        }

        // Check if already processing
        if ($video->transcription_status === 'processing') {
            return [
                'success' => false,
                'message' => 'Transcription is already in progress.',
                'status' => $video->transcription_status,
                'progress' => $video->transcription_progress,
            ];
        }

        // Reset status if previously failed or already completed (allow re-generation)
        $this->videos->updateVideo($video, [
            'transcription_status' => 'pending',
            'transcription_progress' => 0,
            'transcription_error' => null,
            'transcription_segments' => null,
            'summary_status' => 'pending',
            'summary_error' => null,
        ]);

        // Dispatch the job
        Log::info('Dispatching transcription job', [
            'video_id' => $video->id,
            'generate_summary' => $generateSummary,
            'generate_title' => $generateTitle,
        ]);

        GenerateTranscriptionJob::dispatch($video, $generateSummary, $generateTitle);

        return [
            'success' => true,
            'message' => 'Transcription started. This may take a few minutes.',
            'status' => 'pending',
        ];
    }

    public function requestSummary(Video $video): array
    {
        // Check if transcription is available
        if (! $video->isTranscriptionReady()) {
            return [
                'success' => false,
                'message' => 'Transcription is not available. Please generate transcription first.',
                'transcription_status' => $video->transcription_status,
            ];
        }

        // Check if already processing
        if ($video->summary_status === 'processing') {
            return [
                'success' => false,
                'message' => 'Summary is already being generated.',
                'status' => $video->summary_status,
            ];
        }

        // Reset status if previously failed or completed
        $this->videos->updateVideo($video, [
            'summary_status' => 'pending',
            'summary_error' => null,
        ]);

        // Dispatch the job
        Log::info('Dispatching summary job', ['video_id' => $video->id]);

        GenerateSummaryJob::dispatch($video);

        return [
            'success' => true,
            'message' => 'Summary generation started.',
            'status' => 'pending',
        ];
    }

    public function getTranscriptionStatus(Video $video): array
    {
        return [
            'has_audio' => $video->has_audio,
            'transcription_status' => $video->transcription_status,
            'transcription_progress' => $video->transcription_progress,
            'transcription_error' => $video->transcription_error,
            'is_transcribing' => $video->isTranscribing(),
            'is_transcription_ready' => $video->isTranscriptionReady(),
            'is_transcription_failed' => $video->isTranscriptionFailed(),
            'transcription_message' => $video->getTranscriptionStatusMessage(),
            'transcription_generated_at' => $video->transcription_generated_at?->toISOString(),
            'summary_status' => $video->summary_status,
            'summary_error' => $video->summary_error,
            'is_summarizing' => $video->isSummarizing(),
            'is_summary_ready' => $video->isSummaryReady(),
            'is_summary_failed' => $video->isSummaryFailed(),
            'summary_message' => $video->getSummaryStatusMessage(),
            'summary_generated_at' => $video->summary_generated_at?->toISOString(),
            'bug_detection_status' => $video->bug_detection_status,
            'bug_detection_error' => $video->bug_detection_error,
            'is_bug_detecting' => $video->isBugDetecting(),
            'is_bug_detection_ready' => $video->isBugDetectionReady(),
            'is_bug_detection_failed' => $video->isBugDetectionFailed(),
            'bug_detection_message' => $video->getBugDetectionStatusMessage(),
            'bug_detection_generated_at' => $video->bug_detection_generated_at?->toISOString(),
        ];
    }

    public function getTranscription(Video $video): ?array
    {
        if (! $video->isTranscriptionReady()) {
            return null;
        }

        return [
            'transcription' => $video->transcription,
            'segments' => $video->transcription_segments,
            'generated_at' => $video->transcription_generated_at?->toISOString(),
        ];
    }

    public function getSummary(Video $video): ?array
    {
        if (! $video->isSummaryReady()) {
            return null;
        }

        return [
            'summary' => $video->summary,
            'generated_at' => $video->summary_generated_at?->toISOString(),
        ];
    }

    public function getDetectedBugs(Video $video): ?array
    {
        if (! $video->isBugDetectionReady()) {
            return null;
        }

        return [
            'bugs' => $video->detected_bugs ?? [],
            'generated_at' => $video->bug_detection_generated_at?->toISOString(),
        ];
    }

    public function getTranscriptionAndSummary(Video $video): array
    {
        return [
            'transcription' => $this->getTranscription($video),
            'summary' => $this->getSummary($video),
            'bugs' => $this->getDetectedBugs($video),
            'status' => $this->getTranscriptionStatus($video),
        ];
    }

    // ============================================
    // ZOOM EFFECT METHODS
    // ============================================

    public function createZoomSettings(Video $video, array $data): Video
    {
        $this->zoomSettings->createForVideo($video, [
            'enabled' => $data['zoom_enabled'] ?? true,
            'zoom_level' => max(1.2, min(4.0, (float) ($data['zoom_level'] ?? 2.0))),
            'duration_ms' => max(100, min(2000, (int) ($data['zoom_duration_ms'] ?? 500))),
            'events' => $data['zoom_events']['events'] ?? null,
            'recording_resolution' => $data['zoom_events']['recording_resolution'] ?? null,
            'status' => 'pending',
        ]);

        return $video->fresh('zoomSettings');
    }

    public function updateZoomSettings(Video $video, array $data): Video
    {
        $settings = $video->zoomSettings;

        if (! $settings) {
            return $this->createZoomSettings($video, $data);
        }

        $updateData = [];

        if (isset($data['zoom_enabled'])) {
            $updateData['enabled'] = (bool) $data['zoom_enabled'];
        }

        if (isset($data['zoom_level'])) {
            $updateData['zoom_level'] = max(1.2, min(4.0, (float) $data['zoom_level']));
        }

        if (isset($data['zoom_duration_ms'])) {
            $updateData['duration_ms'] = max(100, min(2000, (int) $data['zoom_duration_ms']));
        }

        $this->zoomSettings->updateSettings($settings, $updateData);

        return $video->fresh('zoomSettings');
    }

    public function updateZoomEvents(Video $video, array $zoomEvents): Video
    {
        $settings = $video->zoomSettings;

        if (! $settings) {
            return $this->createZoomSettings($video, [
                'zoom_events' => $zoomEvents,
            ]);
        }

        $this->zoomSettings->updateEvents(
            $settings,
            $zoomEvents['events'] ?? [],
            $zoomEvents['recording_resolution'] ?? null
        );

        return $video->fresh('zoomSettings');
    }

    public function getZoomStatus(Video $video): array
    {
        $video->load('zoomSettings');

        return [
            'zoom_enabled' => $video->isZoomEnabled(),
            'zoom_level' => $video->getZoomLevel(),
            'zoom_duration_ms' => $video->getZoomDurationMs(),
            'zoom_status' => $video->getZoomStatus(),
            'zoom_progress' => $video->getZoomProgress(),
            'zoom_error' => $video->zoomSettings?->error,
            'zoom_event_count' => $video->getZoomEventCount(),
            'is_zoom_processing' => $video->isZoomProcessing(),
            'is_zoom_ready' => $video->isZoomReady(),
            'is_zoom_failed' => $video->isZoomFailed(),
            'zoom_message' => $video->getZoomStatusMessage(),
        ];
    }

    public function getZoomEvents(Video $video): ?array
    {
        $settings = $video->zoomSettings;

        if (! $settings || ! $settings->events) {
            return null;
        }

        return [
            'recording_resolution' => $settings->recording_resolution,
            'events' => $settings->events,
        ];
    }

    // ============================================
    // TRANSCRIPTION METHODS (EDITOR)
    // ============================================

    public function updateTranscription(Video $video, string $text, ?array $segments = null): Video
    {
        $data = [
            'transcription' => $text,
        ];

        if ($segments !== null) {
            // Preserve segment structure with updated text
            $data['transcription_segments'] = array_map(fn ($s) => [
                'start' => round((float) ($s['start'] ?? 0), 2),
                'end' => round((float) ($s['end'] ?? 0), 2),
                'text' => trim($s['text'] ?? ''),
                'words' => $s['words'] ?? null,
            ], $segments);
        } else {
            // No segments provided, clear stale segments
            $data['transcription_segments'] = null;
        }

        $this->videos->updateVideo($video, $data);

        return $video->fresh();
    }

    // ============================================
    // VIDEO EDITOR METHODS
    // ============================================

    public function applyEdits(Video $video, User $user, VideoEditData $editData, array $overlayFiles = []): array
    {
        $videoEdit = $this->videoEdits->createEdit([
            'video_id' => $video->id,
            'user_id' => $user->id,
            'blur_regions' => array_map(fn ($r) => $r->toArray(), $editData->blur_regions),
            'overlay_configs' => array_map(fn ($o) => $o->toArray(), $editData->overlay_configs),
            'text_overlays' => array_map(fn ($t) => $t->toArray(), $editData->text_overlays),
            'trim_start' => $editData->trim_start,
            'trim_end' => $editData->trim_end,
            'merge_video_id' => $editData->merge_video_id,
            'status' => 'pending',
            'progress' => 0,
        ]);

        foreach ($overlayFiles as $file) {
            $videoEdit->addMedia($file)->toMediaCollection('overlays');
        }

        $maxSyncDuration = 180; // 3 minutes

        if (($video->duration ?? 0) <= $maxSyncDuration) {
            // Sync processing for short videos
            set_time_limit(300);
            $job = new ApplyVideoEditsJob($videoEdit);
            $job->handle();

            $videoEdit->refresh();

            return [
                'message' => 'Video edits applied successfully.',
                'mode' => 'sync',
                'output_video_id' => $videoEdit->output_video_id,
            ];
        }

        // Async processing for long videos
        ApplyVideoEditsJob::dispatch($videoEdit)->delay(now()->addSeconds(2));

        Log::info('Video edit job dispatched (async)', [
            'video_id' => $video->id,
            'edit_id' => $videoEdit->id,
            'blur_count' => count($editData->blur_regions),
            'overlay_count' => count($editData->overlay_configs),
            'text_count' => count($editData->text_overlays),
        ]);

        return [
            'message' => 'Video edits are being applied. You will be notified when ready.',
            'mode' => 'async',
        ];
    }

    public function getEditStatus(Video $video): array
    {
        $edit = $this->videoEdits->findLatestForVideo($video->id);

        if (! $edit) {
            return [
                'status' => 'none',
                'progress' => 0,
                'error' => null,
                'output_video_id' => null,
            ];
        }

        return [
            'status' => $edit->status,
            'progress' => $edit->progress,
            'error' => $edit->error,
            'output_video_id' => $edit->output_video_id,
        ];
    }

    // ============================================
    // MP4 DOWNLOAD METHODS
    // ============================================

    public function requestMp4Download(Video $video): array
    {
        $media = $video->getFirstMedia('videos');
        if (! $media) {
            throw new \Exception('Video file not found');
        }

        $maxSyncDuration = 300; // 5 minutes

        if (($video->duration ?? 0) <= $maxSyncDuration) {
            $outputPath = $this->convertToMp4Sync($video, $media);

            return [
                'mode' => 'sync',
                'file_path' => $outputPath,
                'file_name' => ($video->title ?? 'video').'.mp4',
            ];
        }

        ConvertVideoToMp4ForDownloadJob::dispatch($video);

        Log::info('Dispatched async MP4 download conversion', ['video_id' => $video->id]);

        return [
            'mode' => 'async',
        ];
    }

    public function convertToMp4Sync(Video $video, $media): string
    {
        $inputPath = $media->getPath();

        if (! file_exists($inputPath)) {
            throw new \Exception('Video file not found on disk');
        }

        $outputDir = storage_path('app/mp4-downloads');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir.'/video_'.$video->id.'_'.time().'.mp4';

        $command = $this->buildMp4DownloadCommand($video, $inputPath, $outputPath);

        Log::info('Running sync MP4 conversion for download', [
            'video_id' => $video->id,
        ]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        // Clean up temp SRT file
        $this->cleanupTempSrt($video->id);

        if ($returnCode !== 0 || ! file_exists($outputPath) || filesize($outputPath) < 1000) {
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
            $outputText = implode("\n", $output);
            throw new \Exception('MP4 conversion failed: '.substr($outputText, -500));
        }

        return $outputPath;
    }

    public function buildMp4DownloadCommand(Video $video, string $inputPath, string $outputPath): string
    {
        $ffmpegPath = config('media-library.ffmpeg_path');
        $srtPath = $this->generateTempSrt($video);

        if ($srtPath) {
            return sprintf(
                '%s -y -threads 1 -i %s -i %s -vf "scale=min(iw\,1920):min(ih\,1080):force_original_aspect_ratio=decrease,pad=ceil(iw/2)*2:ceil(ih/2)*2" -c:v libx264 -preset fast -crf 22 -maxrate 5M -bufsize 3M -pix_fmt yuv420p -c:a aac -b:a 128k -c:s mov_text -metadata:s:s:0 language=eng -max_muxing_queue_size 1024 -movflags +faststart %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($inputPath),
                escapeshellarg($srtPath),
                escapeshellarg($outputPath)
            );
        }

        return sprintf(
            '%s -y -threads 1 -i %s -vf "scale=min(iw\,1920):min(ih\,1080):force_original_aspect_ratio=decrease,pad=ceil(iw/2)*2:ceil(ih/2)*2" -c:v libx264 -preset fast -crf 22 -maxrate 5M -bufsize 3M -pix_fmt yuv420p -c:a aac -b:a 128k -max_muxing_queue_size 1024 -movflags +faststart %s 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );
    }

    protected function generateTempSrt(Video $video): ?string
    {
        $srt = $this->captionService->generateSrt($video);

        if (! $srt) {
            return null;
        }

        $srtPath = storage_path("app/temp_captions_{$video->id}.srt");
        file_put_contents($srtPath, $srt);

        return $srtPath;
    }

    public function cleanupTempSrt(int $videoId): void
    {
        $srtPath = storage_path("app/temp_captions_{$videoId}.srt");
        if (file_exists($srtPath)) {
            @unlink($srtPath);
        }
    }

    public function findMp4Download(Video $video): ?string
    {
        $pattern = storage_path('app/mp4-downloads/video_'.$video->id.'_*.mp4');
        $files = glob($pattern);

        if (empty($files)) {
            return null;
        }

        // Return the most recent file
        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        return $files[0];
    }
}

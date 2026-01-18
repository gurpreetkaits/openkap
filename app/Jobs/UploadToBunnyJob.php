<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UploadToBunnyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour max

    public $tries = 3;

    public function __construct(
        public Video $video
    ) {}

    public function handle(BunnyStreamService $bunnyService): void
    {
        Log::info('Starting Bunny upload job', [
            'video_id' => $this->video->id,
        ]);

        // Skip if already uploaded to Bunny
        if ($this->video->bunny_video_id && $this->video->bunny_status === 'ready') {
            Log::info('Video already on Bunny, skipping', ['video_id' => $this->video->id]);

            return;
        }

        // Get the video file from media library
        $media = $this->video->getFirstMedia('videos');
        if (! $media) {
            Log::error('No video file found for Bunny upload', ['video_id' => $this->video->id]);
            $this->video->update(['bunny_error' => 'No video file found']);

            return;
        }

        $videoPath = $media->getPath();
        if (! file_exists($videoPath)) {
            Log::error('Video file does not exist', ['video_id' => $this->video->id, 'path' => $videoPath]);
            $this->video->update(['bunny_error' => 'Video file not found on disk']);

            return;
        }

        try {
            // Create video in Bunny if not already created
            if (! $this->video->bunny_video_id) {
                $bunnyVideo = $bunnyService->createVideo($this->video->title);
                $this->video->update([
                    'bunny_video_id' => $bunnyVideo['guid'],
                    'bunny_library_id' => $bunnyService->getLibraryId(),
                    'bunny_status' => 'uploading',
                    'storage_type' => 'bunny',
                ]);
            } else {
                $this->video->update(['bunny_status' => 'uploading']);
            }

            // Upload to Bunny using direct PUT
            $this->uploadToBunny($bunnyService, $videoPath);

            // Update status
            $this->video->update(['bunny_status' => 'processing']);

            Log::info('Bunny upload completed, video is processing', [
                'video_id' => $this->video->id,
                'bunny_video_id' => $this->video->bunny_video_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Bunny upload failed', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
            ]);

            $this->video->update([
                'bunny_status' => 'error',
                'bunny_error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw for retry
        }
    }

    /**
     * Upload video file to Bunny using PUT request
     */
    private function uploadToBunny(BunnyStreamService $bunnyService, string $videoPath): void
    {
        $libraryId = config('services.bunny.library_id');
        $apiKey = config('services.bunny.api_key');
        $videoId = $this->video->bunny_video_id;

        $url = "https://video.bunnycdn.com/library/{$libraryId}/videos/{$videoId}";

        // Use cURL for large file upload with streaming
        $fileSize = filesize($videoPath);
        $fileHandle = fopen($videoPath, 'rb');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fileHandle,
            CURLOPT_INFILESIZE => $fileSize,
            CURLOPT_HTTPHEADER => [
                'AccessKey: '.$apiKey,
                'Content-Type: application/octet-stream',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3600,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($fileHandle);

        if ($httpCode !== 200) {
            throw new \Exception("Bunny upload failed with HTTP {$httpCode}: {$response} {$error}");
        }

        Log::info('Video uploaded to Bunny successfully', [
            'video_id' => $this->video->id,
            'bunny_video_id' => $videoId,
            'file_size' => $fileSize,
        ]);
    }
}

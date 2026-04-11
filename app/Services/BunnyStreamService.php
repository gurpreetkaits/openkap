<?php

namespace App\Services;

use App\Http\Integrations\Bunny\BunnyConnector;
use App\Http\Integrations\Bunny\Requests\CreateVideoRequest;
use App\Http\Integrations\Bunny\Requests\DeleteVideoRequest;
use App\Http\Integrations\Bunny\Requests\GetVideoRequest;
use App\Http\Integrations\Bunny\Requests\ListVideosRequest;
use App\Http\Integrations\Bunny\Requests\UpdateVideoRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BunnyStreamService
{
    protected BunnyConnector $connector;

    protected ?User $user = null;

    protected ?string $correlationId = null;

    public function __construct(?BunnyConnector $connector = null)
    {
        $this->connector = $connector ?? new BunnyConnector;
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Set the user for logging
     */
    public function forUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set correlation ID for tracing related requests
     */
    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Get the connector with logging configured
     */
    protected function getConnector(): BunnyConnector
    {
        return $this->connector
            ->forUser($this->user)
            ->withCorrelationId($this->correlationId);
    }

    /**
     * Check if Bunny Stream is configured
     */
    public function isConfigured(): bool
    {
        return $this->connector->isConfigured();
    }

    /**
     * Create a new video entry in Bunny Stream
     * This must be called before uploading via TUS
     *
     * @param  string  $title  Video title
     * @param  string|null  $collectionId  Optional collection ID for organization
     * @return array{guid: string, ...}
     *
     * @throws \Exception
     */
    public function createVideo(string $title, ?string $collectionId = null): array
    {
        if (! $this->isConfigured()) {
            Log::error('Bunny Stream: Not configured - missing BUNNY_LIBRARY_ID or BUNNY_API_KEY');
            throw new \Exception('Bunny Stream is not configured. Check BUNNY_LIBRARY_ID and BUNNY_API_KEY environment variables.');
        }

        $connector = $this->getConnector()
            ->withLogContext([
                'operation' => 'create_video',
                'title' => $title,
            ]);

        $request = new CreateVideoRequest(
            libraryId: $this->connector->getLibraryId(),
            title: $title,
            collectionId: $collectionId
        );

        $response = $connector->send($request);

        if (! $response->successful()) {
            Log::error('Bunny Stream: Failed to create video', [
                'status' => $response->status(),
                'body' => $response->body(),
                'library_id' => $this->connector->getLibraryId(),
            ]);
            throw new \Exception('Failed to create video in Bunny Stream (HTTP '.$response->status().'): '.$response->body());
        }

        return $response->json();
    }

    /**
     * Generate TUS upload credentials for direct upload from client
     * The signature ensures secure, time-limited upload authorization
     *
     * @param  string  $videoId  Bunny video GUID
     * @param  int|null  $expiresInSeconds  Custom expiry time
     * @return array{uploadUrl: string, libraryId: string, videoId: string, expireTime: int, signature: string}
     */
    public function generateUploadCredentials(string $videoId, ?int $expiresInSeconds = null): array
    {
        return $this->connector->generateUploadCredentials($videoId, $expiresInSeconds);
    }

    /**
     * Generate signed URL for secure video playback
     * Uses token authentication to prevent unauthorized access
     *
     * @param  string  $videoId  Bunny video GUID
     * @param  int|null  $expiresInSeconds  Custom expiry time
     * @return array{hlsUrl: string, embedUrl: string, thumbnailUrl: string, expiresAt: string}
     */
    public function generateSignedPlaybackUrl(string $videoId, ?int $expiresInSeconds = null): array
    {
        return $this->connector->generateSignedPlaybackUrl($videoId, $expiresInSeconds);
    }

    /**
     * Get video status and metadata from Bunny
     *
     * @param  string  $videoId  Bunny video GUID
     * @return array{status: string, duration: int, size: int, width: int, height: int, ...}
     *
     * @throws \Exception
     */
    public function getVideoStatus(string $videoId): array
    {
        $connector = $this->getConnector()
            ->withLogContext([
                'operation' => 'get_video_status',
                'video_id' => $videoId,
            ]);

        $request = new GetVideoRequest(
            libraryId: $this->connector->getLibraryId(),
            videoId: $videoId
        );

        $response = $connector->send($request);

        if (! $response->successful()) {
            Log::error('Bunny Stream: Failed to get video status', [
                'videoId' => $videoId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to get video status from Bunny Stream');
        }

        $data = $response->json();

        // Bunny status codes: 0=created, 1=uploaded, 2=processing, 3=transcoding, 4=finished, 5=error
        $statusMap = [
            0 => 'pending',
            1 => 'uploaded',
            2 => 'processing',
            3 => 'transcoding',
            4 => 'ready',
            5 => 'error',
        ];

        return [
            'videoId' => $data['guid'] ?? $videoId,
            'status' => $statusMap[$data['status'] ?? 0] ?? 'unknown',
            'rawStatus' => $data['status'] ?? 0,
            'duration' => $data['length'] ?? 0,
            'size' => $data['storageSize'] ?? 0,
            'width' => $data['width'] ?? 0,
            'height' => $data['height'] ?? 0,
            'availableResolutions' => $data['availableResolutions'] ?? null,
            'encodeProgress' => $data['encodeProgress'] ?? 0,
            'title' => $data['title'] ?? '',
        ];
    }

    /**
     * Delete a video from Bunny Stream
     *
     * @param  string  $videoId  Bunny video GUID
     */
    public function deleteVideo(string $videoId): bool
    {
        $connector = $this->getConnector()
            ->withLogContext([
                'operation' => 'delete_video',
                'video_id' => $videoId,
            ]);

        $request = new DeleteVideoRequest(
            libraryId: $this->connector->getLibraryId(),
            videoId: $videoId
        );

        $response = $connector->send($request);

        if (! $response->successful()) {
            Log::error('Bunny Stream: Failed to delete video', [
                'videoId' => $videoId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Update video metadata in Bunny
     *
     * @param  string  $videoId  Bunny video GUID
     * @param  array  $data  Data to update (title, etc.)
     */
    public function updateVideo(string $videoId, array $data): bool
    {
        $connector = $this->getConnector()
            ->withLogContext([
                'operation' => 'update_video',
                'video_id' => $videoId,
            ]);

        $request = new UpdateVideoRequest(
            libraryId: $this->connector->getLibraryId(),
            videoId: $videoId,
            data: $data
        );

        $response = $connector->send($request);

        if (! $response->successful()) {
            Log::error('Bunny Stream: Failed to update video', [
                'videoId' => $videoId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * List all videos in the library
     *
     * @param  int  $page  Page number (1-indexed)
     * @param  int  $perPage  Items per page (max 100)
     * @param  string|null  $search  Search query
     * @param  string|null  $collectionId  Filter by collection
     */
    public function listVideos(int $page = 1, int $perPage = 100, ?string $search = null, ?string $collectionId = null): array
    {
        $connector = $this->getConnector()
            ->withLogContext([
                'operation' => 'list_videos',
                'page' => $page,
                'per_page' => $perPage,
            ]);

        $request = new ListVideosRequest(
            libraryId: $this->connector->getLibraryId(),
            page: $page,
            perPage: $perPage,
            search: $search,
            collectionId: $collectionId
        );

        $response = $connector->send($request);

        if (! $response->successful()) {
            Log::error('Bunny Stream: Failed to list videos', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['items' => [], 'totalItems' => 0];
        }

        return $response->json();
    }

    /**
     * Generate a signed direct MP4 download URL.
     */
    public function generateSignedDownloadUrl(string $videoId, string $resolution = '720p', ?int $expiresInSeconds = null): string
    {
        return $this->connector->generateSignedDownloadUrl($videoId, $resolution, $expiresInSeconds);
    }

    /**
     * Get the library ID
     */
    public function getLibraryId(): string
    {
        return $this->connector->getLibraryId();
    }

    /**
     * Get the CDN hostname
     */
    public function getCdnHostname(): string
    {
        return $this->connector->getCdnHostname();
    }
}

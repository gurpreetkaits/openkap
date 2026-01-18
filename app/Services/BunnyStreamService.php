<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BunnyStreamService
{
    private string $libraryId;

    private string $apiKey;

    private string $cdnHostname;

    private string $securityKey;

    private int $playbackExpiry;

    private int $uploadExpiry;

    private string $baseUrl;

    private string $tusEndpoint;

    public function __construct()
    {
        $this->libraryId = config('services.bunny.library_id') ?? '';
        $this->apiKey = config('services.bunny.api_key') ?? '';
        $this->cdnHostname = config('services.bunny.cdn_hostname') ?? '';
        $this->securityKey = config('services.bunny.security_key') ?? '';
        $this->playbackExpiry = (int) (config('services.bunny.playback_expiry') ?? 3600);
        $this->uploadExpiry = (int) (config('services.bunny.upload_expiry') ?? 7200);
        $this->baseUrl = config('services.bunny.base_url') ?? 'https://video.bunnycdn.com';
        $this->tusEndpoint = config('services.bunny.tus_endpoint') ?? 'https://video.bunnycdn.com/tusupload';
    }

    /**
     * Check if Bunny Stream is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->libraryId) && ! empty($this->apiKey);
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
        $payload = ['title' => $title];

        if ($collectionId) {
            $payload['collectionId'] = $collectionId;
        }

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/library/{$this->libraryId}/videos", $payload);

        if (! $response->successful()) {
            Log::error('Bunny Stream: Failed to create video', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to create video in Bunny Stream: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Generate TUS upload credentials for direct upload from client
     * The signature ensures secure, time-limited upload authorization
     *
     * Formula: sha256(library_id + api_key + expiration_time + video_id)
     *
     * @param  string  $videoId  Bunny video GUID
     * @param  int|null  $expiresInSeconds  Custom expiry time
     * @return array{uploadUrl: string, libraryId: string, videoId: string, expireTime: int, signature: string}
     */
    public function generateUploadCredentials(string $videoId, ?int $expiresInSeconds = null): array
    {
        $expireTime = time() + ($expiresInSeconds ?? $this->uploadExpiry);

        // Generate SHA256 signature as per Bunny docs
        // Formula: sha256(library_id + api_key + expiration_time + video_id)
        $signatureString = $this->libraryId.$this->apiKey.$expireTime.$videoId;
        $signature = hash('sha256', $signatureString);

        return [
            'uploadUrl' => $this->tusEndpoint,
            'libraryId' => $this->libraryId,
            'videoId' => $videoId,
            'expireTime' => $expireTime,
            'signature' => $signature,
        ];
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
        $expireTime = time() + ($expiresInSeconds ?? $this->playbackExpiry);

        // Generate signed HLS URL
        $hlsPath = "/{$videoId}/playlist.m3u8";
        $hlsToken = $this->generateUrlToken($hlsPath, $expireTime);

        // Generate signed thumbnail URL (longer expiry)
        $thumbnailExpireTime = time() + 86400; // 24 hours for thumbnails
        $thumbnailPath = "/{$videoId}/thumbnail.jpg";
        $thumbnailToken = $this->generateUrlToken($thumbnailPath, $thumbnailExpireTime);

        return [
            'hlsUrl' => "https://{$this->cdnHostname}{$hlsPath}?token={$hlsToken}&expires={$expireTime}",
            'embedUrl' => "https://iframe.mediadelivery.net/embed/{$this->libraryId}/{$videoId}?token={$hlsToken}&expires={$expireTime}",
            'thumbnailUrl' => "https://{$this->cdnHostname}{$thumbnailPath}?token={$thumbnailToken}&expires={$thumbnailExpireTime}",
            'expiresAt' => date('c', $expireTime),
        ];
    }

    /**
     * Generate URL token for signed URLs
     *
     * @param  string  $path  URL path (e.g., /video-id/playlist.m3u8)
     * @param  int  $expireTime  Unix timestamp
     */
    private function generateUrlToken(string $path, int $expireTime): string
    {
        // Token formula: sha256(security_key + path + expiration)
        $tokenString = $this->securityKey.$path.$expireTime;

        return hash('sha256', $tokenString);
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
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->get("{$this->baseUrl}/library/{$this->libraryId}/videos/{$videoId}");

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
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->delete("{$this->baseUrl}/library/{$this->libraryId}/videos/{$videoId}");

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
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/library/{$this->libraryId}/videos/{$videoId}", $data);

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
        $params = [
            'page' => $page,
            'itemsPerPage' => min($perPage, 100),
        ];

        if ($search) {
            $params['search'] = $search;
        }

        if ($collectionId) {
            $params['collection'] = $collectionId;
        }

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->get("{$this->baseUrl}/library/{$this->libraryId}/videos", $params);

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
     * Get the library ID
     */
    public function getLibraryId(): string
    {
        return $this->libraryId;
    }

    /**
     * Get the CDN hostname
     */
    public function getCdnHostname(): string
    {
        return $this->cdnHostname;
    }
}

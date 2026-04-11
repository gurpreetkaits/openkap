<?php

namespace App\Http\Integrations\Bunny;

use App\Http\Integrations\BaseConnector;

class BunnyConnector extends BaseConnector
{
    protected string $libraryId;

    protected string $apiKey;

    protected string $cdnHostname;

    protected string $securityKey;

    protected int $playbackExpiry;

    protected int $uploadExpiry;

    protected string $tusEndpoint;

    public function __construct(
        ?string $libraryId = null,
        ?string $apiKey = null
    ) {
        $this->libraryId = $libraryId ?? config('services.bunny.library_id') ?? '';
        $this->apiKey = $apiKey ?? config('services.bunny.api_key') ?? '';
        $this->cdnHostname = config('services.bunny.cdn_hostname') ?? '';
        $this->securityKey = config('services.bunny.security_key') ?? '';
        $this->playbackExpiry = (int) (config('services.bunny.playback_expiry') ?? 3600);
        $this->uploadExpiry = (int) (config('services.bunny.upload_expiry') ?? 7200);
        $this->tusEndpoint = config('services.bunny.tus_endpoint') ?? 'https://video.bunnycdn.com/tusupload';
    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return config('services.bunny.base_url') ?? 'https://video.bunnycdn.com';
    }

    /**
     * Default headers including API key
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'AccessKey' => $this->apiKey,
        ];
    }

    /**
     * Get the service name for logging
     */
    protected function getServiceName(): string
    {
        return 'bunny';
    }

    /**
     * Check if Bunny Stream is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->libraryId) && ! empty($this->apiKey);
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

    /**
     * Get the TUS endpoint
     */
    public function getTusEndpoint(): string
    {
        return $this->tusEndpoint;
    }

    /**
     * Generate TUS upload credentials for direct upload from client
     */
    public function generateUploadCredentials(string $videoId, ?int $expiresInSeconds = null): array
    {
        $expireTime = time() + ($expiresInSeconds ?? $this->uploadExpiry);

        // Generate SHA256 signature as per Bunny docs
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
     * Generate a signed direct MP4 download URL for a Bunny video.
     */
    public function generateSignedDownloadUrl(string $videoId, string $resolution = '720p', ?int $expiresInSeconds = null): string
    {
        $expireTime = time() + ($expiresInSeconds ?? 3600);
        $path = "/{$videoId}/play_{$resolution}.mp4";
        $token = $this->generateUrlToken($path, $expireTime);

        return "https://{$this->cdnHostname}{$path}?token={$token}&expires={$expireTime}";
    }

    /**
     * Generate URL token for signed URLs
     */
    private function generateUrlToken(string $path, int $expireTime): string
    {
        $tokenString = $this->securityKey.$path.$expireTime;

        return hash('sha256', $tokenString);
    }
}

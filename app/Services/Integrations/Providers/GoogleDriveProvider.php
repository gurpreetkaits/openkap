<?php

namespace App\Services\Integrations\Providers;

use App\Data\IntegrationShareResultData;
use App\Data\IntegrationTokenData;
use App\Models\Integration;
use App\Models\Video;
use App\Services\Integrations\IntegrationProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveProvider implements IntegrationProviderInterface
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.integrations.google_drive.client_id', '');
        $this->clientSecret = config('services.integrations.google_drive.client_secret', '');
        $this->redirectUri = config('services.integrations.google_drive.redirect_uri', '');
    }

    public function getProviderName(): string
    {
        return 'google_drive';
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return "https://accounts.google.com/o/oauth2/v2/auth?{$params}";
    }

    public function handleCallback(string $code): IntegrationTokenData
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \RuntimeException('Google Drive OAuth failed: '.($data['error_description'] ?? $data['error']));
        }

        // Fetch user info
        $userInfo = Http::withToken($data['access_token'])
            ->get('https://www.googleapis.com/oauth2/v2/userinfo')
            ->json();

        return new IntegrationTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? null,
            expires_in: $data['expires_in'] ?? 3600,
            external_user_id: $userInfo['id'] ?? null,
            external_user_name: $userInfo['email'] ?? null,
            metadata: null,
        );
    }

    public function refreshToken(string $refreshToken): IntegrationTokenData
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \RuntimeException('Google Drive token refresh failed: '.($data['error_description'] ?? $data['error']));
        }

        return new IntegrationTokenData(
            access_token: $data['access_token'],
            refresh_token: null, // Keep existing refresh token
            expires_in: $data['expires_in'] ?? 3600,
            external_user_id: null,
            external_user_name: null,
            metadata: null,
        );
    }

    public function shareVideo(Integration $integration, Video $video, array $options): IntegrationShareResultData
    {
        $folderId = $options['target_id'] ?? 'root';
        $shareUrl = $video->share_token
            ? config('services.frontend.url').'/share/video/'.$video->share_token
            : config('services.frontend.url').'/video/'.$video->id;

        try {
            // Create a file with video link info (shortcut/description)
            $metadata = [
                'name' => $video->title.'.url',
                'mimeType' => 'application/octet-stream',
                'parents' => [$folderId],
                'description' => ($options['message'] ?? '')." - {$shareUrl}",
            ];

            $response = Http::withToken($integration->access_token)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart', [
                    [
                        'name' => 'metadata',
                        'contents' => json_encode($metadata),
                        'headers' => ['Content-Type' => 'application/json'],
                    ],
                    [
                        'name' => 'file',
                        'contents' => "[InternetShortcut]\nURL={$shareUrl}\n",
                        'headers' => ['Content-Type' => 'application/octet-stream'],
                    ],
                ]);

            // Fallback: create using simple metadata-only upload
            if (! $response->successful()) {
                $response = Http::withToken($integration->access_token)
                    ->post('https://www.googleapis.com/drive/v3/files', [
                        'name' => $video->title,
                        'mimeType' => 'application/vnd.google-apps.shortcut',
                        'shortcutDetails' => ['targetId' => ''],
                        'parents' => [$folderId],
                        'description' => $shareUrl,
                    ]);
            }

            $data = $response->json();

            if (isset($data['id'])) {
                return new IntegrationShareResultData(
                    success: true,
                    external_url: "https://drive.google.com/file/d/{$data['id']}/view",
                    external_id: $data['id'],
                    error: null,
                    metadata: ['folder_id' => $folderId],
                );
            }

            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: $data['error']['message'] ?? 'Failed to upload to Google Drive',
                metadata: null,
            );
        } catch (\Exception $e) {
            Log::error('Google Drive share failed', ['error' => $e->getMessage()]);

            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: $e->getMessage(),
                metadata: null,
            );
        }
    }

    public function revokeAccess(Integration $integration): bool
    {
        try {
            Http::post('https://oauth2.googleapis.com/revoke', [
                'token' => $integration->access_token,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('Google Drive revoke failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAvailableTargets(Integration $integration): array
    {
        $targets = [
            ['id' => 'root', 'name' => 'My Drive (root)', 'type' => 'folder'],
        ];

        try {
            $response = Http::withToken($integration->access_token)
                ->get('https://www.googleapis.com/drive/v3/files', [
                    'q' => "mimeType='application/vnd.google-apps.folder' and trashed=false",
                    'fields' => 'files(id,name)',
                    'pageSize' => 100,
                    'orderBy' => 'name',
                ]);

            $data = $response->json();

            foreach ($data['files'] ?? [] as $folder) {
                $targets[] = [
                    'id' => $folder['id'],
                    'name' => $folder['name'],
                    'type' => 'folder',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Drive targets fetch failed', ['error' => $e->getMessage()]);
        }

        return $targets;
    }
}

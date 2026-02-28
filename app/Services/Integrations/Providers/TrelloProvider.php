<?php

namespace App\Services\Integrations\Providers;

use App\Data\IntegrationShareResultData;
use App\Data\IntegrationTokenData;
use App\Models\Integration;
use App\Models\Video;
use App\Services\Integrations\IntegrationProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrelloProvider implements IntegrationProviderInterface
{
    private string $apiKey;

    private string $apiSecret;

    private string $redirectUri;

    public function __construct()
    {
        $this->apiKey = config('services.integrations.trello.api_key', '');
        $this->apiSecret = config('services.integrations.trello.api_secret', '');
        $this->redirectUri = config('services.integrations.trello.redirect_uri', '');
    }

    public function getProviderName(): string
    {
        return 'trello';
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'expiration' => 'never',
            'name' => 'ScreenBuddy',
            'scope' => 'read,write',
            'response_type' => 'token',
            'key' => $this->apiKey,
            'return_url' => $this->redirectUri.'?state='.$state,
            'callback_method' => 'fragment',
        ]);

        return "https://trello.com/1/authorize?{$params}";
    }

    public function handleCallback(string $code): IntegrationTokenData
    {
        // For Trello, 'code' is actually the token (fragment-based auth)
        $token = $code;

        // Verify token by fetching member info
        $response = Http::get('https://api.trello.com/1/members/me', [
            'key' => $this->apiKey,
            'token' => $token,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Trello token validation failed');
        }

        $data = $response->json();

        return new IntegrationTokenData(
            access_token: $token,
            refresh_token: null,
            expires_in: null, // Trello tokens with 'never' don't expire
            external_user_id: $data['id'] ?? null,
            external_user_name: $data['fullName'] ?? $data['username'] ?? null,
            metadata: [
                'username' => $data['username'] ?? null,
            ],
        );
    }

    public function refreshToken(string $refreshToken): IntegrationTokenData
    {
        // Trello tokens don't expire when set to 'never'
        throw new \RuntimeException('Trello tokens do not require refresh');
    }

    public function shareVideo(Integration $integration, Video $video, array $options): IntegrationShareResultData
    {
        $listId = $options['target_id'];
        $message = $options['message'] ?? '';
        $shareUrl = $video->share_token
            ? config('services.frontend.url').'/share/video/'.$video->share_token
            : config('services.frontend.url').'/video/'.$video->id;

        try {
            // Create a card
            $response = Http::post('https://api.trello.com/1/cards', [
                'key' => $this->apiKey,
                'token' => $integration->access_token,
                'idList' => $listId,
                'name' => $video->title,
                'desc' => $message ? "{$message}\n\n{$shareUrl}" : $shareUrl,
                'urlSource' => $shareUrl,
            ]);

            $data = $response->json();

            if (isset($data['id'])) {
                return new IntegrationShareResultData(
                    success: true,
                    external_url: $data['shortUrl'] ?? $data['url'] ?? null,
                    external_id: $data['id'],
                    error: null,
                    metadata: ['list_id' => $listId],
                );
            }

            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: $data['message'] ?? 'Failed to create Trello card',
                metadata: null,
            );
        } catch (\Exception $e) {
            Log::error('Trello share failed', ['error' => $e->getMessage()]);

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
            Http::delete("https://api.trello.com/1/tokens/{$integration->access_token}", [
                'key' => $this->apiKey,
                'token' => $integration->access_token,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('Trello revoke failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAvailableTargets(Integration $integration): array
    {
        $targets = [];

        try {
            // Fetch boards
            $response = Http::get('https://api.trello.com/1/members/me/boards', [
                'key' => $this->apiKey,
                'token' => $integration->access_token,
                'filter' => 'open',
                'fields' => 'id,name',
                'lists' => 'open',
                'list_fields' => 'id,name',
            ]);

            $boards = $response->json();

            foreach ($boards as $board) {
                foreach ($board['lists'] ?? [] as $list) {
                    $targets[] = [
                        'id' => $list['id'],
                        'name' => $board['name'].' / '.$list['name'],
                        'type' => 'list',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Trello targets fetch failed', ['error' => $e->getMessage()]);
        }

        return $targets;
    }
}

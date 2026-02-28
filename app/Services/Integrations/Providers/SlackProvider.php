<?php

namespace App\Services\Integrations\Providers;

use App\Data\IntegrationShareResultData;
use App\Data\IntegrationTokenData;
use App\Models\Integration;
use App\Models\Video;
use App\Services\Integrations\IntegrationProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackProvider implements IntegrationProviderInterface
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.integrations.slack.client_id', '');
        $this->clientSecret = config('services.integrations.slack.client_secret', '');
        $this->redirectUri = config('services.integrations.slack.redirect_uri', '');
    }

    public function getProviderName(): string
    {
        return 'slack';
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'scope' => 'chat:write,channels:read,groups:read',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ]);

        return "https://slack.com/oauth/v2/authorize?{$params}";
    }

    public function handleCallback(string $code): IntegrationTokenData
    {
        $response = Http::asForm()->post('https://slack.com/api/oauth.v2.access', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        $data = $response->json();

        if (! ($data['ok'] ?? false)) {
            throw new \RuntimeException('Slack OAuth failed: '.($data['error'] ?? 'Unknown error'));
        }

        return new IntegrationTokenData(
            access_token: $data['access_token'],
            refresh_token: null,
            expires_in: null,
            external_user_id: $data['authed_user']['id'] ?? null,
            external_user_name: $data['team']['name'] ?? null,
            metadata: [
                'team_id' => $data['team']['id'] ?? null,
                'team_name' => $data['team']['name'] ?? null,
                'bot_user_id' => $data['bot_user_id'] ?? null,
            ],
        );
    }

    public function refreshToken(string $refreshToken): IntegrationTokenData
    {
        // Slack bot tokens don't expire
        throw new \RuntimeException('Slack tokens do not require refresh');
    }

    public function shareVideo(Integration $integration, Video $video, array $options): IntegrationShareResultData
    {
        $channelId = $options['target_id'];
        $message = $options['message'] ?? '';
        $shareUrl = $video->share_token
            ? config('services.frontend.url').'/share/video/'.$video->share_token
            : config('services.frontend.url').'/video/'.$video->id;

        $text = $message
            ? "{$message}\n\n:movie_camera: *{$video->title}*\n{$shareUrl}"
            : ":movie_camera: *{$video->title}*\n{$shareUrl}";

        try {
            $response = Http::withToken($integration->access_token)
                ->post('https://slack.com/api/chat.postMessage', [
                    'channel' => $channelId,
                    'text' => $text,
                    'unfurl_links' => true,
                ]);

            $data = $response->json();

            if (! ($data['ok'] ?? false)) {
                return new IntegrationShareResultData(
                    success: false,
                    external_url: null,
                    external_id: null,
                    error: $data['error'] ?? 'Failed to post message',
                    metadata: null,
                );
            }

            return new IntegrationShareResultData(
                success: true,
                external_url: null,
                external_id: $data['ts'] ?? null,
                error: null,
                metadata: ['channel' => $channelId],
            );
        } catch (\Exception $e) {
            Log::error('Slack share failed', ['error' => $e->getMessage()]);

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
            Http::asForm()->post('https://slack.com/api/auth.revoke', [
                'token' => $integration->access_token,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('Slack revoke failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAvailableTargets(Integration $integration): array
    {
        $targets = [];

        try {
            // Fetch public channels
            $response = Http::withToken($integration->access_token)
                ->get('https://slack.com/api/conversations.list', [
                    'types' => 'public_channel,private_channel',
                    'exclude_archived' => true,
                    'limit' => 200,
                ]);

            $data = $response->json();

            if ($data['ok'] ?? false) {
                foreach ($data['channels'] ?? [] as $channel) {
                    $targets[] = [
                        'id' => $channel['id'],
                        'name' => '#'.$channel['name'],
                        'type' => $channel['is_private'] ? 'private_channel' : 'public_channel',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Slack targets fetch failed', ['error' => $e->getMessage()]);
        }

        return $targets;
    }
}

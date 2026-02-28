<?php

namespace App\Managers;

use App\Models\Integration;
use App\Models\User;
use App\Repositories\IntegrationActionRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\VideoRepository;
use App\Services\Integrations\IntegrationProviderFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntegrationManager
{
    public function __construct(
        protected IntegrationRepository $integrations,
        protected IntegrationActionRepository $integrationActions,
        protected IntegrationProviderFactory $providerFactory,
        protected VideoRepository $videos,
    ) {}

    public function getAvailableProviders(int $userId): array
    {
        $supported = $this->providerFactory->getSupportedProviders();
        $connected = $this->integrations->findAllByUser($userId)
            ->keyBy('provider');

        return array_map(function ($provider) use ($connected) {
            $integration = $connected->get($provider['id']);

            return [
                ...$provider,
                'connected' => $integration !== null && $integration->isActive(),
                'status' => $integration?->status ?? 'disconnected',
                'external_user_name' => $integration?->external_user_name,
            ];
        }, $supported);
    }

    public function initiateOAuth(User $user, string $provider): string
    {
        $providerInstance = $this->providerFactory->make($provider);

        $state = Str::random(40);
        Cache::put(
            "integration_oauth:{$state}",
            ['user_id' => $user->id, 'provider' => $provider],
            now()->addMinutes(10)
        );

        return $providerInstance->getAuthorizationUrl($state);
    }

    public function handleOAuthCallback(string $provider, string $code, string $state): Integration
    {
        $cached = Cache::pull("integration_oauth:{$state}");

        if (! $cached) {
            throw new \RuntimeException('Invalid or expired OAuth state');
        }

        $userId = $cached['user_id'];
        $providerInstance = $this->providerFactory->make($provider);
        $tokenData = $providerInstance->handleCallback($code);

        $existing = $this->integrations->findByUserAndProvider($userId, $provider);

        if ($existing) {
            $this->integrations->update($existing, [
                'access_token' => $tokenData->access_token,
                'refresh_token' => $tokenData->refresh_token ?? $existing->refresh_token,
                'token_expires_at' => $tokenData->expires_in
                    ? now()->addSeconds($tokenData->expires_in)
                    : null,
                'status' => 'active',
                'external_user_id' => $tokenData->external_user_id ?? $existing->external_user_id,
                'external_user_name' => $tokenData->external_user_name ?? $existing->external_user_name,
                'metadata' => $tokenData->metadata ?? $existing->metadata,
            ]);

            return $existing->fresh();
        }

        return $this->integrations->createIntegration([
            'user_id' => $userId,
            'provider' => $provider,
            'status' => 'active',
            'access_token' => $tokenData->access_token,
            'refresh_token' => $tokenData->refresh_token,
            'token_expires_at' => $tokenData->expires_in
                ? now()->addSeconds($tokenData->expires_in)
                : null,
            'external_user_id' => $tokenData->external_user_id,
            'external_user_name' => $tokenData->external_user_name,
            'metadata' => $tokenData->metadata,
        ]);
    }

    public function disconnectIntegration(User $user, string $provider): bool
    {
        $integration = $this->integrations->findByUserAndProvider($user->id, $provider);

        if (! $integration) {
            return false;
        }

        try {
            $providerInstance = $this->providerFactory->make($provider);
            $providerInstance->revokeAccess($integration);
        } catch (\Exception $e) {
            Log::warning("Failed to revoke {$provider} access", ['error' => $e->getMessage()]);
        }

        return $this->integrations->delete($integration);
    }

    public function refreshTokenIfNeeded(Integration $integration): Integration
    {
        if (! $integration->needsRefresh()) {
            return $integration;
        }

        if (! $integration->refresh_token) {
            $this->integrations->update($integration, ['status' => 'expired']);
            throw new \RuntimeException("Integration token expired and no refresh token available for {$integration->provider}");
        }

        try {
            $providerInstance = $this->providerFactory->make($integration->provider);
            $tokenData = $providerInstance->refreshToken($integration->refresh_token);

            $this->integrations->updateTokens(
                $integration,
                $tokenData->access_token,
                $tokenData->refresh_token,
                $tokenData->expires_in ? now()->addSeconds($tokenData->expires_in) : null,
            );

            return $integration->fresh();
        } catch (\Exception $e) {
            Log::error("Token refresh failed for {$integration->provider}", ['error' => $e->getMessage()]);
            $this->integrations->update($integration, ['status' => 'expired']);

            throw $e;
        }
    }

    public function shareVideoToIntegration(User $user, int $videoId, string $provider, array $options): array
    {
        $video = $this->videos->findOrFail($videoId);

        if ((int) $video->user_id !== $user->id) {
            throw new \RuntimeException('You do not have permission to share this video');
        }

        $integration = $this->integrations->findByUserAndProvider($user->id, $provider);

        if (! $integration || ! $integration->isActive()) {
            throw new \RuntimeException("No active {$provider} integration found");
        }

        $integration = $this->refreshTokenIfNeeded($integration);

        // Create action record
        $action = $this->integrationActions->createAction([
            'integration_id' => $integration->id,
            'video_id' => $videoId,
            'user_id' => $user->id,
            'action_type' => 'share_link',
            'status' => 'processing',
            'request_data' => $options,
        ]);

        try {
            $providerInstance = $this->providerFactory->make($provider);
            $result = $providerInstance->shareVideo($integration, $video, $options);

            $this->integrationActions->updateAction($action, [
                'status' => $result->success ? 'completed' : 'failed',
                'response_data' => [
                    'external_url' => $result->external_url,
                    'external_id' => $result->external_id,
                    'metadata' => $result->metadata,
                ],
                'error' => $result->error,
            ]);

            return $this->formatShareResult($action->fresh(), $result->toArray());
        } catch (\Exception $e) {
            $this->integrationActions->updateAction($action, [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function createBugInIntegration(User $user, int $videoId, string $provider, array $bugData): array
    {
        $video = $this->videos->findOrFail($videoId);

        if ((int) $video->user_id !== $user->id) {
            throw new \RuntimeException('You do not have permission to create bugs for this video');
        }

        $integration = $this->integrations->findByUserAndProvider($user->id, $provider);

        if (! $integration || ! $integration->isActive()) {
            throw new \RuntimeException("No active {$provider} integration found");
        }

        $integration = $this->refreshTokenIfNeeded($integration);

        // Create action record
        $action = $this->integrationActions->createAction([
            'integration_id' => $integration->id,
            'video_id' => $videoId,
            'user_id' => $user->id,
            'action_type' => 'create_bug',
            'status' => 'processing',
            'request_data' => $bugData,
        ]);

        try {
            $providerInstance = $this->providerFactory->make($provider);
            $result = $providerInstance->createBugIssue($integration, $video, $bugData);

            $this->integrationActions->updateAction($action, [
                'status' => $result->success ? 'completed' : 'failed',
                'response_data' => [
                    'external_url' => $result->external_url,
                    'external_id' => $result->external_id,
                    'metadata' => $result->metadata,
                ],
                'error' => $result->error,
            ]);

            return $this->formatShareResult($action->fresh(), $result->toArray());
        } catch (\Exception $e) {
            $this->integrationActions->updateAction($action, [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getIntegrationTargets(User $user, string $provider): array
    {
        $integration = $this->integrations->findByUserAndProvider($user->id, $provider);

        if (! $integration || ! $integration->isActive()) {
            throw new \RuntimeException("No active {$provider} integration found");
        }

        $integration = $this->refreshTokenIfNeeded($integration);

        $providerInstance = $this->providerFactory->make($provider);

        return $providerInstance->getAvailableTargets($integration);
    }

    public function getVideoShareHistory(int $videoId, int $userId): array
    {
        $actions = $this->integrationActions->findByVideoAndUser($videoId, $userId);

        return $actions->map(function ($action) {
            return $this->formatAction($action);
        })->toArray();
    }

    public function formatIntegration(Integration $integration): array
    {
        return [
            'id' => $integration->id,
            'provider' => $integration->provider,
            'status' => $integration->status,
            'external_user_name' => $integration->external_user_name,
            'connected_at' => $integration->created_at?->toIso8601String(),
        ];
    }

    protected function formatAction(mixed $action): array
    {
        return [
            'id' => $action->id,
            'provider' => $action->integration?->provider,
            'action_type' => $action->action_type,
            'status' => $action->status,
            'external_url' => $action->response_data['external_url'] ?? null,
            'error' => $action->error,
            'created_at' => $action->created_at?->toIso8601String(),
            'target_name' => $action->request_data['target_name'] ?? null,
        ];
    }

    protected function formatShareResult(mixed $action, array $result): array
    {
        return [
            'action_id' => $action->id,
            'status' => $action->status,
            'success' => $result['success'],
            'external_url' => $result['external_url'] ?? null,
            'error' => $result['error'] ?? null,
        ];
    }
}

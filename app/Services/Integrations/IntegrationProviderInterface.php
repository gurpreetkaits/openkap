<?php

namespace App\Services\Integrations;

use App\Data\IntegrationShareResultData;
use App\Data\IntegrationTokenData;
use App\Models\Integration;
use App\Models\Video;

interface IntegrationProviderInterface
{
    public function getProviderName(): string;

    public function getAuthorizationUrl(string $state): string;

    public function handleCallback(string $code): IntegrationTokenData;

    public function refreshToken(string $refreshToken): IntegrationTokenData;

    public function shareVideo(Integration $integration, Video $video, array $options): IntegrationShareResultData;

    public function revokeAccess(Integration $integration): bool;

    public function getAvailableTargets(Integration $integration): array;
}

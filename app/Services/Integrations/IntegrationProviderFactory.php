<?php

namespace App\Services\Integrations;

use App\Services\Integrations\Providers\JiraProvider;
use InvalidArgumentException;

class IntegrationProviderFactory
{
    public function make(string $provider): IntegrationProviderInterface
    {
        return match ($provider) {
            'jira' => app(JiraProvider::class),
            default => throw new InvalidArgumentException("Unsupported integration provider: {$provider}"),
        };
    }

    public function getSupportedProviders(): array
    {
        return [
            [
                'id' => 'jira',
                'name' => 'Jira',
                'description' => 'Create issues with video links',
                'icon' => 'jira',
            ],
        ];
    }
}

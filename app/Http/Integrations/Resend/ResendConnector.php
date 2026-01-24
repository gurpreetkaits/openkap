<?php

namespace App\Http\Integrations\Resend;

use App\Http\Integrations\BaseConnector;

class ResendConnector extends BaseConnector
{
    protected string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.resend.key') ?? '';
    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return config('services.resend.base_url') ?? 'https://api.resend.com';
    }

    /**
     * Default headers including API key
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
    }

    /**
     * Get the service name for logging
     */
    protected function getServiceName(): string
    {
        return 'resend';
    }

    /**
     * Check if Resend is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }
}

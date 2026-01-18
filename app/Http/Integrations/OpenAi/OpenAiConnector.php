<?php

namespace App\Http\Integrations\OpenAi;

use App\Http\Integrations\BaseConnector;
use Saloon\Http\Auth\TokenAuthentication;

class OpenAiConnector extends BaseConnector
{
    public function __construct(
        protected ?string $apiKey = null
    ) {
        $this->apiKey = $apiKey ?? config('services.openai.api_key');
    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.openai.com/v1';
    }

    /**
     * Default headers
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Default authentication using Bearer token
     */
    protected function defaultAuth(): TokenAuthentication
    {
        return new TokenAuthentication($this->apiKey);
    }

    /**
     * Get the service name for logging
     */
    protected function getServiceName(): string
    {
        return 'openai';
    }

    /**
     * Check if OpenAI is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }
}

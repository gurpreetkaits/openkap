<?php

namespace App\Http\Integrations\OpenAi;

use App\Http\Integrations\BaseConnector;
use Saloon\Http\Auth\TokenAuthenticator;

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
     *
     * Note: Content-Type is intentionally not set here.
     * Saloon automatically sets the correct Content-Type based on the request body type:
     * - HasJsonBody -> application/json
     * - HasMultipartBody -> multipart/form-data (for file uploads like transcription)
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }

    /**
     * Default authentication using Bearer token
     */
    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->apiKey);
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

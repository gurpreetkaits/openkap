<?php

namespace App\Http\Integrations\Concerns;

use App\Models\ApiLog;
use App\Models\User;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

trait LogsApiRequests
{
    protected ?User $logUser = null;

    protected ?string $correlationId = null;

    protected array $logContext = [];

    protected bool $loggingEnabled = true;

    /**
     * Set the user for logging
     */
    public function forUser(?User $user): static
    {
        $this->logUser = $user;

        return $this;
    }

    /**
     * Set correlation ID for tracing related requests
     */
    public function withCorrelationId(string $correlationId): static
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Add context to the log
     */
    public function withLogContext(array $context): static
    {
        $this->logContext = array_merge($this->logContext, $context);

        return $this;
    }

    /**
     * Disable logging for requests from this connector instance
     */
    public function withoutLogging(): static
    {
        $this->loggingEnabled = false;

        return $this;
    }

    /**
     * Get the service name for logging
     */
    abstract protected function getServiceName(): string;

    /**
     * Log the request before sending
     */
    protected function logRequest(PendingRequest $pendingRequest): ?int
    {
        if (! $this->loggingEnabled) {
            return null;
        }

        $request = $pendingRequest->getRequest();
        $url = (string) $pendingRequest->getUri();
        $parsedUrl = parse_url($url);
        $endpoint = ($parsedUrl['path'] ?? '').
            (isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '');

        // Get request body, handling multipart specially
        $body = $pendingRequest->body();
        $requestBody = $this->sanitizeRequestBody($body);

        $log = ApiLog::create([
            'service' => $this->getServiceName(),
            'endpoint' => $endpoint,
            'method' => $request->getMethod()->value,
            'request_body' => is_string($requestBody) ? $requestBody : json_encode($requestBody),
            'user_id' => $this->logUser?->id,
            'correlation_id' => $this->correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
            'context' => $this->logContext,
            'is_successful' => false, // Will be updated on response
        ]);

        return $log->id;
    }

    /**
     * Update log with response data
     */
    protected function logResponse(int $logId, Response $response, float $startTime): void
    {
        $duration = (int) ((microtime(true) - $startTime) * 1000);

        $updateData = [
            'response_status' => $response->status(),
            'response_body' => $response->body(),
            'duration_ms' => $duration,
            'is_successful' => $response->successful(),
        ];

        if (! $response->successful()) {
            $updateData['error_message'] = "HTTP {$response->status()}: ".substr($response->body(), 0, 500);
        }

        ApiLog::where('id', $logId)->update($updateData);
    }

    /**
     * Update log with error data
     */
    protected function logError(int $logId, \Throwable $e, float $startTime): void
    {
        $duration = (int) ((microtime(true) - $startTime) * 1000);

        ApiLog::where('id', $logId)->update([
            'duration_ms' => $duration,
            'is_successful' => false,
            'error_message' => $e->getMessage(),
        ]);
    }

    /**
     * Sanitize request body for logging (hide sensitive data, handle files)
     */
    protected function sanitizeRequestBody($body): array|string
    {
        if ($body === null) {
            return '';
        }

        if ($body instanceof \Saloon\Data\MultipartValue) {
            return ['_multipart' => 'file upload'];
        }

        if (is_object($body) && method_exists($body, 'all')) {
            $data = $body->all();

            // Check if it's a multipart body
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if ($value instanceof \Saloon\Data\MultipartValue) {
                        $data[$key] = '[FILE: '.$value->name.']';
                    }
                }
            }

            return $data;
        }

        if (is_string($body)) {
            return $body;
        }

        return (array) $body;
    }
}

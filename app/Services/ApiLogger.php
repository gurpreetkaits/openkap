<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApiLogger
{
    protected ?User $user = null;

    protected ?string $correlationId = null;

    protected array $context = [];

    protected string $service = 'unknown';

    protected bool $enabled = true;

    protected array $httpOptions = [];

    protected array $headers = [];

    protected ?int $timeout = null;

    /**
     * Set the user making the API request
     */
    public function forUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the service name (e.g., 'polar', 'stripe', 'openai')
     */
    public function forService(string $service): self
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Set correlation ID for tracing related requests
     */
    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Add additional context to the log
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Disable logging for this request
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Set request timeout
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set request headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Attach a file to the request
     */
    public function attach(string $name, string $contents, string $filename): self
    {
        $this->httpOptions['multipart'][] = [
            'name' => $name,
            'contents' => $contents,
            'filename' => $filename,
        ];

        return $this;
    }

    /**
     * Create HTTP client instance with current settings
     */
    protected function createHttpClient()
    {
        $client = Http::withHeaders($this->headers);

        if ($this->timeout) {
            $client = $client->timeout($this->timeout);
        }

        return $client;
    }

    /**
     * Make a POST request with logging
     */
    public function post(string $url, array $data = []): Response
    {
        return $this->sendRequest('POST', $url, $data);
    }

    /**
     * Make a GET request with logging
     */
    public function get(string $url, array $query = []): Response
    {
        return $this->sendRequest('GET', $url, $query);
    }

    /**
     * Make a PUT request with logging
     */
    public function put(string $url, array $data = []): Response
    {
        return $this->sendRequest('PUT', $url, $data);
    }

    /**
     * Make a DELETE request with logging
     */
    public function delete(string $url, array $data = []): Response
    {
        return $this->sendRequest('DELETE', $url, $data);
    }

    /**
     * Send request with logging (request first, then response)
     */
    protected function sendRequest(string $method, string $url, array $data = []): Response
    {
        $correlationId = $this->correlationId ?? Str::uuid()->toString();
        $startTime = microtime(true);
        $logId = null;

        // Step 1: Log the request immediately
        if ($this->enabled) {
            $logId = $this->logRequestStart($method, $url, $data, $correlationId);
        }

        // Step 2: Make the request
        try {
            $client = $this->createHttpClient();

            // Handle multipart (file upload)
            if (! empty($this->httpOptions['multipart'])) {
                $client = $client->asMultipart();
                foreach ($this->httpOptions['multipart'] as $part) {
                    $client = $client->attach($part['name'], $part['contents'], $part['filename']);
                }
            }

            $response = match (strtoupper($method)) {
                'GET' => $client->get($url, $data),
                'POST' => empty($this->httpOptions['multipart']) ? $client->post($url, $data) : $client->post($url, $data),
                'PUT' => $client->put($url, $data),
                'DELETE' => $client->delete($url, $data),
                default => $client->post($url, $data),
            };

            // Step 3: Update log with response
            if ($this->enabled && $logId) {
                $this->logRequestComplete($logId, $response, $startTime);
            }

            return $response;

        } catch (\Throwable $e) {
            // Log the error
            if ($this->enabled && $logId) {
                $this->logRequestError($logId, $e, $startTime);
            }

            throw $e;
        }
    }

    /**
     * Log the request start (before sending)
     */
    protected function logRequestStart(string $method, string $url, array $data, string $correlationId): int
    {
        $parsedUrl = parse_url($url);
        $endpoint = ($parsedUrl['path'] ?? '').(isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '');

        // Don't log file contents, just metadata
        $requestBody = $data;
        if (! empty($this->httpOptions['multipart'])) {
            $requestBody = ['_multipart' => 'file upload', 'fields' => array_keys($data)];
        }

        $log = ApiLog::create([
            'service' => $this->service,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'request_body' => is_array($requestBody) ? json_encode($requestBody) : (string) $requestBody,
            'user_id' => $this->user?->id,
            'correlation_id' => $correlationId,
            'context' => $this->context,
            // is_successful defaults to false, will be updated to true on success
        ]);

        return $log->id;
    }

    /**
     * Update the log with response data
     */
    protected function logRequestComplete(int $logId, Response $response, float $startTime): void
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
     * Update the log with error data
     */
    protected function logRequestError(int $logId, \Throwable $e, float $startTime): void
    {
        $duration = (int) ((microtime(true) - $startTime) * 1000);

        ApiLog::where('id', $logId)->update([
            'duration_ms' => $duration,
            'is_successful' => false,
            'error_message' => $e->getMessage(),
        ]);
    }

    /**
     * Log an API request manually (legacy method)
     */
    public function log(
        string $method,
        string $url,
        array $headers = [],
        $body = null,
        ?Response $response = null,
        ?\Throwable $exception = null
    ): ?ApiLog {
        if (! $this->enabled) {
            return null;
        }

        $startTime = microtime(true);
        $parsedUrl = parse_url($url);
        $endpoint = ($parsedUrl['path'] ?? '').(isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '');

        $logData = [
            'service' => $this->service,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'request_body' => is_array($body) ? json_encode($body) : (string) $body,
            'user_id' => $this->user?->id,
            'correlation_id' => $this->correlationId ?? Str::uuid()->toString(),
            'context' => $this->context,
        ];

        if ($response) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $logData['response_status'] = $response->status();
            $logData['response_body'] = $response->body();
            $logData['duration_ms'] = $duration;
            $logData['is_successful'] = $response->successful();

            if (! $response->successful()) {
                $logData['error_message'] = "HTTP {$response->status()}";
            }
        }

        if ($exception) {
            $logData['is_successful'] = false;
            $logData['error_message'] = $exception->getMessage();
        }

        return ApiLog::create($logData);
    }

    /**
     * Create a new instance
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * Legacy method - returns $this for chaining, actual request made via post/get methods
     */
    public function http(): self
    {
        return $this;
    }
}

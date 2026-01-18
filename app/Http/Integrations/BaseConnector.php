<?php

namespace App\Http\Integrations;

use App\Http\Integrations\Concerns\LogsApiRequests;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

abstract class BaseConnector extends Connector
{
    use LogsApiRequests;

    /**
     * Boot the connector and set up logging middleware
     */
    public function boot(PendingRequest $pendingRequest): void
    {
        // Store start time in the request config for later use
        $startTime = microtime(true);
        $logId = $this->logRequest($pendingRequest);

        $pendingRequest->config()->add('_log_id', $logId);
        $pendingRequest->config()->add('_log_start_time', $startTime);

        // Add response middleware to log the response
        $pendingRequest->middleware()->onResponse(function (Response $response) use ($pendingRequest) {
            $logId = $pendingRequest->config()->get('_log_id');
            $startTime = $pendingRequest->config()->get('_log_start_time');

            if ($logId && $startTime) {
                $this->logResponse($logId, $response, $startTime);
            }

            return $response;
        });
    }

    /**
     * Send a request and handle exceptions for logging
     *
     * This overrides the parent send method to ensure connection exceptions
     * (timeouts, network errors, etc.) are still logged to api_logs
     */
    public function send(Request $request, ...$args): Response
    {
        try {
            return parent::send($request, ...$args);
        } catch (\Throwable $e) {
            // Log the exception if we have a log ID stored
            // The logId is set during boot() before the request is sent
            $this->logConnectionException($e);

            throw $e;
        }
    }

    /**
     * Log connection exceptions that occur before getting a response
     */
    protected function logConnectionException(\Throwable $e): void
    {
        // We need to find the most recent log entry for this connector
        // since the exception happens after boot() but before response middleware
        $this->logExceptionByCorrelationId($e);
    }

    /**
     * Default headers for all requests
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}

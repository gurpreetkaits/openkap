<?php

namespace App\Http\Integrations;

use App\Http\Integrations\Concerns\LogsApiRequests;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
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
     * Default headers for all requests
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}

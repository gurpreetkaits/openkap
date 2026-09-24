<?php

namespace App\Managers;

use App\Data\ExtensionErrorData;
use App\Repositories\ExtensionErrorRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Ingests error reports sent by the browser extension.
 *
 * Extension failures are otherwise invisible — they happen in a service
 * worker or an offscreen document that nobody has a devtools window open
 * on. Storing them makes the failure rate something you can look at.
 */
class ExtensionErrorManager
{
    /** Reject absurd batches outright. */
    public const MAX_BATCH = 50;

    public function __construct(
        protected ExtensionErrorRepository $errors
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $reports
     * @return int Number of rows stored.
     */
    public function record(array $reports, ?int $userId): int
    {
        $rows = [];
        $now = now();

        foreach (array_slice($reports, 0, self::MAX_BATCH) as $report) {
            if (! is_array($report)) {
                continue;
            }

            $data = ExtensionErrorData::fromClient($report);

            if ($data->message === '') {
                continue;
            }

            $rows[] = [
                'user_id' => $userId,
                'extension_version' => $data->extension_version,
                'browser' => $data->browser,
                'platform' => $data->platform,
                'context' => $data->context,
                'code' => $data->code,
                'message' => $data->message,
                'stack' => $data->stack,
                'session_id' => $data->session_id,
                'detail' => $data->detail !== null ? json_encode($data->detail) : null,
                'occurred_at' => $this->parseTimestamp($data->occurred_at),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->forwardToSentry($data, $userId);
        }

        if ($rows === []) {
            return 0;
        }

        $this->errors->insertMany($rows);

        Log::channel('daily')->info('Extension errors received', [
            'user_id' => $userId,
            'count' => count($rows),
            'codes' => array_values(array_unique(array_column($rows, 'code'))),
        ]);

        return count($rows);
    }

    /**
     * @return array{recent: array<int, mixed>, by_code: array<int, mixed>}
     */
    public function triageReport(int $sinceDays = 7, int $limit = 100): array
    {
        return [
            'by_code' => $this->errors->groupedByCode($sinceDays)->all(),
            'recent' => $this->errors->recent($limit)->map(fn ($error) => [
                'id' => $error->id,
                'user_id' => $error->user_id,
                'user_email' => $error->user?->email,
                'context' => $error->context,
                'code' => $error->code,
                'message' => $error->message,
                'session_id' => $error->session_id,
                'extension_version' => $error->extension_version,
                'browser' => $error->browser,
                'platform' => $error->platform,
                'detail' => $error->detail,
                'occurred_at' => $error->occurred_at?->toISOString(),
                'created_at' => $error->created_at?->toISOString(),
            ])->all(),
        ];
    }

    private function parseTimestamp(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function forwardToSentry(ExtensionErrorData $data, ?int $userId): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        try {
            \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($data, $userId) {
                $scope->setTag('source', 'extension');
                $scope->setTag('extension.context', $data->context);
                $scope->setTag('extension.code', $data->code);

                if ($data->extension_version !== null) {
                    $scope->setTag('extension.version', $data->extension_version);
                }

                $scope->setContext('extension', array_filter([
                    'session_id' => $data->session_id,
                    'browser' => $data->browser,
                    'platform' => $data->platform,
                    'stack' => $data->stack,
                    'detail' => $data->detail,
                    'user_id' => $userId,
                ], fn ($value) => $value !== null));

                \Sentry\captureMessage(
                    "[extension:{$data->context}] {$data->code}: {$data->message}",
                    \Sentry\Severity::error()
                );
            });
        } catch (\Throwable $e) {
            // Telemetry must never break the endpoint that receives it.
            Log::warning('Failed forwarding extension error to Sentry', ['error' => $e->getMessage()]);
        }
    }
}

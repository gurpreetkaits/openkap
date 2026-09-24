<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ExtensionErrorData extends Data
{
    public function __construct(
        public string $context,

        public string $code,

        public string $message,

        public ?string $stack,

        public ?string $session_id,

        public ?string $extension_version,

        public ?string $browser,

        public ?string $platform,

        /** Breadcrumbs and any extra context the extension attached. */
        public ?array $detail,

        public ?string $occurred_at,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromClient(array $payload): self
    {
        return new self(
            context: self::clamp($payload['context'] ?? 'unknown', 32),
            code: self::clamp($payload['code'] ?? 'ERR_UNKNOWN', 64),
            message: self::clamp($payload['message'] ?? '', 2000),
            stack: isset($payload['stack']) ? self::clamp((string) $payload['stack'], 8000) : null,
            session_id: self::validSessionId($payload['session_id'] ?? null),
            extension_version: isset($payload['extension_version']) ? self::clamp((string) $payload['extension_version'], 32) : null,
            browser: isset($payload['browser']) ? self::clamp((string) $payload['browser'], 64) : null,
            platform: isset($payload['platform']) ? self::clamp((string) $payload['platform'], 64) : null,
            detail: is_array($payload['detail'] ?? null) ? $payload['detail'] : null,
            occurred_at: isset($payload['occurred_at']) ? (string) $payload['occurred_at'] : null,
        );
    }

    private static function clamp(mixed $value, int $length): string
    {
        return mb_substr(is_scalar($value) ? (string) $value : '', 0, $length);
    }

    private static function validSessionId(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $value) === 1
            ? $value
            : null;
    }
}

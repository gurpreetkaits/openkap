<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A stream upload session could not be used as requested.
 *
 * Carries the HTTP status and machine-readable error code the controller
 * should surface, so the manager stays free of HTTP concerns while the API
 * contract the extension depends on is preserved exactly.
 */
class StreamSessionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 404,
        public readonly ?string $errorCode = null,
        public readonly array $extra = [],
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('Invalid session', 404);
    }

    public static function forbidden(): self
    {
        return new self('Unauthorized', 403);
    }

    public static function empty(): self
    {
        return new self('No video data received', 400, 'no_video_data');
    }

    public static function completionInProgress(): self
    {
        return new self('This recording is already being finalised', 409, 'completion_in_progress');
    }

    public static function rejected(string $message, string $code, array $extra = []): self
    {
        return new self($message, 422, $code, $extra);
    }

    public static function limitReached(string $message, array $extra = []): self
    {
        return new self($message, 403, 'video_limit_reached', $extra);
    }
}

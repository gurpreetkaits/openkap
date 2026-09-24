<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a client asks to finalise a recording but the server does not
 * hold every chunk the client says it produced.
 *
 * Completing anyway is what used to silently truncate recordings: the video
 * was built from whatever had arrived and returned 201. Refusing here, and
 * handing back the exact missing indexes, lets the extension re-upload from
 * its local queue and try again.
 */
class MissingChunksException extends RuntimeException
{
    /**
     * @param  list<int>  $missing
     */
    public function __construct(
        public readonly array $missing,
        public readonly int $expectedChunks,
        public readonly int $receivedChunks,
        public readonly string $type = 'video',
    ) {
        $shown = array_slice($missing, 0, 10);
        $suffix = count($missing) > 10 ? ', …' : '';

        parent::__construct(sprintf(
            'Missing %d of %d %s chunks: [%s%s]',
            count($missing),
            $expectedChunks,
            $type,
            implode(', ', $shown),
            $suffix
        ));
    }
}

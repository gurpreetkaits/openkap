<?php

namespace App\Services;

use RuntimeException;

/**
 * Race-free storage for chunked recording uploads.
 *
 * Every chunk is written to its own immutable file. There is no shared
 * mutable state (no append target, no counter file), so concurrent chunk
 * requests — which are guaranteed under mod_php, since the extension
 * uploads several chunks in parallel — cannot lose each other's writes.
 *
 * Invariants this class guarantees:
 *   1. A chunk write is atomic: bytes land in a unique temp file and are
 *      then rename()d into place. A reader never observes a partial chunk.
 *   2. Writing chunk N never reads or mutates any state belonging to
 *      chunk M. Concurrency is therefore safe by construction, not by lock.
 *   3. Re-uploading an index is idempotent — the last writer wins and the
 *      result is a complete, valid chunk either way.
 *   4. The set of received chunks is derived from the filesystem, so it
 *      cannot drift from reality the way a counter in JSON can.
 *
 * Deliberately free of framework dependencies so it can be exercised from
 * plain PHP subprocesses in the concurrency test.
 */
class ChunkStorageService
{
    public const TYPE_VIDEO = 'video';

    public const TYPE_CAMERA = 'camera';

    /** Hard ceiling on chunk index — a 3s chunk interval makes this ~83 hours. */
    public const MAX_CHUNK_INDEX = 100000;

    public function __construct(
        private readonly string $baseDir
    ) {}

    public function baseDir(): string
    {
        return $this->baseDir;
    }

    public function sessionDir(string $sessionId): string
    {
        return "{$this->baseDir}/{$sessionId}";
    }

    public function sessionExists(string $sessionId): bool
    {
        return is_file($this->sessionDir($sessionId).'/session.json')
            || is_file($this->sessionDir($sessionId).'/metadata.json');
    }

    /**
     * Create the session directory and write its immutable descriptor.
     *
     * session.json is written once and never modified again. Everything
     * that used to mutate metadata.json (counters, pending maps, the
     * next-expected cursor) is now derived from the chunk directory.
     */
    public function createSession(string $sessionId, array $descriptor): void
    {
        $dir = $this->sessionDir($sessionId);

        $this->ensureDir($dir);
        $this->ensureDir("{$dir}/chunks/".self::TYPE_VIDEO);
        $this->ensureDir("{$dir}/chunks/".self::TYPE_CAMERA);

        $this->atomicWrite("{$dir}/session.json", json_encode($descriptor, JSON_PRETTY_PRINT));
    }

    /** @return array<string, mixed>|null */
    public function readSession(string $sessionId): ?array
    {
        $dir = $this->sessionDir($sessionId);

        foreach (["{$dir}/session.json", "{$dir}/metadata.json"] as $path) {
            if (! is_file($path)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($path), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Store one chunk. Safe to call concurrently for any mix of indexes,
     * including the same index twice.
     *
     * @param  string  $sourcePath  Path to the uploaded temp file.
     */
    public function storeChunk(string $sessionId, int $index, string $sourcePath, string $type = self::TYPE_VIDEO): int
    {
        if ($index < 0 || $index > self::MAX_CHUNK_INDEX) {
            throw new RuntimeException("Chunk index out of range: {$index}");
        }

        $dir = $this->chunkDir($sessionId, $type);
        $this->ensureDir($dir);

        $final = $dir.'/'.$this->chunkFilename($index);
        // Unique temp name: two concurrent uploads of the same index must not
        // share a staging file, or one could truncate the other mid-copy.
        $temp = $final.'.tmp.'.bin2hex(random_bytes(8));

        if (! @copy($sourcePath, $temp)) {
            throw new RuntimeException("Failed to stage chunk {$index} for session {$sessionId}");
        }

        // rename() over an existing file is atomic on POSIX — readers see
        // either the old complete chunk or the new complete chunk.
        if (! @rename($temp, $final)) {
            @unlink($temp);
            throw new RuntimeException("Failed to commit chunk {$index} for session {$sessionId}");
        }

        return (int) filesize($final);
    }

    /**
     * Indexes currently on disk, ascending.
     *
     * @return list<int>
     */
    public function receivedIndexes(string $sessionId, string $type = self::TYPE_VIDEO): array
    {
        $dir = $this->chunkDir($sessionId, $type);

        if (! is_dir($dir)) {
            return [];
        }

        $indexes = [];

        foreach ((array) scandir($dir) as $entry) {
            // .tmp.* staging files are deliberately excluded — an in-flight
            // chunk is not a received chunk.
            if (preg_match('/^(\d{8})\.webm$/', (string) $entry, $m) === 1) {
                $indexes[] = (int) $m[1];
            }
        }

        sort($indexes);

        return $indexes;
    }

    /**
     * Indexes in [0, expectedCount) that have not arrived.
     *
     * @return list<int>
     */
    public function missingIndexes(string $sessionId, int $expectedCount, string $type = self::TYPE_VIDEO): array
    {
        if ($expectedCount <= 0) {
            return [];
        }

        $received = array_flip($this->receivedIndexes($sessionId, $type));
        $missing = [];

        for ($i = 0; $i < $expectedCount; $i++) {
            if (! isset($received[$i])) {
                $missing[] = $i;
            }
        }

        return $missing;
    }

    public function totalSize(string $sessionId, string $type = self::TYPE_VIDEO): int
    {
        $dir = $this->chunkDir($sessionId, $type);
        $total = 0;

        foreach ($this->receivedIndexes($sessionId, $type) as $index) {
            $total += (int) filesize($dir.'/'.$this->chunkFilename($index));
        }

        return $total;
    }

    /**
     * Bytes that could still be turned into a video for this session.
     *
     * Counts per-chunk files, a legacy pre-appended video.webm, and legacy
     * pending_* files. That last part matters: the old append-in-place code
     * routinely left a 0-byte video.webm next to tens of megabytes of
     * stranded pending chunks, and treating those sessions as empty would
     * delete a recording that is fully recoverable.
     */
    public function recoverableBytes(string $sessionId): int
    {
        $total = $this->totalSize($sessionId) + $this->totalSize($sessionId, self::TYPE_CAMERA);

        $dir = $this->sessionDir($sessionId);

        foreach (['video.webm', 'camera.webm'] as $legacyFile) {
            if (is_file("{$dir}/{$legacyFile}")) {
                $total += (int) filesize("{$dir}/{$legacyFile}");
            }
        }

        foreach ((array) glob("{$dir}/*pending_*.webm") as $path) {
            $total += (int) filesize((string) $path);
        }

        return $total;
    }

    public function lastChunkAt(string $sessionId): ?int
    {
        $newest = null;

        foreach ([self::TYPE_VIDEO, self::TYPE_CAMERA] as $type) {
            $dir = $this->chunkDir($sessionId, $type);

            foreach ($this->receivedIndexes($sessionId, $type) as $index) {
                $mtime = @filemtime($dir.'/'.$this->chunkFilename($index));

                if ($mtime !== false && ($newest === null || $mtime > $newest)) {
                    $newest = $mtime;
                }
            }
        }

        return $newest;
    }

    /**
     * Concatenate every received chunk, in index order, into one file.
     *
     * Assembly happens exactly once per session, after uploading has
     * finished, so it never races with a chunk write.
     *
     * @return int Bytes written.
     */
    public function assemble(string $sessionId, string $destination, string $type = self::TYPE_VIDEO): int
    {
        $indexes = $this->receivedIndexes($sessionId, $type);

        // Legacy sessions (started before per-chunk storage shipped) have a
        // pre-appended video.webm instead of a chunks/ directory. Honour it so
        // recordings in flight across a deploy still complete.
        if ($indexes === []) {
            return $this->assembleLegacy($sessionId, $destination, $type);
        }

        $this->ensureDir(dirname($destination));

        $out = fopen($destination, 'wb');

        if ($out === false) {
            throw new RuntimeException("Cannot open {$destination} for writing");
        }

        $written = 0;
        $chunkDir = $this->chunkDir($sessionId, $type);

        try {
            foreach ($indexes as $index) {
                $path = $chunkDir.'/'.$this->chunkFilename($index);
                $in = fopen($path, 'rb');

                if ($in === false) {
                    throw new RuntimeException("Cannot read chunk {$index} of session {$sessionId}");
                }

                try {
                    $copied = stream_copy_to_stream($in, $out);

                    if ($copied === false) {
                        throw new RuntimeException("Failed copying chunk {$index} of session {$sessionId}");
                    }

                    $written += $copied;
                } finally {
                    fclose($in);
                }
            }
        } finally {
            fclose($out);
        }

        return $written;
    }

    /**
     * Assemble a session written by the previous append-in-place format.
     */
    private function assembleLegacy(string $sessionId, string $destination, string $type): int
    {
        $dir = $this->sessionDir($sessionId);
        $legacyPath = $type === self::TYPE_CAMERA ? "{$dir}/camera.webm" : "{$dir}/video.webm";

        if (! is_file($legacyPath)) {
            return 0;
        }

        $this->ensureDir(dirname($destination));

        $out = fopen($destination, 'wb');

        if ($out === false) {
            throw new RuntimeException("Cannot open {$destination} for writing");
        }

        $written = 0;

        try {
            $in = fopen($legacyPath, 'rb');

            if ($in !== false) {
                $written += (int) stream_copy_to_stream($in, $out);
                fclose($in);
            }

            // Orphaned pending_* files are appended in index order. Under the
            // old code these were routinely stranded by the metadata race;
            // picking them up here recovers what would have been lost.
            $prefix = $type === self::TYPE_CAMERA ? 'camera_pending_' : 'pending_';
            $pending = [];

            foreach ((array) glob("{$dir}/{$prefix}*.webm") as $path) {
                if (preg_match('/'.preg_quote($prefix, '/').'(\d+)\.webm$/', (string) $path, $m) === 1) {
                    // camera_pending_* also matches the video prefix; skip it.
                    if ($type !== self::TYPE_CAMERA && str_contains(basename((string) $path), 'camera_')) {
                        continue;
                    }

                    $pending[(int) $m[1]] = $path;
                }
            }

            ksort($pending);

            foreach ($pending as $path) {
                $in = fopen($path, 'rb');

                if ($in !== false) {
                    $written += (int) stream_copy_to_stream($in, $out);
                    fclose($in);
                }
            }
        } finally {
            fclose($out);
        }

        return $written;
    }

    /**
     * Serialise completion so two concurrent /complete calls cannot both
     * assemble and both create a Video row.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T|null Null when another request already holds the lock.
     */
    public function withCompletionLock(string $sessionId, callable $callback): mixed
    {
        $dir = $this->sessionDir($sessionId);
        $this->ensureDir($dir);

        $handle = fopen("{$dir}/complete.lock", 'c');

        if ($handle === false) {
            throw new RuntimeException("Cannot open completion lock for session {$sessionId}");
        }

        try {
            if (! flock($handle, LOCK_EX | LOCK_NB)) {
                return null;
            }

            try {
                return $callback();
            } finally {
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }

    public function deleteSession(string $sessionId): void
    {
        $this->deleteTree($this->sessionDir($sessionId));
    }

    /**
     * @return list<string> Session ids currently on disk.
     */
    public function allSessionIds(): array
    {
        if (! is_dir($this->baseDir)) {
            return [];
        }

        $ids = [];

        foreach ((array) scandir($this->baseDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir("{$this->baseDir}/{$entry}")) {
                $ids[] = (string) $entry;
            }
        }

        return $ids;
    }

    public function chunkDir(string $sessionId, string $type = self::TYPE_VIDEO): string
    {
        $type = $type === self::TYPE_CAMERA ? self::TYPE_CAMERA : self::TYPE_VIDEO;

        return $this->sessionDir($sessionId)."/chunks/{$type}";
    }

    private function chunkFilename(int $index): string
    {
        return sprintf('%08d.webm', $index);
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Cannot create directory {$dir}");
        }
    }

    private function atomicWrite(string $path, string $contents): void
    {
        $temp = $path.'.tmp.'.bin2hex(random_bytes(8));

        if (@file_put_contents($temp, $contents) === false || ! @rename($temp, $path)) {
            @unlink($temp);
            throw new RuntimeException("Cannot write {$path}");
        }
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach ((array) scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "{$dir}/{$entry}";

            is_dir($path) ? $this->deleteTree($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}

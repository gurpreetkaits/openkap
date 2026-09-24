<?php

namespace Tests\Unit;

use App\Services\ChunkStorageService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * These tests exist to prove the bug that was silently truncating long
 * recordings cannot come back.
 *
 * The old implementation kept a counter and a pending-chunk map inside
 * metadata.json, read-modify-wrote it on every chunk, and appended chunk
 * bytes into one shared video.webm. Two chunks arriving at once — routine,
 * because the extension uploads in parallel — lost one side of the write.
 * The stranded chunk files were then never appended and never reported.
 *
 * The replacement has no shared mutable state at all, which the concurrency
 * test below exercises with real OS processes rather than a simulation.
 */
class ChunkStorageServiceTest extends TestCase
{
    private string $baseDir;

    private ChunkStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir().'/openkap-chunks-'.bin2hex(random_bytes(6));
        mkdir($this->baseDir, 0755, true);

        $this->storage = new ChunkStorageService($this->baseDir);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->baseDir);

        parent::tearDown();
    }

    #[Test]
    public function it_stores_chunks_and_reports_them_in_index_order(): void
    {
        $session = $this->newSession();

        // Deliberately out of order — arrival order must not matter.
        foreach ([3, 0, 2, 1] as $index) {
            $this->storage->storeChunk($session, $index, $this->tempFile("chunk-{$index}"));
        }

        $this->assertSame([0, 1, 2, 3], $this->storage->receivedIndexes($session));
    }

    #[Test]
    public function it_assembles_chunks_in_index_order_regardless_of_arrival_order(): void
    {
        $session = $this->newSession();

        foreach ([2, 0, 3, 1] as $index) {
            $this->storage->storeChunk($session, $index, $this->tempFile("<{$index}>"));
        }

        $destination = $this->baseDir.'/out.webm';
        $written = $this->storage->assemble($session, $destination);

        $this->assertSame('<0><1><2><3>', file_get_contents($destination));
        $this->assertSame(strlen('<0><1><2><3>'), $written);
    }

    #[Test]
    public function re_uploading_the_same_index_is_idempotent(): void
    {
        $session = $this->newSession();

        $this->storage->storeChunk($session, 0, $this->tempFile('first'));
        $this->storage->storeChunk($session, 1, $this->tempFile('second'));
        // A retry that lands after the original succeeded.
        $this->storage->storeChunk($session, 0, $this->tempFile('first'));

        $this->assertSame([0, 1], $this->storage->receivedIndexes($session));

        $destination = $this->baseDir.'/out.webm';
        $this->storage->assemble($session, $destination);

        $this->assertSame('firstsecond', file_get_contents($destination));
    }

    #[Test]
    public function it_reports_gaps_against_the_expected_count(): void
    {
        $session = $this->newSession();

        foreach ([0, 1, 4] as $index) {
            $this->storage->storeChunk($session, $index, $this->tempFile('x'));
        }

        $this->assertSame([2, 3], $this->storage->missingIndexes($session, 5));
        $this->assertSame([2, 3, 5, 6], $this->storage->missingIndexes($session, 7));
        $this->assertSame([], $this->storage->missingIndexes($session, 2));
        $this->assertSame([], $this->storage->missingIndexes($session, 0));
    }

    #[Test]
    public function staging_files_are_never_counted_as_received(): void
    {
        $session = $this->newSession();
        $this->storage->storeChunk($session, 0, $this->tempFile('done'));

        // Simulate an upload that is mid-copy: a .tmp file is present but has
        // not been renamed into place yet.
        file_put_contents(
            $this->storage->chunkDir($session).'/00000001.webm.tmp.abcdef',
            'half-written'
        );

        $this->assertSame([0], $this->storage->receivedIndexes($session));
        $this->assertSame([1], $this->storage->missingIndexes($session, 2));
    }

    #[Test]
    public function it_rejects_out_of_range_indexes(): void
    {
        $session = $this->newSession();

        $this->expectException(RuntimeException::class);
        $this->storage->storeChunk($session, ChunkStorageService::MAX_CHUNK_INDEX + 1, $this->tempFile('x'));
    }

    #[Test]
    public function video_and_camera_tracks_are_stored_independently(): void
    {
        $session = $this->newSession();

        $this->storage->storeChunk($session, 0, $this->tempFile('V0'), ChunkStorageService::TYPE_VIDEO);
        $this->storage->storeChunk($session, 1, $this->tempFile('V1'), ChunkStorageService::TYPE_VIDEO);
        $this->storage->storeChunk($session, 0, $this->tempFile('C0'), ChunkStorageService::TYPE_CAMERA);

        $this->assertSame([0, 1], $this->storage->receivedIndexes($session, ChunkStorageService::TYPE_VIDEO));
        $this->assertSame([0], $this->storage->receivedIndexes($session, ChunkStorageService::TYPE_CAMERA));

        $this->storage->assemble($session, $this->baseDir.'/v.webm', ChunkStorageService::TYPE_VIDEO);
        $this->storage->assemble($session, $this->baseDir.'/c.webm', ChunkStorageService::TYPE_CAMERA);

        $this->assertSame('V0V1', file_get_contents($this->baseDir.'/v.webm'));
        $this->assertSame('C0', file_get_contents($this->baseDir.'/c.webm'));
    }

    #[Test]
    public function the_completion_lock_admits_only_one_holder(): void
    {
        $session = $this->newSession();
        $inner = 'not-run';

        $outer = $this->storage->withCompletionLock($session, function () use ($session, &$inner) {
            $inner = $this->storage->withCompletionLock($session, fn () => 'second');

            return 'first';
        });

        $this->assertSame('first', $outer);
        $this->assertNull($inner, 'A second concurrent completion must not acquire the lock');

        // The lock is released afterwards.
        $this->assertSame('later', $this->storage->withCompletionLock($session, fn () => 'later'));
    }

    #[Test]
    public function it_assembles_legacy_sessions_and_recovers_stranded_pending_chunks(): void
    {
        // A session in the old on-disk format, including the pending_* files
        // the old metadata race used to strand and drop.
        $session = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $dir = $this->storage->sessionDir($session);
        mkdir($dir, 0755, true);

        file_put_contents("{$dir}/metadata.json", json_encode([
            'user_id' => 7,
            'title' => 'Legacy recording',
            'started_at' => '2026-01-01T00:00:00Z',
        ]));
        file_put_contents("{$dir}/video.webm", 'HEAD');
        file_put_contents("{$dir}/pending_11.webm", '[11]');
        file_put_contents("{$dir}/pending_9.webm", '[9]');
        file_put_contents("{$dir}/camera_pending_1.webm", '[cam1]');

        $this->assertNotNull($this->storage->readSession($session));
        $this->assertTrue($this->storage->sessionExists($session));

        $this->storage->assemble($session, $this->baseDir.'/legacy.webm');

        // Numeric order, not glob order: 9 before 11.
        $this->assertSame('HEAD[9][11]', file_get_contents($this->baseDir.'/legacy.webm'));
    }

    #[Test]
    public function deleting_a_session_removes_the_whole_tree(): void
    {
        $session = $this->newSession();
        $this->storage->storeChunk($session, 0, $this->tempFile('x'));

        $this->storage->deleteSession($session);

        $this->assertDirectoryDoesNotExist($this->storage->sessionDir($session));
        $this->assertFalse($this->storage->sessionExists($session));
    }

    /**
     * The regression test for the truncated-recording bug.
     *
     * Six real OS processes write 60 chunks concurrently, with overlapping
     * retries of the same indexes, exactly as parallel uploads under mod_php
     * would. Every chunk must survive and the assembled bytes must be
     * byte-for-byte the in-order concatenation.
     *
     * Under the old append+metadata design this is precisely the interleaving
     * that stranded chunks and silently shortened the video.
     */
    #[Test]
    public function concurrent_writers_never_lose_a_chunk(): void
    {
        $session = $this->newSession();

        $chunkCount = 60;
        $workers = 6;
        // Indexes every worker also writes, standing in for a retry that
        // lands at the same moment as the original upload.
        $contended = [0, 7, 13, 41, 59];

        $script = $this->baseDir.'/worker.php';
        file_put_contents($script, <<<'WORKER'
<?php
require $argv[1];

use App\Services\ChunkStorageService;

[$autoload, $baseDir, $sessionId, $worker, $workers, $chunkCount, $contended] = [
    $argv[1], $argv[2], $argv[3], (int) $argv[4], (int) $argv[5], (int) $argv[6], array_map('intval', explode(',', $argv[7])),
];

$storage = new ChunkStorageService($baseDir);

$indexes = [];
for ($i = 0; $i < $chunkCount; $i++) {
    if ($i % $workers === $worker) {
        $indexes[] = $i;
    }
}
$indexes = array_merge($indexes, $contended);
shuffle($indexes);

$tmp = sys_get_temp_dir().'/worker-'.$worker.'-'.getmypid();

foreach ($indexes as $index) {
    // Content is a pure function of the index, so a duplicate write from a
    // second worker is byte-identical — last-writer-wins stays deterministic.
    file_put_contents($tmp, str_repeat(chr(65 + ($index % 26)), 512 + $index));
    $storage->storeChunk($sessionId, $index, $tmp);
}

@unlink($tmp);
exit(0);
WORKER);

        $autoload = dirname(__DIR__, 2).'/vendor/autoload.php';
        $processes = [];

        for ($worker = 0; $worker < $workers; $worker++) {
            $cmd = sprintf(
                '%s %s %s %s %s %d %d %d %s',
                escapeshellarg(PHP_BINARY),
                escapeshellarg($script),
                escapeshellarg($autoload),
                escapeshellarg($this->baseDir),
                escapeshellarg($session),
                $worker,
                $workers,
                $chunkCount,
                escapeshellarg(implode(',', $contended))
            );

            $process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

            $this->assertIsResource($process, 'Failed to spawn concurrency worker');

            $processes[] = [$process, $pipes];
        }

        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exit = proc_close($process);

            $this->assertSame(0, $exit, "Worker failed.\nSTDOUT: {$stdout}\nSTDERR: {$stderr}");
        }

        $received = $this->storage->receivedIndexes($session);

        $this->assertSame(
            range(0, $chunkCount - 1),
            $received,
            'Concurrent writers lost or duplicated chunks'
        );

        $expected = '';
        for ($i = 0; $i < $chunkCount; $i++) {
            $expected .= str_repeat(chr(65 + ($i % 26)), 512 + $i);
        }

        $destination = $this->baseDir.'/concurrent.webm';
        $written = $this->storage->assemble($session, $destination);

        $this->assertSame(strlen($expected), $written);
        $this->assertSame(
            $expected,
            file_get_contents($destination),
            'Assembled output is not the exact in-order concatenation'
        );
        $this->assertSame([], $this->storage->missingIndexes($session, $chunkCount));

        // No staging files left behind.
        $this->assertSame(
            [],
            glob($this->storage->chunkDir($session).'/*.tmp.*') ?: []
        );
    }

    private function newSession(): string
    {
        $sessionId = sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );

        $this->storage->createSession($sessionId, [
            'user_id' => 1,
            'title' => 'Test recording',
            'mime_type' => 'video/webm',
            'has_camera' => false,
            'started_at' => '2026-01-01T00:00:00Z',
        ]);

        return $sessionId;
    }

    private function tempFile(string $contents): string
    {
        $path = $this->baseDir.'/upload-'.bin2hex(random_bytes(6));
        file_put_contents($path, $contents);

        return $path;
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

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Services\ChunkStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * If the browser dies mid-recording — crash, closed laptop, killed tab —
 * the chunks that made it to the server must still become a video rather
 * than being swept away.
 */
class StaleUploadSessionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private const WEBM_HEADER = "\x1A\x45\xDF\xA3"
        ."\x9F\x42\x86\x81\x01"
        ."\x42\xF7\x81\x01"
        ."\x42\xF2\x81\x04"
        ."\x42\xF3\x81\x08"
        ."\x42\x82\x84webm";

    protected ChunkStorageService $chunks;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        $this->chunks = app(ChunkStorageService::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->chunks->allSessionIds() as $sessionId) {
            $this->chunks->deleteSession($sessionId);
        }

        parent::tearDown();
    }

    #[Test]
    public function an_abandoned_session_with_chunks_is_recovered_into_a_video(): void
    {
        $user = User::factory()->create();
        $sessionId = $this->seedSession($user->id, chunkCount: 4, ageSeconds: 900);

        $this->artisan('uploads:process-stale', ['--timeout' => 300])
            ->assertSuccessful();

        $this->assertSame(1, Video::count());
        $this->assertSame($user->id, Video::first()->user_id);
        $this->assertFalse($this->chunks->sessionExists($sessionId));
    }

    #[Test]
    public function a_session_still_receiving_chunks_is_left_alone(): void
    {
        $user = User::factory()->create();
        $sessionId = $this->seedSession($user->id, chunkCount: 3, ageSeconds: 5);

        $this->artisan('uploads:process-stale', ['--timeout' => 300])
            ->assertSuccessful();

        $this->assertSame(0, Video::count(), 'A live recording must not be finalised underneath the user');
        $this->assertTrue($this->chunks->sessionExists($sessionId));
    }

    #[Test]
    public function an_empty_abandoned_session_is_cleaned_up(): void
    {
        $user = User::factory()->create();
        $sessionId = $this->seedSession($user->id, chunkCount: 0, ageSeconds: 7200);

        $this->artisan('uploads:process-stale', ['--timeout' => 300, '--cleanup' => 3600])
            ->assertSuccessful();

        $this->assertSame(0, Video::count());
        $this->assertFalse($this->chunks->sessionExists($sessionId));
    }

    #[Test]
    public function a_legacy_format_session_is_still_recoverable(): void
    {
        // Sessions started before per-chunk storage shipped must survive the
        // deploy rather than being lost.
        $user = User::factory()->create();
        $sessionId = '99999999-8888-7777-6666-555555555555';
        $dir = $this->chunks->sessionDir($sessionId);

        mkdir($dir, 0755, true);
        file_put_contents("{$dir}/metadata.json", json_encode([
            'user_id' => $user->id,
            'title' => 'Mid-deploy recording',
            'started_at' => now()->subHour()->toISOString(),
        ]));
        file_put_contents("{$dir}/video.webm", self::WEBM_HEADER.str_repeat("\x00", 512));
        touch("{$dir}/video.webm", time() - 900);

        $this->artisan('uploads:process-stale', ['--timeout' => 300])
            ->assertSuccessful();

        $this->assertSame(1, Video::count());
        $this->assertSame('Mid-deploy recording', Video::first()->title);
    }

    private function seedSession(int $userId, int $chunkCount, int $ageSeconds): string
    {
        $sessionId = sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );

        $this->chunks->createSession($sessionId, [
            'user_id' => $userId,
            'title' => 'Abandoned recording',
            'mime_type' => 'video/webm',
            'has_camera' => false,
            'started_at' => now()->subSeconds($ageSeconds)->toISOString(),
        ]);

        for ($index = 0; $index < $chunkCount; $index++) {
            $payload = $index === 0
                ? self::WEBM_HEADER.str_repeat("\x00", 256)
                : str_repeat('z', 128);

            $temp = sys_get_temp_dir().'/seed-'.bin2hex(random_bytes(4));
            file_put_contents($temp, $payload);
            $this->chunks->storeChunk($sessionId, $index, $temp);
            unlink($temp);

            touch(
                $this->chunks->chunkDir($sessionId).'/'.sprintf('%08d.webm', $index),
                time() - $ageSeconds
            );
        }

        return $sessionId;
    }
}

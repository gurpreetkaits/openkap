<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use App\Services\ChunkStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end coverage of the chunked recording upload API.
 *
 * The behaviour these lock down is the one that used to fail on long
 * recordings: chunks arriving in any order, arriving twice, or not arriving
 * at all must never produce a silently truncated video.
 */
class StreamChunkUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected ChunkStorageService $chunks;

    /** EBML/WebM magic so the assembled file sniffs as video/webm. */
    private const WEBM_HEADER = "\x1A\x45\xDF\xA3"
        ."\x9F\x42\x86\x81\x01"
        ."\x42\xF7\x81\x01"
        ."\x42\xF2\x81\x04"
        ."\x42\xF3\x81\x08"
        ."\x42\x82\x84webm";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        Config::set('services.bunny', [
            'library_id' => '',
            'api_key' => '',
            'cdn_hostname' => '',
        ]);

        $this->user = User::factory()->create();
        $this->chunks = app(ChunkStorageService::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->chunks->allSessionIds() as $sessionId) {
            $this->chunks->deleteSession($sessionId);
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Session lifecycle
    // ---------------------------------------------------------------

    #[Test]
    public function it_starts_an_upload_session(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'My recording']);

        $response->assertOk()
            ->assertJsonStructure(['session_id', 'storage_type', 'will_use_bunny']);

        $sessionId = $response->json('session_id');

        $this->assertTrue($this->chunks->sessionExists($sessionId));
        $this->assertSame($this->user->id, $this->chunks->readSession($sessionId)['user_id']);
    }

    #[Test]
    public function it_accepts_a_chunk_and_reports_a_receipt(): void
    {
        $sessionId = $this->newUploadSession();

        $response = $this->actingAs($this->user)
            ->post("/api/stream/{$sessionId}/chunk", [
                'chunk' => $this->chunkFile('hello'),
                'chunk_index' => 0,
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('chunk_index', 0)
            ->assertJsonPath('chunks_received', 1)
            ->assertJsonPath('type', 'video');

        $this->assertSame([0], $this->chunks->receivedIndexes($sessionId));
    }

    #[Test]
    public function chunks_uploaded_out_of_order_assemble_in_index_order(): void
    {
        $sessionId = $this->newUploadSession();

        // Upload deliberately scrambled — this is the normal case once
        // retries and parallel uploads are in play.
        foreach ([2, 0, 4, 1, 3] as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $this->completeSession($sessionId, expectedChunks: 5)->assertCreated();

        $this->assertSame($this->expectedBytes(5), $this->storedVideoBytes());
    }

    #[Test]
    public function a_duplicate_chunk_upload_does_not_duplicate_video_data(): void
    {
        $sessionId = $this->newUploadSession();

        $this->uploadChunk($sessionId, 0);
        $this->uploadChunk($sessionId, 1);
        // Retry of chunk 0 that lands after the original already succeeded.
        $this->uploadChunk($sessionId, 0);
        $this->uploadChunk($sessionId, 2);

        $this->completeSession($sessionId, expectedChunks: 3)->assertCreated();

        $this->assertSame($this->expectedBytes(3), $this->storedVideoBytes());
    }

    #[Test]
    public function the_assembled_video_is_byte_identical_to_the_recorded_chunks(): void
    {
        $sessionId = $this->newUploadSession();

        $indexes = range(0, 39);
        shuffle($indexes);

        foreach ($indexes as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $this->completeSession($sessionId, expectedChunks: 40)->assertCreated();

        $this->assertSame($this->expectedBytes(40), $this->storedVideoBytes());
    }

    // ---------------------------------------------------------------
    // Gap detection — the behaviour that replaces silent truncation
    // ---------------------------------------------------------------

    #[Test]
    public function completing_with_a_missing_chunk_is_refused_with_the_missing_indexes(): void
    {
        $sessionId = $this->newUploadSession();

        foreach ([0, 1, 2, 4, 5] as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $response = $this->completeSession($sessionId, expectedChunks: 6);

        $response->assertStatus(409)
            ->assertJsonPath('error', 'missing_chunks')
            ->assertJsonPath('missing', [3])
            ->assertJsonPath('expected_chunks', 6)
            ->assertJsonPath('received_chunks', 5);

        $this->assertSame(0, Video::count(), 'A partial recording must not become a video');
    }

    #[Test]
    public function a_refused_completion_keeps_the_session_so_the_client_can_retry(): void
    {
        $sessionId = $this->newUploadSession();

        foreach ([0, 1, 3] as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $this->completeSession($sessionId, expectedChunks: 4)->assertStatus(409);

        // Nothing was thrown away: the chunks that did arrive are still there.
        $this->assertTrue($this->chunks->sessionExists($sessionId));
        $this->assertSame([0, 1, 3], $this->chunks->receivedIndexes($sessionId));

        // The extension re-uploads the gap from its local queue and finishes.
        $this->uploadChunk($sessionId, 2);
        $this->completeSession($sessionId, expectedChunks: 4)->assertCreated();

        $this->assertSame($this->expectedBytes(4), $this->storedVideoBytes());
        $this->assertFalse($this->chunks->sessionExists($sessionId));
    }

    #[Test]
    public function a_trailing_chunk_that_never_arrived_is_detected(): void
    {
        $sessionId = $this->newUploadSession();

        // The classic long-recording failure: the tail goes missing.
        foreach (range(0, 37) as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $this->completeSession($sessionId, expectedChunks: 40)
            ->assertStatus(409)
            ->assertJsonPath('missing', [38, 39]);
    }

    #[Test]
    public function completing_without_an_expected_count_still_works(): void
    {
        // Backwards compatibility: an older extension build does not send
        // expected_chunks, and must still be able to save a recording.
        $sessionId = $this->newUploadSession();

        foreach ([0, 1, 2] as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $this->actingAs($this->user)
            ->postJson("/api/stream/{$sessionId}/complete", ['duration' => 12])
            ->assertCreated();

        $this->assertSame($this->expectedBytes(3), $this->storedVideoBytes());
    }

    #[Test]
    public function completing_a_session_with_no_chunks_is_rejected(): void
    {
        $sessionId = $this->newUploadSession();

        $this->actingAs($this->user)
            ->postJson("/api/stream/{$sessionId}/complete", ['duration' => 5])
            ->assertStatus(400)
            ->assertJsonPath('error', 'no_video_data');

        $this->assertSame(0, Video::count());
    }

    // ---------------------------------------------------------------
    // Plan limits — a finished upload is never thrown away
    // ---------------------------------------------------------------

    #[Test]
    public function a_recording_slightly_over_the_plan_limit_is_still_saved(): void
    {
        // What happened in production: a free user recorded to the 5-minute
        // cap, all 100 chunks uploaded, and the save was refused for being
        // one second over. The recording was deleted.
        $limit = $this->user->getMaxRecordingSeconds();

        $sessionId = $this->newUploadSession();

        foreach ([0, 1, 2] as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $this->completeSession($sessionId, expectedChunks: 3, duration: $limit + 1)
            ->assertCreated();

        $this->assertSame(1, Video::count(), 'an uploaded recording must never be discarded for length');
        $this->assertSame($limit + 1, Video::first()->duration);
    }

    #[Test]
    public function a_recording_well_over_the_plan_limit_is_still_saved(): void
    {
        // Even when a client fails to stop itself, the user's work is kept.
        // The monthly minutes cap is enforced downstream from a probed
        // duration, so nothing here is load-bearing for billing.
        $limit = $this->user->getMaxRecordingSeconds();

        $sessionId = $this->newUploadSession();
        $this->uploadChunk($sessionId, 0);

        $this->completeSession($sessionId, expectedChunks: 1, duration: $limit * 4)
            ->assertCreated();

        $this->assertSame(1, Video::count());
    }

    #[Test]
    public function a_recording_exactly_at_the_plan_limit_is_saved(): void
    {
        $limit = $this->user->getMaxRecordingSeconds();

        $sessionId = $this->newUploadSession();
        $this->uploadChunk($sessionId, 0);

        $this->completeSession($sessionId, expectedChunks: 1, duration: $limit)
            ->assertCreated();

        $this->assertSame($limit, Video::first()->duration);
    }

    #[Test]
    public function a_recording_below_the_minimum_is_still_rejected(): void
    {
        // Unchanged behaviour: an accidental sub-second recording is not
        // worth keeping, and the user has not invested an upload in it.
        Setting::updateOrCreate(
            ['key' => 'min_recording_duration_limit'],
            ['value' => '5']
        );

        $this->assertSame(5, $this->user->getMinRecordingSeconds());

        $sessionId = $this->newUploadSession();
        $this->uploadChunk($sessionId, 0);

        $this->completeSession($sessionId, expectedChunks: 1, duration: 2)
            ->assertStatus(422)
            ->assertJsonPath('error', 'duration_too_short');

        $this->assertSame(0, Video::count());
    }

    // ---------------------------------------------------------------
    // Status reconciliation
    // ---------------------------------------------------------------

    #[Test]
    public function status_reports_exactly_which_chunks_the_server_holds(): void
    {
        $sessionId = $this->newUploadSession();

        foreach ([0, 1, 3] as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $this->actingAs($this->user)
            ->getJson("/api/stream/{$sessionId}/status?expected_chunks=5")
            ->assertOk()
            ->assertJsonPath('chunks_received', 3)
            ->assertJsonPath('received_indexes', [0, 1, 3])
            ->assertJsonPath('missing_indexes', [2, 4]);
    }

    #[Test]
    public function status_omits_missing_indexes_when_no_expectation_is_given(): void
    {
        $sessionId = $this->newUploadSession();
        $this->uploadChunk($sessionId, 0);

        $this->actingAs($this->user)
            ->getJson("/api/stream/{$sessionId}/status")
            ->assertOk()
            ->assertJsonPath('received_indexes', [0])
            ->assertJsonPath('missing_indexes', null);
    }

    // ---------------------------------------------------------------
    // Ownership and validation
    // ---------------------------------------------------------------

    #[Test]
    public function another_user_cannot_touch_someone_elses_session(): void
    {
        $sessionId = $this->newUploadSession();
        $this->uploadChunk($sessionId, 0);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post("/api/stream/{$sessionId}/chunk", [
                'chunk' => $this->chunkFile('evil'),
                'chunk_index' => 1,
            ], ['Accept' => 'application/json'])->assertStatus(403);

        $this->actingAs($intruder)
            ->getJson("/api/stream/{$sessionId}/status")
            ->assertStatus(403);

        $this->actingAs($intruder)
            ->postJson("/api/stream/{$sessionId}/complete", ['duration' => 5])
            ->assertStatus(403);

        $this->actingAs($intruder)
            ->postJson("/api/stream/{$sessionId}/cancel")
            ->assertStatus(403);

        // The owner's data is untouched.
        $this->assertSame([0], $this->chunks->receivedIndexes($sessionId));
    }

    #[Test]
    public function an_unknown_session_is_a_404(): void
    {
        $unknown = '11111111-2222-3333-4444-555555555555';

        $this->actingAs($this->user)
            ->getJson("/api/stream/{$unknown}/status")
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->postJson("/api/stream/{$unknown}/complete", ['duration' => 5])
            ->assertStatus(404);
    }

    #[Test]
    public function a_malformed_session_id_cannot_escape_the_upload_directory(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/stream/'.urlencode('../../../etc').'/status')
            ->assertStatus(404);
    }

    #[Test]
    public function an_out_of_range_chunk_index_is_rejected(): void
    {
        $sessionId = $this->newUploadSession();

        $this->actingAs($this->user)
            ->post("/api/stream/{$sessionId}/chunk", [
                'chunk' => $this->chunkFile('x'),
                'chunk_index' => ChunkStorageService::MAX_CHUNK_INDEX + 1,
            ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    #[Test]
    public function cancelling_removes_the_session(): void
    {
        $sessionId = $this->newUploadSession();
        $this->uploadChunk($sessionId, 0);

        $this->actingAs($this->user)
            ->postJson("/api/stream/{$sessionId}/cancel")
            ->assertOk();

        $this->assertFalse($this->chunks->sessionExists($sessionId));
    }

    #[Test]
    public function a_completed_recording_becomes_a_video_owned_by_the_recorder(): void
    {
        $sessionId = $this->newUploadSession('Weekly update');

        foreach ([0, 1, 2] as $index) {
            $this->uploadChunk($sessionId, $index);
        }

        $response = $this->completeSession($sessionId, expectedChunks: 3, duration: 42);

        $response->assertCreated()
            ->assertJsonPath('video.title', 'Weekly update')
            ->assertJsonPath('video.duration', 42);

        $video = Video::first();

        $this->assertSame($this->user->id, $video->user_id);
        $this->assertSame(42, $video->duration);
        $this->assertNotNull($video->getFirstMedia('videos'));
        $this->assertSame(1, $this->user->fresh()->videos_count);
        $this->assertFalse($this->chunks->sessionExists($sessionId), 'A completed session is cleaned up');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function newUploadSession(string $title = 'Test recording'): string
    {
        return $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => $title])
            ->json('session_id');
    }

    /**
     * Content for chunk N. Chunk 0 carries the WebM header so the assembled
     * file passes the media library's MIME check, exactly like a real
     * MediaRecorder stream.
     */
    private function chunkBody(int $index): string
    {
        return $index === 0
            ? self::WEBM_HEADER.str_repeat("\x00", 256)
            : str_repeat(chr(65 + ($index % 26)), 128 + $index);
    }

    private function expectedBytes(int $count): string
    {
        $bytes = '';

        for ($i = 0; $i < $count; $i++) {
            $bytes .= $this->chunkBody($i);
        }

        return $bytes;
    }

    private function chunkFile(string $contents, string $name = 'chunk.webm'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    private function uploadChunk(string $sessionId, int $index): void
    {
        $this->actingAs($this->user)
            ->post("/api/stream/{$sessionId}/chunk", [
                'chunk' => $this->chunkFile($this->chunkBody($index), "chunk_{$index}.webm"),
                'chunk_index' => $index,
            ], ['Accept' => 'application/json'])->assertOk();
    }

    private function completeSession(string $sessionId, ?int $expectedChunks = null, int $duration = 30)
    {
        $payload = ['duration' => $duration];

        if ($expectedChunks !== null) {
            $payload['expected_chunks'] = $expectedChunks;
        }

        return $this->actingAs($this->user)
            ->postJson("/api/stream/{$sessionId}/complete", $payload);
    }

    private function storedVideoBytes(): string
    {
        $media = Video::firstOrFail()->getFirstMedia('videos');

        $this->assertNotNull($media, 'No video media was attached');

        return Storage::disk('public')->get($media->getPathRelativeToRoot());
    }
}

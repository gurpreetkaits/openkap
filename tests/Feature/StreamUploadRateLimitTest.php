<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ChunkStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Rate limiting must never be the reason a recording fails to upload.
 *
 * Before named limiters, the global API throttle and every per-route
 * numeric throttle shared one counter keyed on the user id. Ordinary app
 * traffic therefore consumed the recording budget, and re-sending a gap of
 * chunks after a network blip could 429 itself into a dead recording.
 */
class StreamUploadRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        foreach (app(ChunkStorageService::class)->allSessionIds() as $sessionId) {
            app(ChunkStorageService::class)->deleteSession($sessionId);
        }

        parent::tearDown();
    }

    #[Test]
    public function ordinary_api_traffic_does_not_consume_the_recording_budget(): void
    {
        // Burn through a chunk of the general API allowance first.
        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($this->user)->getJson('/api/user')->assertOk();
        }

        $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'Still allowed'])
            ->assertOk();
    }

    #[Test]
    public function a_burst_of_chunk_uploads_is_not_rate_limited(): void
    {
        $sessionId = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'Long recording'])
            ->json('session_id');

        // 150 chunks back to back — more than the old global 120/min ceiling,
        // and the shape of a client re-sending a gap after reconnecting.
        for ($index = 0; $index < 150; $index++) {
            $this->actingAs($this->user)
                ->post("/api/stream/{$sessionId}/chunk", [
                    'chunk' => UploadedFile::fake()->createWithContent("c{$index}.webm", 'x'),
                    'chunk_index' => $index,
                ], ['Accept' => 'application/json'])
                ->assertOk();
        }

        $this->assertCount(150, app(ChunkStorageService::class)->receivedIndexes($sessionId));
    }

    #[Test]
    public function status_polling_during_reconciliation_is_not_rate_limited(): void
    {
        $sessionId = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'Reconciling'])
            ->json('session_id');

        for ($i = 0; $i < 40; $i++) {
            $this->actingAs($this->user)
                ->getJson("/api/stream/{$sessionId}/status")
                ->assertOk();
        }
    }
}

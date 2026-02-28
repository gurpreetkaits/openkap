<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BunnyWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Bunny disabled - encoding costs too high');

        $this->user = User::factory()->create();
    }

    // ==========================================
    // WEBHOOK VALIDATION TESTS
    // ==========================================

    #[Test]
    public function webhook_requires_video_guid(): void
    {
        $response = $this->postJson('/api/webhooks/bunny', [
            'Status' => 4,
        ]);

        $response->assertStatus(400)
            ->assertSee('Invalid payload');
    }

    #[Test]
    public function webhook_requires_status(): void
    {
        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-guid-123',
        ]);

        $response->assertStatus(400)
            ->assertSee('Invalid payload');
    }

    #[Test]
    public function webhook_returns_200_for_unknown_video(): void
    {
        // Bunny might send webhooks for videos we don't track
        // We should acknowledge them to prevent retries
        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'unknown-video-guid',
            'Status' => 4,
        ]);

        $response->assertStatus(200)
            ->assertSee('Video not found');
    }

    // ==========================================
    // STATUS UPDATE TESTS
    // ==========================================

    #[Test]
    public function webhook_updates_video_status_to_uploaded(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 1, // uploaded
        ]);

        $response->assertStatus(200)
            ->assertSee('OK');

        $video->refresh();
        $this->assertEquals('uploaded', $video->bunny_status);
    }

    #[Test]
    public function webhook_updates_video_status_to_processing(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
            'bunny_status' => 'uploaded',
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 2, // processing
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('processing', $video->bunny_status);
    }

    #[Test]
    public function webhook_updates_video_status_to_transcoding(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 3, // transcoding
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('transcoding', $video->bunny_status);
    }

    #[Test]
    public function webhook_updates_video_status_to_ready_with_metadata(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
            'duration' => 0,
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 4, // finished/ready
            'Length' => 120,
            'Width' => 1920,
            'Height' => 1080,
            'StorageSize' => 50000000,
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('ready', $video->bunny_status);
        $this->assertEquals(120, $video->duration);
        $this->assertEquals('1080p', $video->bunny_resolution);
        $this->assertEquals(50000000, $video->bunny_file_size);
    }

    #[Test]
    public function webhook_updates_video_status_to_error(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 5, // error
            'ErrorMessage' => 'Invalid video codec',
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('error', $video->bunny_status);
        $this->assertEquals('Invalid video codec', $video->bunny_error);
    }

    #[Test]
    public function webhook_handles_error_without_message(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 5, // error
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('error', $video->bunny_status);
        $this->assertEquals('Unknown error during processing', $video->bunny_error);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    #[Test]
    public function webhook_handles_unknown_status_code(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 99, // unknown status
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('unknown', $video->bunny_status);
    }

    #[Test]
    public function webhook_is_accessible_without_authentication(): void
    {
        // Webhooks from Bunny should not require auth
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
        ]);

        // No actingAs() - simulating external webhook call
        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 4,
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function webhook_handles_partial_metadata(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
            'duration' => 60,
        ]);

        // Only Length provided, no dimensions
        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 4,
            'Length' => 120,
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('ready', $video->bunny_status);
        $this->assertEquals(120, $video->duration);
        $this->assertNull($video->bunny_resolution);
    }

    #[Test]
    public function webhook_preserves_existing_data_when_not_provided(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-video-guid',
            'title' => 'My Important Video',
            'description' => 'A detailed description',
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'test-video-guid',
            'Status' => 4,
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('My Important Video', $video->title);
        $this->assertEquals('A detailed description', $video->description);
    }

    // ==========================================
    // WORKFLOW TESTS
    // ==========================================

    #[Test]
    public function full_webhook_lifecycle(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'lifecycle-test-guid',
        ]);

        // Step 1: Video created (status 0)
        $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'lifecycle-test-guid',
            'Status' => 0,
        ])->assertStatus(200);

        $video->refresh();
        $this->assertEquals('pending', $video->bunny_status);

        // Step 2: Video uploaded (status 1)
        $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'lifecycle-test-guid',
            'Status' => 1,
        ])->assertStatus(200);

        $video->refresh();
        $this->assertEquals('uploaded', $video->bunny_status);

        // Step 3: Processing started (status 2)
        $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'lifecycle-test-guid',
            'Status' => 2,
        ])->assertStatus(200);

        $video->refresh();
        $this->assertEquals('processing', $video->bunny_status);

        // Step 4: Transcoding (status 3)
        $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'lifecycle-test-guid',
            'Status' => 3,
        ])->assertStatus(200);

        $video->refresh();
        $this->assertEquals('transcoding', $video->bunny_status);

        // Step 5: Finished (status 4)
        $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'lifecycle-test-guid',
            'Status' => 4,
            'Length' => 300,
            'Width' => 1920,
            'Height' => 1080,
            'StorageSize' => 100000000,
        ])->assertStatus(200);

        $video->refresh();
        $this->assertEquals('ready', $video->bunny_status);
        $this->assertEquals(300, $video->duration);
        $this->assertEquals('1080p', $video->bunny_resolution);
        $this->assertEquals(100000000, $video->bunny_file_size);
    }
}

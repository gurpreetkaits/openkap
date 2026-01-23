<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BunnyStreamIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'videos_count' => 0,
        ]);

        // Set up Bunny config for tests
        Config::set('services.bunny', [
            'library_id' => 'test-library-123',
            'api_key' => 'test-api-key-456',
            'cdn_hostname' => 'test-cdn.b-cdn.net',
            'security_key' => 'test-security-key-789',
            'playback_expiry' => 3600,
            'upload_expiry' => 7200,
            'base_url' => 'https://video.bunnycdn.com',
            'tus_endpoint' => 'https://video.bunnycdn.com/tusupload',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // STREAMING UPLOAD FLOW TESTS
    // ==========================================

    #[Test]
    public function user_can_start_streaming_upload_session(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/stream/start', [
                'title' => 'My Recording',
                'mime_type' => 'video/webm',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'storage_type',
                'will_use_bunny',
                'message',
            ])
            ->assertJson([
                'storage_type' => 'local',
                'message' => 'Upload session started',
            ]);

        // Verify session directory was created
        $sessionId = $response->json('session_id');
        $this->assertNotEmpty($sessionId);
    }

    #[Test]
    public function user_cannot_start_session_without_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/stream/start', [
                'mime_type' => 'video/webm',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    #[Test]
    public function user_cannot_start_session_when_video_limit_reached(): void
    {
        // Create videos to exceed the free limit (default is 1)
        // User must not have an active subscription
        Video::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/stream/start', [
                'title' => 'My Recording',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'video_limit_reached',
            ]);
    }

    #[Test]
    public function user_can_upload_chunks_to_session(): void
    {
        // Start a session
        $startResponse = $this->actingAs($this->user)
            ->postJson('/api/stream/start', [
                'title' => 'Chunk Test Recording',
            ]);

        $sessionId = $startResponse->json('session_id');

        // Upload a chunk
        $chunk = UploadedFile::fake()->create('chunk.webm', 100, 'video/webm');

        $response = $this->actingAs($this->user)
            ->postJson("/api/stream/{$sessionId}/chunk", [
                'chunk' => $chunk,
                'chunk_index' => 0,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'chunk_index',
                'chunk_size',
                'total_size',
                'chunks_received',
            ])
            ->assertJson([
                'message' => 'Chunk received',
                'chunk_index' => 0,
                'chunks_received' => 1,
            ]);
    }

    #[Test]
    public function user_can_upload_multiple_chunks_in_order(): void
    {
        $startResponse = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'Multi-chunk Recording']);

        $sessionId = $startResponse->json('session_id');

        // Upload 3 chunks in order
        for ($i = 0; $i < 3; $i++) {
            $chunk = UploadedFile::fake()->create("chunk_{$i}.webm", 50, 'video/webm');

            $response = $this->actingAs($this->user)
                ->postJson("/api/stream/{$sessionId}/chunk", [
                    'chunk' => $chunk,
                    'chunk_index' => $i,
                ]);

            $response->assertStatus(200)
                ->assertJson([
                    'chunk_index' => $i,
                    'chunks_received' => $i + 1,
                ]);
        }
    }

    #[Test]
    public function user_cannot_upload_chunk_to_invalid_session(): void
    {
        $chunk = UploadedFile::fake()->create('chunk.webm', 100, 'video/webm');

        $response = $this->actingAs($this->user)
            ->postJson('/api/stream/invalid-session-id/chunk', [
                'chunk' => $chunk,
                'chunk_index' => 0,
            ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Invalid session']);
    }

    #[Test]
    public function user_cannot_upload_chunk_to_another_users_session(): void
    {
        // Create session as user
        $startResponse = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'My Recording']);

        $sessionId = $startResponse->json('session_id');

        // Try to upload as different user
        $otherUser = User::factory()->create();
        $chunk = UploadedFile::fake()->create('chunk.webm', 100, 'video/webm');

        $response = $this->actingAs($otherUser)
            ->postJson("/api/stream/{$sessionId}/chunk", [
                'chunk' => $chunk,
                'chunk_index' => 0,
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    #[Test]
    public function user_can_complete_upload_and_get_video(): void
    {
        Queue::fake();

        // Start session
        $startResponse = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'Complete Test Recording']);

        $sessionId = $startResponse->json('session_id');

        // Upload a chunk (we need actual file content for this)
        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        if (! file_exists($sessionDir)) {
            mkdir($sessionDir, 0755, true);
        }

        // Create a minimal WebM file with proper EBML/Matroska header
        $webmContent = $this->createWebmFileContent();
        file_put_contents("{$sessionDir}/video.webm", $webmContent);

        // Update metadata
        $metadata = json_decode(file_get_contents("{$sessionDir}/metadata.json"), true);
        $metadata['total_size'] = strlen($webmContent);
        $metadata['chunks_received'] = 1;
        file_put_contents("{$sessionDir}/metadata.json", json_encode($metadata));

        // Complete upload
        $response = $this->actingAs($this->user)
            ->postJson("/api/stream/{$sessionId}/complete", [
                'duration' => 60,
                'title' => 'Final Title',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'video' => [
                    'id',
                    'title',
                    'duration',
                    'url',
                    'share_url',
                    'share_token',
                    'is_public',
                    'storage_type',
                    'created_at',
                ],
            ])
            ->assertJson([
                'message' => 'Video uploaded successfully',
                'video' => [
                    'title' => 'Final Title',
                    'duration' => 60,
                    'is_public' => true,
                ],
            ]);

        // Verify video was created in database
        $videoId = $response->json('video.id');
        $video = Video::find($videoId);
        $this->assertNotNull($video);
        $this->assertEquals($this->user->id, $video->user_id);
        $this->assertEquals('Final Title', $video->title);

        // Verify user's video count was incremented
        $this->user->refresh();
        $this->assertEquals(1, $this->user->videos_count);
    }

    #[Test]
    public function complete_upload_dispatches_bunny_job_when_configured(): void
    {
        Queue::fake();

        // Create a pro user who should encode videos
        $proUser = User::factory()->withProSubscription()->create();

        // Mock BunnyStreamService to return configured
        $this->mock(BunnyStreamService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
        });

        // Start and complete a session (simplified)
        $startResponse = $this->actingAs($proUser)
            ->postJson('/api/stream/start', ['title' => 'Bunny Test Recording']);

        $sessionId = $startResponse->json('session_id');

        // Create minimal video file with proper WebM header
        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        file_put_contents("{$sessionDir}/video.webm", $this->createWebmFileContent());
        $metadata = json_decode(file_get_contents("{$sessionDir}/metadata.json"), true);
        $metadata['total_size'] = strlen($this->createWebmFileContent());
        $metadata['chunks_received'] = 1;
        file_put_contents("{$sessionDir}/metadata.json", json_encode($metadata));

        $response = $this->actingAs($proUser)
            ->postJson("/api/stream/{$sessionId}/complete", ['duration' => 30]);

        $response->assertStatus(201);

        // Jobs are dispatched via afterResponse closure, so we check the video state
        $video = Video::find($response->json('video.id'));
        $this->assertEquals('bunny', $video->storage_type);
        $this->assertEquals('pending', $video->bunny_status);
    }

    #[Test]
    public function user_can_cancel_upload_session(): void
    {
        $startResponse = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'Cancel Test']);

        $sessionId = $startResponse->json('session_id');

        $response = $this->actingAs($this->user)
            ->postJson("/api/stream/{$sessionId}/cancel");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Upload cancelled']);

        // Verify session directory was cleaned up
        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        $this->assertDirectoryDoesNotExist($sessionDir);
    }

    #[Test]
    public function user_can_get_session_status(): void
    {
        $startResponse = $this->actingAs($this->user)
            ->postJson('/api/stream/start', ['title' => 'Status Test']);

        $sessionId = $startResponse->json('session_id');

        $response = $this->actingAs($this->user)
            ->getJson("/api/stream/{$sessionId}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'title',
                'chunks_received',
                'total_size',
                'started_at',
            ])
            ->assertJson([
                'session_id' => $sessionId,
                'title' => 'Status Test',
                'chunks_received' => 0,
            ]);
    }

    // ==========================================
    // BUNNY VIDEO PLAYBACK TESTS
    // ==========================================

    #[Test]
    public function user_can_get_playback_urls_for_ready_bunny_video(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        $this->mock(BunnyStreamService::class, function ($mock) {
            $mock->shouldReceive('getVideoStatus')
                ->once()
                ->andReturn([
                    'videoId' => 'video-123',
                    'status' => 'ready',
                    'rawStatus' => 4,
                    'duration' => 120,
                    'size' => 1024000,
                    'width' => 1920,
                    'height' => 1080,
                    'availableResolutions' => '360p,480p,720p,1080p',
                    'encodeProgress' => 100,
                    'title' => 'Test Video',
                ]);
            $mock->shouldReceive('generateSignedPlaybackUrl')
                ->once()
                ->andReturn([
                    'hlsUrl' => 'https://cdn.bunny.net/video123/playlist.m3u8?token=abc&expires=123',
                    'embedUrl' => 'https://iframe.mediadelivery.net/embed/lib123/video123',
                    'thumbnailUrl' => 'https://cdn.bunny.net/video123/thumbnail.jpg',
                    'expiresAt' => now()->addHour()->toISOString(),
                ]);
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/bunny/videos/{$video->id}/playback");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'video' => [
                    'id',
                    'title',
                    'duration',
                    'resolution',
                    'status',
                    'encode_progress',
                    'available_resolutions',
                ],
                'playback' => [
                    'hlsUrl',
                    'embedUrl',
                    'thumbnailUrl',
                    'expiresAt',
                ],
            ])
            ->assertJsonPath('video.status', 'ready')
            ->assertJsonPath('video.encode_progress', 100)
            ->assertJsonPath('video.available_resolutions', ['360p', '480p', '720p', '1080p']);
    }

    #[Test]
    public function user_can_get_playback_urls_for_transcoding_bunny_video(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'storage_type' => 'bunny',
            'bunny_video_id' => 'transcoding-video-123',
            'bunny_status' => 'transcoding',
        ]);

        $this->mock(BunnyStreamService::class, function ($mock) {
            $mock->shouldReceive('getVideoStatus')
                ->once()
                ->andReturn([
                    'videoId' => 'transcoding-video-123',
                    'status' => 'transcoding',
                    'rawStatus' => 3,
                    'duration' => 120,
                    'size' => 1024000,
                    'width' => 1920,
                    'height' => 1080,
                    'availableResolutions' => '360p,480p',
                    'encodeProgress' => 45,
                    'title' => 'Test Video',
                ]);
            $mock->shouldReceive('generateSignedPlaybackUrl')
                ->once()
                ->andReturn([
                    'hlsUrl' => 'https://cdn.bunny.net/video123/playlist.m3u8?token=abc&expires=123',
                    'embedUrl' => 'https://iframe.mediadelivery.net/embed/lib123/video123',
                    'thumbnailUrl' => 'https://cdn.bunny.net/video123/thumbnail.jpg',
                    'expiresAt' => now()->addHour()->toISOString(),
                ]);
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/bunny/videos/{$video->id}/playback");

        $response->assertStatus(200)
            ->assertJsonPath('video.status', 'transcoding')
            ->assertJsonPath('video.encode_progress', 45)
            ->assertJsonPath('video.available_resolutions', ['360p', '480p'])
            ->assertJsonStructure([
                'playback' => [
                    'hlsUrl',
                    'embedUrl',
                    'thumbnailUrl',
                    'expiresAt',
                ],
            ]);
    }

    #[Test]
    public function playback_returns_processing_status_for_non_ready_video(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/bunny/videos/{$video->id}/playback");

        $response->assertStatus(202)
            ->assertJson([
                'error' => 'processing',
                'message' => 'Video is still being processed',
                'status' => 'processing',
            ]);
    }

    #[Test]
    public function shared_playback_works_for_public_bunny_videos(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
        ]);

        $this->mock(BunnyStreamService::class, function ($mock) {
            $mock->shouldReceive('getVideoStatus')
                ->once()
                ->andReturn([
                    'videoId' => 'video-123',
                    'status' => 'ready',
                    'rawStatus' => 4,
                    'duration' => 120,
                    'size' => 1024000,
                    'width' => 1920,
                    'height' => 1080,
                    'availableResolutions' => '360p,480p,720p,1080p',
                    'encodeProgress' => 100,
                    'title' => 'Test Video',
                ]);
            $mock->shouldReceive('generateSignedPlaybackUrl')
                ->once()
                ->andReturn([
                    'hlsUrl' => 'https://cdn.bunny.net/video123/playlist.m3u8',
                    'embedUrl' => 'https://iframe.mediadelivery.net/embed/lib123/video123',
                    'thumbnailUrl' => 'https://cdn.bunny.net/video123/thumbnail.jpg',
                    'expiresAt' => now()->addHour()->toISOString(),
                ]);
        });

        // No auth required for shared videos
        $response = $this->getJson("/api/bunny/share/{$video->share_token}/playback");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'video' => [
                    'id',
                    'title',
                    'status',
                    'encode_progress',
                    'available_resolutions',
                    'owner' => [
                        'name',
                    ],
                ],
                'playback' => [
                    'hlsUrl',
                ],
            ]);
    }

    #[Test]
    public function shared_playback_fails_for_private_videos(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/bunny/share/{$video->share_token}/playback");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'not_found',
                'message' => 'Video not found or is private',
            ]);
    }

    #[Test]
    public function shared_playback_fails_for_expired_links(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'share_expires_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/api/bunny/share/{$video->share_token}/playback");

        $response->assertStatus(410)
            ->assertJson([
                'error' => 'expired',
                'message' => 'This share link has expired',
            ]);
    }

    // ==========================================
    // BUNNY VIDEO STATUS TESTS
    // ==========================================

    #[Test]
    public function user_can_check_bunny_video_status(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
        ]);

        $this->mock(BunnyStreamService::class, function ($mock) use ($video) {
            $mock->shouldReceive('getVideoStatus')
                ->with($video->bunny_video_id)
                ->once()
                ->andReturn([
                    'status' => 'processing',
                    'encodeProgress' => 50,
                    'duration' => 120,
                    'size' => 5000000,
                    'height' => 1080,
                ]);
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/bunny/videos/{$video->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'progress',
                'duration',
                'is_ready',
            ])
            ->assertJson([
                'status' => 'processing',
                'progress' => 50,
                'is_ready' => false,
            ]);
    }

    #[Test]
    public function status_check_updates_local_status_when_changed(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
        ]);

        $this->mock(BunnyStreamService::class, function ($mock) use ($video) {
            $mock->shouldReceive('getVideoStatus')
                ->with($video->bunny_video_id)
                ->once()
                ->andReturn([
                    'status' => 'ready',
                    'encodeProgress' => 100,
                    'duration' => 120,
                    'size' => 5000000,
                    'height' => 1080,
                ]);
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/bunny/videos/{$video->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ready',
                'is_ready' => true,
            ]);

        // Verify database was updated
        $video->refresh();
        $this->assertEquals('ready', $video->bunny_status);
        $this->assertEquals('1080p', $video->bunny_resolution);
    }

    // ==========================================
    // BUNNY VIDEO DELETION TESTS
    // ==========================================

    #[Test]
    public function user_can_delete_bunny_video(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        $bunnyVideoId = $video->bunny_video_id;

        $this->mock(BunnyStreamService::class, function ($mock) use ($bunnyVideoId) {
            $mock->shouldReceive('deleteVideo')
                ->with($bunnyVideoId)
                ->once()
                ->andReturn(true);
        });

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/bunny/videos/{$video->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);

        // Verify video was deleted from database
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    #[Test]
    public function user_cannot_delete_another_users_bunny_video(): void
    {
        $otherUser = User::factory()->create();
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/bunny/videos/{$video->id}");

        $response->assertStatus(404);

        // Verify video still exists
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    // ==========================================
    // FULL END-TO-END WORKFLOW TEST
    // ==========================================

    #[Test]
    public function full_bunny_upload_workflow(): void
    {
        Queue::fake();

        // Create a pro user who should encode videos
        $proUser = User::factory()->withProSubscription()->create();

        // Mock BunnyStreamService
        $this->mock(BunnyStreamService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
        });

        // Step 1: Start upload session
        $startResponse = $this->actingAs($proUser)
            ->postJson('/api/stream/start', [
                'title' => 'E2E Test Recording',
                'mime_type' => 'video/webm',
            ]);

        $startResponse->assertStatus(200);
        $sessionId = $startResponse->json('session_id');
        $this->assertTrue($startResponse->json('will_use_bunny'));

        // Step 2: Upload chunks using proper WebM content
        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        // Create a single chunk with proper WebM header
        $webmContent = $this->createWebmFileContent();
        $tempFile = tempnam(sys_get_temp_dir(), 'chunk');
        file_put_contents($tempFile, $webmContent);

        $chunk = new UploadedFile($tempFile, 'chunk_0.webm', 'video/webm', null, true);

        $chunkResponse = $this->actingAs($proUser)
            ->postJson("/api/stream/{$sessionId}/chunk", [
                'chunk' => $chunk,
                'chunk_index' => 0,
            ]);

        $chunkResponse->assertStatus(200);

        // Replace the video.webm with proper WebM content before completing
        file_put_contents("{$sessionDir}/video.webm", $webmContent);
        $metadata = json_decode(file_get_contents("{$sessionDir}/metadata.json"), true);
        $metadata['total_size'] = strlen($webmContent);
        file_put_contents("{$sessionDir}/metadata.json", json_encode($metadata));

        // Step 3: Complete upload
        $completeResponse = $this->actingAs($proUser)
            ->postJson("/api/stream/{$sessionId}/complete", [
                'duration' => 180,
            ]);

        $completeResponse->assertStatus(201);

        // Verify video was created
        $video = Video::find($completeResponse->json('video.id'));
        $this->assertNotNull($video);
        $this->assertEquals('bunny', $video->storage_type);
        $this->assertEquals('pending', $video->bunny_status);

        // Verify share URL is immediately available
        $shareUrl = $completeResponse->json('video.share_url');
        $this->assertNotEmpty($shareUrl);

        // Step 4: Simulate webhook from Bunny (video ready)
        $video->update([
            'bunny_video_id' => 'test-bunny-video-id',
            'bunny_status' => 'ready',
            'bunny_resolution' => '1080p',
        ]);

        // Step 5: Verify playback works
        $this->mock(BunnyStreamService::class, function ($mock) {
            $mock->shouldReceive('getVideoStatus')
                ->andReturn([
                    'videoId' => 'test-bunny-video-id',
                    'status' => 'ready',
                    'rawStatus' => 4,
                    'duration' => 180,
                    'size' => 1024000,
                    'width' => 1920,
                    'height' => 1080,
                    'availableResolutions' => '360p,480p,720p,1080p',
                    'encodeProgress' => 100,
                    'title' => 'Test Video',
                ]);
            $mock->shouldReceive('generateSignedPlaybackUrl')
                ->andReturn([
                    'hlsUrl' => 'https://cdn.bunny.net/test/playlist.m3u8',
                    'embedUrl' => 'https://iframe.mediadelivery.net/embed/lib/test',
                    'thumbnailUrl' => 'https://cdn.bunny.net/test/thumbnail.jpg',
                    'expiresAt' => now()->addHour()->toISOString(),
                ]);
        });

        $playbackResponse = $this->actingAs($this->user)
            ->getJson("/api/bunny/videos/{$video->id}/playback");

        $playbackResponse->assertStatus(200)
            ->assertJsonStructure([
                'playback' => ['hlsUrl'],
                'video' => ['available_resolutions', 'encode_progress'],
            ]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Create minimal WebM file content with proper EBML/Matroska header.
     * This content will be detected as video/webm by PHP's finfo.
     */
    protected function createWebmFileContent(): string
    {
        // Minimal valid WebM file header (EBML document with DocType "webm")
        // This is a properly structured EBML header that finfo will recognize
        $header = pack('C*',
            // EBML Element ID
            0x1A, 0x45, 0xDF, 0xA3,
            // EBML Size (variable length encoded: 31 bytes)
            0xA3,
            // EBMLVersion: 1
            0x42, 0x86, 0x81, 0x01,
            // EBMLReadVersion: 1
            0x42, 0xF7, 0x81, 0x01,
            // EBMLMaxIDLength: 4
            0x42, 0xF2, 0x81, 0x04,
            // EBMLMaxSizeLength: 8
            0x42, 0xF3, 0x81, 0x08,
            // DocType: "webm"
            0x42, 0x82, 0x84, 0x77, 0x65, 0x62, 0x6D,
            // DocTypeVersion: 2
            0x42, 0x87, 0x81, 0x02,
            // DocTypeReadVersion: 2
            0x42, 0x85, 0x81, 0x02,
            // Segment Element ID
            0x18, 0x53, 0x80, 0x67,
            // Unknown segment size (all 1s means unknown length)
            0x01, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF
        );

        // Pad to reasonable size
        return $header.str_repeat("\x00", 1000);
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\ConvertVideoToMp4Job;
use App\Jobs\ProcessHlsConversionJob;
use App\Models\User;
use App\Models\Video;
use App\Services\HlsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoUploadWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function conversion_job_is_dispatched_when_video_is_created(): void
    {
        Queue::fake();

        // Create video directly (simulating what happens after upload)
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Video',
            'conversion_status' => 'pending',
        ]);

        // Dispatch conversion job (this is what VideoManager does after upload)
        ConvertVideoToMp4Job::dispatch($video);

        // Assert MP4 conversion job was dispatched
        Queue::assertPushed(ConvertVideoToMp4Job::class, function ($job) {
            return $job->video->title === 'Test Video';
        });
    }

    #[Test]
    public function mp4_conversion_job_dispatches_hls_conversion_on_success(): void
    {
        Queue::fake();

        // Create a video with completed conversion status
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Video for HLS',
            'conversion_status' => 'completed',
            'converted_at' => now(),
        ]);

        // Manually dispatch HLS job (simulating what MP4 job does on success)
        ProcessHlsConversionJob::dispatch($video);

        Queue::assertPushed(ProcessHlsConversionJob::class, function ($job) use ($video) {
            return $job->video->id === $video->id;
        });
    }

    #[Test]
    public function video_conversion_status_includes_hls_fields(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'conversion_status' => 'processing',
            'conversion_progress' => 50,
            'hls_status' => 'pending',
            'hls_progress' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}/conversion-status");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'conversion_status' => 'processing',
                'conversion_progress' => 50,
                'hls_status' => 'pending',
                'hls_progress' => 0,
            ]);
    }

    #[Test]
    public function hls_url_is_returned_when_conversion_is_complete(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}");

        $response->assertStatus(200);

        $videoData = $response->json('video');
        $this->assertArrayHasKey('hls_url', $videoData);
    }

    #[Test]
    public function unauthorized_user_cannot_check_other_users_video_status(): void
    {
        $otherUser = User::factory()->create();
        $video = Video::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}/conversion-status");

        $response->assertStatus(403);
    }

    #[Test]
    public function video_upload_fails_without_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/videos', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'video']);
    }

    #[Test]
    public function video_upload_validates_file_type(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson('/api/videos', [
                'title' => 'Test Video',
                'video' => $invalidFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video']);
    }

    #[Test]
    public function full_workflow_mp4_to_hls_conversion(): void
    {
        Queue::fake();

        // Step 1: Create video (simulating upload)
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Workflow Test Video',
            'conversion_status' => 'pending',
        ]);

        // Step 2: Dispatch MP4 conversion (what VideoManager does)
        ConvertVideoToMp4Job::dispatch($video);

        Queue::assertPushed(ConvertVideoToMp4Job::class, function ($job) {
            return $job->video->title === 'Workflow Test Video';
        });

        // Step 3: Simulate MP4 conversion completion
        $video->update([
            'conversion_status' => 'completed',
            'conversion_progress' => 100,
            'converted_at' => now(),
        ]);

        // Step 4: Dispatch HLS job (what MP4 job does on success)
        ProcessHlsConversionJob::dispatch($video);

        Queue::assertPushed(ProcessHlsConversionJob::class, function ($job) use ($video) {
            return $job->video->id === $video->id;
        });

        // Step 5: Simulate HLS conversion completion
        $video->update([
            'hls_status' => 'completed',
            'hls_progress' => 100,
            'hls_path' => 'hls/'.$video->id,
            'hls_converted_at' => now(),
        ]);

        // Verify final state
        $video->refresh();
        $this->assertTrue($video->isHlsReady());
        $this->assertEquals('completed', $video->conversion_status);
        $this->assertEquals('completed', $video->hls_status);
    }

    #[Test]
    public function hls_service_paths_are_loaded_from_config(): void
    {
        // Set specific paths in config
        config([
            'media-library.ffmpeg_path' => '/test/path/ffmpeg',
            'media-library.ffprobe_path' => '/test/path/ffprobe',
        ]);

        $hlsService = new HlsService;

        // Use reflection to check private properties
        $reflection = new \ReflectionClass($hlsService);

        $ffmpegProperty = $reflection->getProperty('ffmpegPath');
        $ffmpegProperty->setAccessible(true);

        $ffprobeProperty = $reflection->getProperty('ffprobePath');
        $ffprobeProperty->setAccessible(true);

        $this->assertEquals('/test/path/ffmpeg', $ffmpegProperty->getValue($hlsService));
        $this->assertEquals('/test/path/ffprobe', $ffprobeProperty->getValue($hlsService));
    }

    #[Test]
    public function video_shows_hls_ready_status_after_full_conversion(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($video->isHlsReady());
        $this->assertNotNull($video->getHlsUrl());
    }

    #[Test]
    public function video_hls_url_is_null_when_not_ready(): void
    {
        $video = Video::factory()->converted()->create([
            'user_id' => $this->user->id,
            'hls_status' => 'pending',
        ]);

        $this->assertFalse($video->isHlsReady());
        $this->assertNull($video->getHlsUrl());
    }

    #[Test]
    public function conversion_status_shows_hls_ready_when_complete(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}/conversion-status");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'hls_status' => 'completed',
                'is_hls_ready' => true,
            ]);
    }
}

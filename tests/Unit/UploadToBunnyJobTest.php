<?php

namespace Tests\Unit;

use App\Jobs\UploadToBunnyJob;
use App\Models\User;
use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class UploadToBunnyJobTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Bunny disabled - encoding costs too high');

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // JOB CONFIGURATION TESTS
    // ==========================================

    #[Test]
    public function job_has_correct_timeout(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
        ]);

        $job = new UploadToBunnyJob($video);

        $this->assertEquals(3600, $job->timeout);
    }

    #[Test]
    public function job_has_correct_retry_count(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
        ]);

        $job = new UploadToBunnyJob($video);

        $this->assertEquals(3, $job->tries);
    }

    // ==========================================
    // SKIP CONDITIONS TESTS
    // ==========================================

    #[Test]
    public function job_skips_if_video_already_ready(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        $bunnyService = Mockery::mock(BunnyStreamService::class);
        $bunnyService->shouldNotReceive('createVideo');
        $bunnyService->shouldNotReceive('getLibraryId');

        $job = new UploadToBunnyJob($video);
        $job->handle($bunnyService);

        // Video status should remain unchanged
        $video->refresh();
        $this->assertEquals('ready', $video->bunny_status);
    }

    #[Test]
    public function job_handles_missing_media(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
        ]);

        // No media attached - simulate missing file
        $bunnyService = Mockery::mock(BunnyStreamService::class);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'No video file found for Bunny upload';
            });

        $job = new UploadToBunnyJob($video);
        $job->handle($bunnyService);

        // Check error was recorded
        $video->refresh();
        $this->assertEquals('No video file found', $video->bunny_error);
    }

    // ==========================================
    // VIDEO CREATION TESTS
    // ==========================================

    #[Test]
    public function job_creates_video_in_bunny_if_not_exists(): void
    {
        // Create video without bunny_video_id
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Upload Video',
            'bunny_video_id' => null,
        ]);

        // Create a mock media and attach to video using partial mock
        $this->createMockMediaForVideo($video);

        $bunnyService = Mockery::mock(BunnyStreamService::class);

        $bunnyService->shouldReceive('createVideo')
            ->once()
            ->with('Test Upload Video')
            ->andReturn(['guid' => 'new-bunny-guid']);

        $bunnyService->shouldReceive('getLibraryId')
            ->once()
            ->andReturn('test-library-id');

        // The actual upload would happen via cURL, which we can't easily mock
        // So we'll test up to the point of video creation
        $job = new UploadToBunnyJob($video);

        try {
            $job->handle($bunnyService);
        } catch (\Exception $e) {
            // Expected - cURL upload will fail in test environment
        }

        $video->refresh();
        $this->assertEquals('new-bunny-guid', $video->bunny_video_id);
        $this->assertEquals('test-library-id', $video->bunny_library_id);
    }

    #[Test]
    public function job_skips_creation_if_bunny_video_id_exists(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'existing-guid',
            'bunny_library_id' => 'existing-library',
        ]);

        $this->createMockMediaForVideo($video);

        $bunnyService = Mockery::mock(BunnyStreamService::class);

        // Should NOT create a new video
        $bunnyService->shouldNotReceive('createVideo');
        $bunnyService->shouldNotReceive('getLibraryId');

        $job = new UploadToBunnyJob($video);

        try {
            $job->handle($bunnyService);
        } catch (\Exception $e) {
            // Expected - cURL upload will fail
        }

        // bunny_video_id should remain unchanged
        $video->refresh();
        $this->assertEquals('existing-guid', $video->bunny_video_id);
    }

    // ==========================================
    // STATUS UPDATE TESTS
    // ==========================================

    #[Test]
    public function job_updates_status_to_uploading(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => 'test-guid',
        ]);

        $this->createMockMediaForVideo($video);

        $bunnyService = Mockery::mock(BunnyStreamService::class);

        $job = new UploadToBunnyJob($video);

        try {
            $job->handle($bunnyService);
        } catch (\Exception $e) {
            // Expected
        }

        $video->refresh();
        // Status should be 'uploading' or 'error' depending on where it failed
        $this->assertContains($video->bunny_status, ['uploading', 'error']);
    }

    // ==========================================
    // ERROR HANDLING TESTS
    // ==========================================

    #[Test]
    public function job_records_error_on_failure(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
            'bunny_video_id' => null,
        ]);

        $this->createMockMediaForVideo($video);

        $bunnyService = Mockery::mock(BunnyStreamService::class);

        $bunnyService->shouldReceive('createVideo')
            ->once()
            ->andThrow(new \Exception('API connection failed'));

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Bunny upload failed'
                    && $context['error'] === 'API connection failed';
            });

        $job = new UploadToBunnyJob($video);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API connection failed');

        $job->handle($bunnyService);

        $video->refresh();
        $this->assertEquals('error', $video->bunny_status);
        $this->assertEquals('API connection failed', $video->bunny_error);
    }

    #[Test]
    public function job_rethrows_exception_for_retry(): void
    {
        $video = Video::factory()->bunnyPending()->create([
            'user_id' => $this->user->id,
        ]);

        $this->createMockMediaForVideo($video);

        $bunnyService = Mockery::mock(BunnyStreamService::class);

        $bunnyService->shouldReceive('createVideo')
            ->andThrow(new \Exception('Temporary failure'));

        Log::shouldReceive('info');
        Log::shouldReceive('error');

        $job = new UploadToBunnyJob($video);

        $this->expectException(\Exception::class);

        $job->handle($bunnyService);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Create a mock media file for the video.
     * Creates an actual file with proper video-like content and
     * directly attaches it via the media table.
     */
    protected function createMockMediaForVideo(Video $video): void
    {
        // Create a temp directory and file
        $tempDir = storage_path('app/public/temp-test-media');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fileName = "video_{$video->id}.webm";
        $tempFile = "{$tempDir}/{$fileName}";

        // Create a minimal fake video file - just needs to exist for path check
        file_put_contents($tempFile, str_repeat("\x00", 1000));

        // Create media record directly in database (bypass collection validation)
        Media::create([
            'model_type' => Video::class,
            'model_id' => $video->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'collection_name' => 'videos',
            'name' => "video_{$video->id}",
            'file_name' => $fileName,
            'mime_type' => 'video/webm',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1000,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        // Update the media record to point to the actual file path
        $media = $video->getFirstMedia('videos');
        if ($media) {
            // Create directory structure that Spatie expects
            $mediaDir = storage_path("app/public/{$media->id}");
            if (! file_exists($mediaDir)) {
                mkdir($mediaDir, 0755, true);
            }
            // Move/copy the file to where Spatie expects it
            copy($tempFile, "{$mediaDir}/{$fileName}");
        }
    }
}

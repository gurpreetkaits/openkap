<?php

namespace Tests\Feature;

use App\Jobs\GenerateThumbnailJob;
use App\Jobs\GenerateTranscriptionJob;
use App\Jobs\RemuxWebmJob;
use App\Jobs\UploadToBunnyJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoUploadTest extends TestCase
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

    protected function configureBunny(bool $enabled): void
    {
        Config::set('services.bunny', [
            'library_id' => $enabled ? 'test-library-123' : '',
            'api_key' => $enabled ? 'test-api-key-456' : '',
            'cdn_hostname' => 'test-cdn.b-cdn.net',
            'security_key' => 'test-security',
            'playback_expiry' => 3600,
            'upload_expiry' => 7200,
            'base_url' => 'https://video.bunnycdn.com',
            'tus_endpoint' => 'https://video.bunnycdn.com/tusupload',
        ]);
    }

    /**
     * Build a fake .webm UploadedFile whose content begins with the EBML/WebM
     * magic header so Spatie's finfo-based MIME sniff sees `video/webm`.
     */
    protected function fakeVideoFile(string $name = 'clip.webm'): UploadedFile
    {
        $webmHeader = "\x1A\x45\xDF\xA3"          // EBML magic
            ."\x9F\x42\x86\x81\x01"               // EBMLVersion = 1
            ."\x42\xF7\x81\x01"                   // EBMLReadVersion = 1
            ."\x42\xF2\x81\x04"                   // EBMLMaxIDLength = 4
            ."\x42\xF3\x81\x08"                   // EBMLMaxSizeLength = 8
            ."\x42\x82\x84webm";                  // DocType = "webm"

        return UploadedFile::fake()->createWithContent($name, $webmHeader.str_repeat("\x00", 1024));
    }

    protected function uploadPayload(string $title, ?UploadedFile $file = null, int $duration = 30): array
    {
        return [
            'title' => $title,
            'duration' => $duration,
            'video' => $file ?: $this->fakeVideoFile(),
        ];
    }

    #[Test]
    public function authenticated_user_can_upload_a_video(): void
    {
        $this->configureBunny(false);

        $response = $this->actingAs($this->user)
            ->postJson('/api/videos', $this->uploadPayload('Holiday Recording'));

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'video' => ['id', 'title', 'share_url', 'thumbnail', 'conversion_status'],
            ])
            ->assertJsonPath('video.title', 'Holiday Recording');

        $this->assertDatabaseHas('videos', [
            'user_id' => $this->user->id,
            'title' => 'Holiday Recording',
        ]);

        $video = Video::where('user_id', $this->user->id)->first();
        $this->assertNotNull($video->getFirstMedia('videos'), 'Video file should be attached via media library');
    }

    #[Test]
    public function unauthenticated_user_cannot_upload(): void
    {
        $response = $this->postJson('/api/videos', $this->uploadPayload('No Auth'));

        $response->assertStatus(401);
        $this->assertDatabaseCount('videos', 0);
    }

    #[Test]
    public function upload_dispatches_bunny_job_when_bunny_is_configured(): void
    {
        $this->configureBunny(true);

        $response = $this->actingAs($this->user)
            ->postJson('/api/videos', $this->uploadPayload('Goes to Bunny'));

        $response->assertCreated();

        $video = Video::where('user_id', $this->user->id)->firstOrFail();

        // Bunny should be the chosen storage and encoder; local remux is skipped.
        $this->assertSame('bunny', $video->storage_type);
        $this->assertSame('completed', $video->conversion_status);
        $this->assertSame(100, (int) $video->conversion_progress);

        Queue::assertPushed(UploadToBunnyJob::class, fn ($job) => $job->video->id === $video->id);
        Queue::assertNotPushed(RemuxWebmJob::class);
        Queue::assertPushed(GenerateTranscriptionJob::class, fn ($job) => $job->video->id === $video->id);
        // Thumbnail must be dispatched as a job, not generated synchronously
        // (otherwise the request blocks on FFmpeg for the whole video).
        Queue::assertPushed(GenerateThumbnailJob::class, fn ($job) => $job->video->id === $video->id);
    }

    #[Test]
    public function upload_falls_back_to_local_remux_when_bunny_not_configured(): void
    {
        $this->configureBunny(false);

        $this->actingAs($this->user)
            ->postJson('/api/videos', $this->uploadPayload('Local Only'))
            ->assertCreated();

        $video = Video::where('user_id', $this->user->id)->firstOrFail();

        Queue::assertNotPushed(UploadToBunnyJob::class);
        Queue::assertPushed(RemuxWebmJob::class, fn ($job) => $job->video->id === $video->id);
        Queue::assertPushed(GenerateTranscriptionJob::class);
    }

    #[Test]
    public function upload_increments_user_video_count(): void
    {
        $this->configureBunny(true);

        $startingCount = (int) $this->user->videos_count;

        $this->actingAs($this->user)
            ->postJson('/api/videos', $this->uploadPayload('Counted'))
            ->assertCreated();

        $this->assertSame($startingCount + 1, (int) $this->user->fresh()->videos_count);
    }

    #[Test]
    public function upload_supports_multiple_videos_for_one_user(): void
    {
        $this->configureBunny(true);

        foreach (['First clip', 'Second clip', 'Third clip'] as $title) {
            $this->actingAs($this->user)
                ->postJson('/api/videos', $this->uploadPayload($title))
                ->assertCreated();
        }

        $this->assertSame(3, Video::where('user_id', $this->user->id)->count());
        Queue::assertPushed(UploadToBunnyJob::class, 3);
    }

    #[Test]
    public function upload_is_blocked_when_free_quota_exceeded(): void
    {
        $this->configureBunny(true);

        Setting::setValue('free_video_limit', 1, 'integer');

        // First upload fills the free quota.
        $this->actingAs($this->user)
            ->postJson('/api/videos', $this->uploadPayload('Within quota'))
            ->assertCreated();

        // Second upload should be rejected with the documented payload.
        $response = $this->actingAs($this->user)
            ->postJson('/api/videos', $this->uploadPayload('Over quota'));

        $response->assertStatus(403)
            ->assertJsonPath('error', 'video_limit_reached');

        $this->assertSame(1, Video::where('user_id', $this->user->id)->count());

        // Only the first upload triggers the Bunny job.
        Queue::assertPushed(UploadToBunnyJob::class, 1);
    }

    #[Test]
    public function upload_rejects_missing_fields(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/videos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'video']);

        Queue::assertNotPushed(UploadToBunnyJob::class);
        Queue::assertNotPushed(RemuxWebmJob::class);
    }

    #[Test]
    public function upload_rejects_non_video_files(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/videos', [
                'title' => 'Wrong type',
                'duration' => 30,
                'video' => UploadedFile::fake()->create('notes.pdf', 200, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['video']);

        $this->assertDatabaseCount('videos', 0);
        Queue::assertNotPushed(UploadToBunnyJob::class);
    }
}

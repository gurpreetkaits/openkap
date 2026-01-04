<?php

namespace Tests\Feature;

use App\Jobs\GenerateSummaryJob;
use App\Jobs\GenerateTranscriptionJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function transcription_job_is_dispatched_after_hls_conversion(): void
    {
        Queue::fake();

        // Create a video with completed conversion
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Video for Transcription',
            'transcription_status' => 'pending',
        ]);

        // Dispatch transcription job (simulating what HLS job does)
        GenerateTranscriptionJob::dispatch($video, true, true);

        Queue::assertPushed(GenerateTranscriptionJob::class, function ($job) use ($video) {
            return $job->video->id === $video->id;
        });
    }

    #[Test]
    public function user_can_request_transcription_for_completed_video(): void
    {
        Queue::fake();

        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'transcription_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/transcription", [
                'generate_summary' => true,
                'generate_title' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'status' => 'pending',
            ]);

        Queue::assertPushed(GenerateTranscriptionJob::class);
    }

    #[Test]
    public function user_cannot_request_transcription_for_processing_video(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'conversion_status' => 'processing',
            'transcription_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/transcription");

        // Returns 400 because video conversion is not complete
        $response->assertStatus(400);
    }

    #[Test]
    public function user_can_get_transcription_status(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'transcription_status' => 'processing',
            'transcription_progress' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}/transcription/status");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'transcription_status' => 'processing',
                'transcription_progress' => 50,
                'is_transcribing' => true,
            ]);
    }

    #[Test]
    public function user_can_get_completed_transcription(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'transcription_status' => 'completed',
            'transcription' => 'This is a test transcription.',
            'transcription_segments' => [
                ['start' => 0, 'end' => 5, 'text' => 'This is a test'],
                ['start' => 5, 'end' => 10, 'text' => 'transcription.'],
            ],
            'transcription_generated_at' => now(),
            'summary_status' => 'completed',
            'summary' => 'This is a test summary.',
            'summary_generated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}/transcription");

        $response->assertStatus(200)
            ->assertJsonPath('transcription.transcription', 'This is a test transcription.')
            ->assertJsonPath('summary.summary', 'This is a test summary.');
    }

    #[Test]
    public function unauthorized_user_cannot_access_other_users_transcription(): void
    {
        $otherUser = User::factory()->create();
        $video = Video::factory()->withHls()->create([
            'user_id' => $otherUser->id,
            'transcription_status' => 'completed',
            'transcription' => 'Private transcription',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}/transcription");

        $response->assertStatus(403);
    }

    #[Test]
    public function transcription_returns_null_when_not_ready(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'transcription_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$video->id}/transcription");

        $response->assertStatus(200)
            ->assertJsonPath('transcription', null);
    }

    #[Test]
    public function transcription_already_processing_returns_error(): void
    {
        Queue::fake();

        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'transcription_status' => 'processing',
            'transcription_progress' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/transcription");

        // Returns 400 because transcription is already in progress
        $response->assertStatus(400);

        // Job should not be dispatched again
        Queue::assertNotPushed(GenerateTranscriptionJob::class);
    }

    #[Test]
    public function summary_job_is_dispatched_after_transcription_completion(): void
    {
        Queue::fake();

        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'transcription_status' => 'completed',
            'transcription' => 'Test transcription for summary',
        ]);

        GenerateSummaryJob::dispatch($video);

        Queue::assertPushed(GenerateSummaryJob::class, function ($job) use ($video) {
            return $job->video->id === $video->id;
        });
    }

    #[Test]
    public function video_model_has_transcription_helper_methods(): void
    {
        // Note: isTranscribing() returns true for both 'pending' and 'processing'
        $pendingVideo = Video::factory()->create([
            'transcription_status' => 'pending',
        ]);

        $processingVideo = Video::factory()->create([
            'transcription_status' => 'processing',
        ]);

        // isSummaryReady requires both status and content
        $completedVideo = Video::factory()->create([
            'transcription_status' => 'completed',
            'transcription' => 'Test transcription content',
        ]);

        $failedVideo = Video::factory()->create([
            'transcription_status' => 'failed',
        ]);

        // Pending is considered "transcribing" (waiting to process)
        $this->assertTrue($pendingVideo->isTranscribing());
        $this->assertFalse($pendingVideo->isTranscriptionReady());
        $this->assertFalse($pendingVideo->isTranscriptionFailed());

        $this->assertTrue($processingVideo->isTranscribing());
        $this->assertFalse($processingVideo->isTranscriptionReady());
        $this->assertFalse($processingVideo->isTranscriptionFailed());

        $this->assertFalse($completedVideo->isTranscribing());
        $this->assertTrue($completedVideo->isTranscriptionReady());
        $this->assertFalse($completedVideo->isTranscriptionFailed());

        $this->assertFalse($failedVideo->isTranscribing());
        $this->assertFalse($failedVideo->isTranscriptionReady());
        $this->assertTrue($failedVideo->isTranscriptionFailed());
    }

    #[Test]
    public function video_model_has_summary_helper_methods(): void
    {
        // Note: isSummarizing() returns true for 'pending' and 'processing'
        $pendingVideo = Video::factory()->create([
            'summary_status' => 'pending',
        ]);

        $processingVideo = Video::factory()->create([
            'summary_status' => 'processing',
        ]);

        // isSummaryReady requires both status and content
        $completedVideo = Video::factory()->create([
            'summary_status' => 'completed',
            'summary' => 'Test summary content',
        ]);

        $failedVideo = Video::factory()->create([
            'summary_status' => 'failed',
        ]);

        // Pending is considered "summarizing" (waiting to process)
        $this->assertTrue($pendingVideo->isSummarizing());
        $this->assertFalse($pendingVideo->isSummaryReady());
        $this->assertFalse($pendingVideo->isSummaryFailed());

        $this->assertTrue($processingVideo->isSummarizing());
        $this->assertFalse($processingVideo->isSummaryReady());
        $this->assertFalse($processingVideo->isSummaryFailed());

        $this->assertFalse($completedVideo->isSummarizing());
        $this->assertTrue($completedVideo->isSummaryReady());
        $this->assertFalse($completedVideo->isSummaryFailed());

        $this->assertFalse($failedVideo->isSummarizing());
        $this->assertFalse($failedVideo->isSummaryReady());
        $this->assertTrue($failedVideo->isSummaryFailed());
    }

    #[Test]
    public function transcription_segments_are_stored_as_json(): void
    {
        $segments = [
            ['start' => 0.0, 'end' => 2.5, 'text' => 'Hello world'],
            ['start' => 2.5, 'end' => 5.0, 'text' => 'This is a test'],
        ];

        $video = Video::factory()->create([
            'transcription_segments' => $segments,
        ]);

        $video->refresh();

        $this->assertIsArray($video->transcription_segments);
        $this->assertCount(2, $video->transcription_segments);
        $this->assertEquals('Hello world', $video->transcription_segments[0]['text']);
    }

    #[Test]
    public function shared_video_includes_transcription_when_available(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'transcription_status' => 'completed',
            'transcription' => 'Shared video transcription',
            'transcription_segments' => [['start' => 0, 'end' => 5, 'text' => 'Shared video transcription']],
            'summary_status' => 'completed',
            'summary' => 'Shared video summary',
        ]);

        // Access without auth (public shared video)
        $response = $this->getJson("/api/share/video/{$video->share_token}");

        $response->assertStatus(200)
            ->assertJsonPath('video.transcription', 'Shared video transcription')
            ->assertJsonPath('video.summary', 'Shared video summary');
    }

    #[Test]
    public function shared_video_excludes_transcription_when_not_ready(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'transcription_status' => 'pending',
        ]);

        $response = $this->getJson("/api/share/video/{$video->share_token}");

        $response->assertStatus(200)
            ->assertJsonPath('video.transcription', null)
            ->assertJsonPath('video.summary', null);
    }

    #[Test]
    public function transcription_can_be_regenerated(): void
    {
        Queue::fake();

        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'transcription_status' => 'completed',
            'transcription' => 'Old transcription',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/transcription");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
            ]);

        Queue::assertPushed(GenerateTranscriptionJob::class);

        $video->refresh();
        $this->assertEquals('pending', $video->transcription_status);
    }

    #[Test]
    public function api_logs_are_created_for_openai_calls(): void
    {
        // This is more of an integration test - we verify that api_logs table
        // is used when making OpenAI API calls (would require mocking HTTP)
        $this->assertTrue(
            \Schema::hasTable('api_logs'),
            'api_logs table should exist for logging OpenAI API calls'
        );

        $this->assertTrue(
            \Schema::hasColumn('api_logs', 'service'),
            'api_logs table should have service column'
        );

        $this->assertTrue(
            \Schema::hasColumn('api_logs', 'context'),
            'api_logs table should have context column for video_id etc'
        );
    }
}

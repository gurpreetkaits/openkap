<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserVideosTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->member = User::factory()->create(['is_admin' => false]);
    }

    #[Test]
    public function admin_can_list_a_users_recordings(): void
    {
        Video::factory()->count(3)->create(['user_id' => $this->member->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.user_id', $this->member->id)
            ->assertJsonPath('data.user_email', $this->member->email)
            ->assertJsonPath('data.total_videos', 3)
            ->assertJsonCount(3, 'data.videos')
            ->assertJsonStructure([
                'data' => [
                    'user_id', 'user_name', 'user_email', 'total_videos', 'problem_videos',
                    'videos' => [['id', 'title', 'duration', 'file_size_bytes', 'health', 'created_at']],
                ],
            ]);
    }

    #[Test]
    public function it_only_returns_videos_belonging_to_the_requested_user(): void
    {
        $mine = Video::factory()->create(['user_id' => $this->member->id]);
        $someoneElses = Video::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $ids = collect($response->json('data.videos'))->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($someoneElses->id));
    }

    #[Test]
    public function a_failed_recording_is_flagged_and_surfaces_its_error(): void
    {
        Video::factory()->create([
            'user_id' => $this->member->id,
            'conversion_status' => 'failed',
            'conversion_error' => 'FFmpeg exited with code 254',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.videos.0.health', 'failed')
            ->assertJsonPath('data.videos.0.error', 'FFmpeg exited with code 254')
            ->assertJsonPath('data.problem_videos', 1);
    }

    #[Test]
    public function a_completed_recording_with_no_duration_is_flagged_as_empty(): void
    {
        Video::factory()->withHls()->create([
            'user_id' => $this->member->id,
            'duration' => 0,
            'file_size_bytes' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.videos.0.health', 'empty')
            ->assertJsonPath('data.problem_videos', 1);
    }

    #[Test]
    public function a_still_processing_recording_is_not_flagged_as_empty(): void
    {
        // Pending videos legitimately have no size yet — they must not read as broken.
        Video::factory()->create([
            'user_id' => $this->member->id,
            'conversion_status' => 'pending',
            'duration' => 0,
            'file_size_bytes' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.videos.0.health', 'processing')
            ->assertJsonPath('data.problem_videos', 0);
    }

    #[Test]
    public function a_good_local_recording_is_healthy_despite_the_default_bunny_status(): void
    {
        // videos.bunny_status defaults to 'pending' even for local-only recordings.
        // If that column were read unconditionally this would wrongly report
        // 'processing' forever, so this asserts the storage-type gating holds.
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->member->id,
            'duration' => 120,
            'file_size_bytes' => 5_000_000,
        ]);

        $this->assertSame('pending', $video->fresh()->bunny_status);
        $this->assertSame('local', $video->fresh()->storage_type);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.videos.0.health', 'ok')
            ->assertJsonPath('data.videos.0.bunny_status', null)
            ->assertJsonPath('data.problem_videos', 0);
    }

    #[Test]
    public function a_bunny_recording_that_errored_is_flagged_as_failed(): void
    {
        // Bunny reports failure as 'error', not 'failed' like the local pipeline.
        Video::factory()->withHls()->create([
            'user_id' => $this->member->id,
            'duration' => 120,
            'file_size_bytes' => 5_000_000,
            'storage_type' => 'bunny',
            'bunny_video_id' => 'abc-123',
            'bunny_status' => 'error',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.videos.0.health', 'failed')
            ->assertJsonPath('data.problem_videos', 1);
    }

    #[Test]
    public function a_ready_bunny_recording_is_healthy(): void
    {
        Video::factory()->withHls()->create([
            'user_id' => $this->member->id,
            'duration' => 90,
            'file_size_bytes' => 0,
            'bunny_file_size' => 4_200_000,
            'storage_type' => 'bunny',
            'bunny_video_id' => 'abc-456',
            'bunny_status' => 'ready',
            'bunny_resolution' => '1920x1080',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.videos.0.health', 'ok')
            ->assertJsonPath('data.videos.0.bunny_resolution', '1920x1080')
            // Bunny-hosted recordings carry their size on the Bunny column.
            ->assertJsonPath('data.videos.0.file_size_bytes', 4_200_000)
            ->assertJsonPath('data.problem_videos', 0);
    }

    /**
     * These four shapes are the ones that actually dominate production. Bunny handles
     * its own delivery, so the local HLS step never runs for Bunny-hosted recordings
     * and hls_status stays 'pending' forever — treating that as "in flight" previously
     * mislabelled ~90% of the library as "processing".
     */
    #[Test]
    public function a_ready_bunny_recording_is_ok_even_though_hls_stayed_pending(): void
    {
        Video::factory()->create([
            'user_id' => $this->member->id,
            'duration' => 200, 'file_size_bytes' => 0, 'bunny_file_size' => 8_000_000,
            'storage_type' => 'bunny', 'bunny_video_id' => 'b-1',
            'conversion_status' => 'completed', 'hls_status' => 'pending', 'bunny_status' => 'ready',
        ]);

        $this->assertHealthIs('ok');
    }

    #[Test]
    public function a_converted_local_recording_is_ok_even_though_hls_stayed_pending(): void
    {
        Video::factory()->create([
            'user_id' => $this->member->id,
            'duration' => 200, 'file_size_bytes' => 7_000_000,
            'conversion_status' => 'completed', 'hls_status' => 'pending',
        ]);

        $this->assertHealthIs('ok');
    }

    #[Test]
    public function a_ready_bunny_recording_ignores_the_local_conversion_status(): void
    {
        // Bunny-hosted recordings are never converted locally, so conversion_status
        // sits at 'pending' and must not drag health down.
        Video::factory()->create([
            'user_id' => $this->member->id,
            'duration' => 200, 'file_size_bytes' => 0, 'bunny_file_size' => 8_000_000,
            'storage_type' => 'bunny', 'bunny_video_id' => 'b-2',
            'conversion_status' => 'pending', 'hls_status' => 'pending', 'bunny_status' => 'ready',
        ]);

        $this->assertHealthIs('ok');
    }

    #[Test]
    public function a_failed_hls_step_still_counts_as_failed(): void
    {
        Video::factory()->create([
            'user_id' => $this->member->id,
            'duration' => 200, 'file_size_bytes' => 7_000_000,
            'conversion_status' => 'completed', 'hls_status' => 'failed',
        ]);

        $this->assertHealthIs('failed');
    }

    #[Test]
    public function a_bunny_recording_still_uploading_is_processing(): void
    {
        Video::factory()->create([
            'user_id' => $this->member->id,
            'duration' => 0, 'file_size_bytes' => 0,
            'storage_type' => 'bunny', 'bunny_video_id' => 'b-3',
            'conversion_status' => 'completed', 'hls_status' => 'pending', 'bunny_status' => 'uploading',
        ]);

        $this->assertHealthIs('processing');
    }

    private function assertHealthIs(string $expected): void
    {
        $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos")
            ->assertOk()
            ->assertJsonPath('data.videos.0.health', $expected);
    }

    #[Test]
    public function videos_are_returned_newest_first(): void
    {
        $old = Video::factory()->create([
            'user_id' => $this->member->id,
            'created_at' => now()->subDays(5),
        ]);
        $new = Video::factory()->create([
            'user_id' => $this->member->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->member->id}/videos");

        $response->assertOk()
            ->assertJsonPath('data.videos.0.id', $new->id)
            ->assertJsonPath('data.videos.1.id', $old->id);
    }

    #[Test]
    public function a_non_admin_cannot_view_another_users_recordings(): void
    {
        Video::factory()->create(['user_id' => $this->admin->id]);

        $this->actingAs($this->member)
            ->getJson("/api/admin/users/{$this->admin->id}/videos")
            ->assertStatus(403);
    }

    #[Test]
    public function a_guest_cannot_view_recordings(): void
    {
        $this->getJson("/api/admin/users/{$this->member->id}/videos")
            ->assertStatus(401);
    }

    #[Test]
    public function an_unknown_user_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/users/999999/videos')
            ->assertStatus(404);
    }
}

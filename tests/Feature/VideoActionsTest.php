<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['videos_count' => 5]);
        $this->otherUser = User::factory()->create(['videos_count' => 3]);
    }

    // ==========================================
    // DUPLICATE VIDEO
    // ==========================================

    #[Test]
    public function user_can_duplicate_their_own_video(): void
    {
        $video = Video::factory()->bunnyReady()->withTranscription()->withSummary()->create([
            'user_id' => $this->user->id,
            'title' => 'My Original Video',
            'description' => 'Some description',
            'folder_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/duplicate");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Video duplicated successfully']);

        // Verify the duplicate was created
        $this->assertDatabaseHas('videos', [
            'title' => 'My Original Video (copy)',
            'user_id' => $this->user->id,
            'description' => 'Some description',
            'storage_type' => 'bunny',
            'bunny_status' => 'ready',
            'bunny_video_id' => $video->bunny_video_id,
            'is_public' => false,
        ]);

        // Verify videos_count was incremented
        $this->user->refresh();
        $this->assertEquals(6, $this->user->videos_count);
    }

    #[Test]
    public function duplicate_copies_transcription_and_summary(): void
    {
        $video = Video::factory()->withTranscriptionAndSummary()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/duplicate");

        $response->assertStatus(200);

        $newVideoId = $response->json('video.id');
        $newVideo = Video::find($newVideoId);

        $this->assertEquals($video->transcription, $newVideo->transcription);
        $this->assertEquals($video->transcription_segments, $newVideo->transcription_segments);
        $this->assertEquals($video->summary, $newVideo->summary);
    }

    #[Test]
    public function duplicate_creates_unique_share_token(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/duplicate");

        $response->assertStatus(200);

        $newVideoId = $response->json('video.id');
        $newVideo = Video::find($newVideoId);

        $this->assertNotEquals($video->share_token, $newVideo->share_token);
        $this->assertNotEmpty($newVideo->share_token);
    }

    #[Test]
    public function duplicate_sets_video_as_private(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/duplicate");

        $response->assertStatus(200);

        $newVideoId = $response->json('video.id');
        $newVideo = Video::find($newVideoId);

        $this->assertFalse($newVideo->is_public);
    }

    #[Test]
    public function user_cannot_duplicate_another_users_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$video->id}/duplicate");

        $response->assertStatus(403);

        // videos_count should not change
        $this->user->refresh();
        $this->assertEquals(5, $this->user->videos_count);
    }

    #[Test]
    public function unauthenticated_user_cannot_duplicate_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/videos/{$video->id}/duplicate");

        $response->assertStatus(401);
    }

    // ==========================================
    // TOGGLE PRIVACY
    // ==========================================

    #[Test]
    public function user_can_make_video_private(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/videos/{$video->id}", ['is_public' => false]);

        $response->assertStatus(200)
            ->assertJsonPath('video.is_public', false);

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'is_public' => false,
        ]);
    }

    #[Test]
    public function user_can_make_video_public(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/videos/{$video->id}", ['is_public' => true]);

        $response->assertStatus(200)
            ->assertJsonPath('video.is_public', true);

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'is_public' => true,
        ]);
    }

    #[Test]
    public function user_cannot_change_privacy_of_another_users_video(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/videos/{$video->id}", ['is_public' => false]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'is_public' => true,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_change_privacy(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/videos/{$video->id}", ['is_public' => false]);

        $response->assertStatus(401);
    }

    // ==========================================
    // DELETE VIDEO
    // ==========================================

    #[Test]
    public function user_can_delete_their_own_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Video deleted successfully']);

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);

        $this->user->refresh();
        $this->assertEquals(4, $this->user->videos_count);
    }

    #[Test]
    public function delete_bunny_video_calls_bunny_service(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        $bunnyMock = Mockery::mock(BunnyStreamService::class);
        $bunnyMock->shouldReceive('deleteVideo')
            ->once()
            ->with($video->bunny_video_id);
        $this->app->instance(BunnyStreamService::class, $bunnyMock);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    #[Test]
    public function delete_local_video_does_not_call_bunny_service(): void
    {
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'storage_type' => 'local',
        ]);

        $bunnyMock = Mockery::mock(BunnyStreamService::class);
        $bunnyMock->shouldNotReceive('deleteVideo');
        $this->app->instance(BunnyStreamService::class, $bunnyMock);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    #[Test]
    public function delete_continues_even_if_bunny_deletion_fails(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        $bunnyMock = Mockery::mock(BunnyStreamService::class);
        $bunnyMock->shouldReceive('deleteVideo')
            ->once()
            ->andThrow(new \Exception('Bunny API down'));
        $this->app->instance(BunnyStreamService::class, $bunnyMock);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    #[Test]
    public function user_cannot_delete_another_users_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_delete_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    // ==========================================
    // BUNNY WEBHOOK STATUS REGRESSION
    // ==========================================

    #[Test]
    public function webhook_does_not_regress_ready_status(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        // Simulate out-of-order webhook: Bunny sends 'uploaded' (status=1) after 'ready'
        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => $video->bunny_video_id,
            'Status' => 1, // uploaded
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('ready', $video->bunny_status);
    }

    #[Test]
    public function webhook_does_not_regress_ready_to_transcoding(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => $video->bunny_video_id,
            'Status' => 3, // transcoding
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('ready', $video->bunny_status);
    }

    #[Test]
    public function webhook_allows_error_to_override_ready(): void
    {
        $video = Video::factory()->bunnyReady()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => $video->bunny_video_id,
            'Status' => 5, // error
            'ErrorMessage' => 'Something went wrong',
        ]);

        $response->assertStatus(200);

        $video->refresh();
        $this->assertEquals('error', $video->bunny_status);
        $this->assertEquals('Something went wrong', $video->bunny_error);
    }

    #[Test]
    public function webhook_updates_processing_to_ready_with_metadata(): void
    {
        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => $video->bunny_video_id,
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
}

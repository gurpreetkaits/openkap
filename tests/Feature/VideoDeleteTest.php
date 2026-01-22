<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['videos_count' => 3]);
        $this->otherUser = User::factory()->create(['videos_count' => 2]);
    }

    #[Test]
    public function user_can_delete_their_own_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Video deleted successfully']);

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    #[Test]
    public function user_cannot_delete_another_users_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Unauthorized']);

        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    #[Test]
    public function deleting_video_decrements_user_video_count(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);
        $initialCount = $this->user->videos_count;

        $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$video->id}");

        $this->user->refresh();
        $this->assertEquals($initialCount - 1, $this->user->videos_count);
    }

    #[Test]
    public function unauthenticated_user_cannot_delete_video(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    // Bulk Delete Tests

    #[Test]
    public function user_can_bulk_delete_their_own_videos(): void
    {
        $videos = Video::factory()->count(3)->create(['user_id' => $this->user->id]);
        $videoIds = $videos->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson('/api/videos/bulk-delete', [
                'video_ids' => $videoIds,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['deleted' => 3]);

        foreach ($videoIds as $id) {
            $this->assertDatabaseMissing('videos', ['id' => $id]);
        }
    }

    #[Test]
    public function bulk_delete_only_deletes_users_own_videos(): void
    {
        $userVideos = Video::factory()->count(2)->create(['user_id' => $this->user->id]);
        $otherVideos = Video::factory()->count(2)->create(['user_id' => $this->otherUser->id]);

        $allIds = $userVideos->pluck('id')
            ->merge($otherVideos->pluck('id'))
            ->toArray();

        $response = $this->actingAs($this->user)
            ->postJson('/api/videos/bulk-delete', [
                'video_ids' => $allIds,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['deleted' => 2]);

        // User's videos should be deleted
        foreach ($userVideos as $video) {
            $this->assertDatabaseMissing('videos', ['id' => $video->id]);
        }

        // Other user's videos should still exist
        foreach ($otherVideos as $video) {
            $this->assertDatabaseHas('videos', ['id' => $video->id]);
        }
    }

    #[Test]
    public function bulk_delete_decrements_user_video_count_correctly(): void
    {
        $videos = Video::factory()->count(2)->create(['user_id' => $this->user->id]);
        $initialCount = $this->user->videos_count;

        $this->actingAs($this->user)
            ->postJson('/api/videos/bulk-delete', [
                'video_ids' => $videos->pluck('id')->toArray(),
            ]);

        $this->user->refresh();
        $this->assertEquals($initialCount - 2, $this->user->videos_count);
    }

    #[Test]
    public function bulk_delete_requires_video_ids(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/videos/bulk-delete', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video_ids']);
    }

    #[Test]
    public function bulk_delete_requires_at_least_one_video(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/videos/bulk-delete', [
                'video_ids' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video_ids']);
    }

    #[Test]
    public function bulk_delete_validates_video_ids_exist(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/videos/bulk-delete', [
                'video_ids' => [99999, 99998],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video_ids.0', 'video_ids.1']);
    }

    #[Test]
    public function unauthenticated_user_cannot_bulk_delete(): void
    {
        $videos = Video::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/videos/bulk-delete', [
            'video_ids' => $videos->pluck('id')->toArray(),
        ]);

        $response->assertStatus(401);

        foreach ($videos as $video) {
            $this->assertDatabaseHas('videos', ['id' => $video->id]);
        }
    }
}

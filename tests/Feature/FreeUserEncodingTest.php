<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FreeUserEncodingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Bunny disabled - encoding costs too high');
    }

    #[Test]
    public function free_user_cannot_use_bunny_encoding(): void
    {
        $freeUser = User::factory()->free()->create();

        $response = $this->actingAs($freeUser)
            ->postJson('/api/bunny/videos/create', [
                'title' => 'Test Video',
            ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'error' => 'subscription_required',
                'use_local_storage' => true,
            ]);
    }

    #[Test]
    public function pro_user_can_use_bunny_encoding(): void
    {
        $proUser = User::factory()->withProSubscription()->create();

        // Note: This will fail with 503 if Bunny is not configured,
        // which is expected in tests. The point is it doesn't return
        // the subscription_required error.
        $response = $this->actingAs($proUser)
            ->postJson('/api/bunny/videos/create', [
                'title' => 'Test Video',
            ]);

        // If Bunny is configured, it would return 201
        // If not configured, it returns 503 (bunny_not_configured)
        // It should NOT return 403 subscription_required
        $this->assertNotEquals(403, $response->status());
    }

    #[Test]
    public function free_user_gets_local_storage_on_stream_upload(): void
    {
        $freeUser = User::factory()->free()->create();

        $response = $this->actingAs($freeUser)
            ->postJson('/api/stream/start', [
                'title' => 'Test Recording',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'will_use_bunny' => false,
                'is_free_account' => true,
            ]);
    }

    #[Test]
    public function pro_user_should_encode_videos(): void
    {
        $proUser = User::factory()->withProSubscription()->create();

        $this->assertTrue($proUser->shouldEncodeVideos());
    }

    #[Test]
    public function free_user_should_not_encode_videos(): void
    {
        $freeUser = User::factory()->free()->create();

        $this->assertFalse($freeUser->shouldEncodeVideos());
    }

    #[Test]
    public function free_user_can_still_upload_videos(): void
    {
        $freeUser = User::factory()->free()->create();

        $response = $this->actingAs($freeUser)
            ->postJson('/api/stream/start', [
                'title' => 'Test Recording',
            ]);

        // Free users can start upload, just won't get Bunny encoding
        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'storage_type',
                'will_use_bunny',
            ]);
    }

    #[Test]
    public function free_user_cannot_exceed_video_limit(): void
    {
        // Set the free video limit to 2 for this test
        Setting::updateOrCreate(
            ['key' => 'free_video_limit'],
            ['value' => '2', 'type' => 'integer']
        );

        $freeUser = User::factory()->free()->create();

        // Create actual videos to reach the limit
        Video::factory()->count(2)->create(['user_id' => $freeUser->id]);

        $response = $this->actingAs($freeUser)
            ->postJson('/api/stream/start', [
                'title' => 'Test Recording',
            ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'error' => 'video_limit_reached',
            ]);
    }
}

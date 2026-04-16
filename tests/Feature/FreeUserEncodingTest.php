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

    }

    #[Test]
    public function free_user_can_use_bunny_encoding(): void
    {
        $freeUser = User::factory()->free()->create();

        // Bunny is available to all users — free users get the same
        // encoding as pro users. The only gate is video quota.
        $response = $this->actingAs($freeUser)
            ->postJson('/api/bunny/videos/create', [
                'title' => 'Test Video',
            ]);

        // If Bunny is configured, it would return 201
        // If not configured, it returns 503 (bunny_not_configured)
        // It should NOT return 403 subscription_required
        $this->assertNotEquals(403, $response->status());
    }

    #[Test]
    public function pro_user_can_use_bunny_encoding(): void
    {
        $proUser = User::factory()->withProSubscription()->create();

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
    public function pro_user_should_encode_videos(): void
    {
        $proUser = User::factory()->withProSubscription()->create();

        $this->assertTrue($proUser->shouldEncodeVideos());
    }

    #[Test]
    public function free_user_can_still_upload_videos(): void
    {
        $freeUser = User::factory()->free()->create();

        $response = $this->actingAs($freeUser)
            ->postJson('/api/stream/start', [
                'title' => 'Test Recording',
            ]);

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

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UploadQuotaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reports_upload_quota_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/videos/quota');

        $response->assertOk()
            ->assertJsonStructure(['quota' => [
                'can_upload',
                'plan_type',
                'videos_count',
                'max_videos',
                'remaining_video_quota',
                'monthly_minutes_exceeded',
                'upgrade_url',
            ]])
            ->assertJsonPath('quota.videos_count', 0);
    }

    #[Test]
    public function the_quota_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/videos/quota')->assertStatus(401);
    }
}

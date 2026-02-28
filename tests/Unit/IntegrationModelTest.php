<?php

namespace Tests\Unit;

use App\Models\Integration;
use App\Models\IntegrationAction;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntegrationModelTest extends TestCase
{
    use RefreshDatabase;

    // ==================
    // Integration Model Tests
    // ==================

    #[Test]
    public function token_fields_are_hidden_from_serialization(): void
    {
        $integration = Integration::factory()->create();

        $array = $integration->toArray();

        $this->assertArrayNotHasKey('access_token', $array);
        $this->assertArrayNotHasKey('refresh_token', $array);
    }

    #[Test]
    public function is_expired_returns_true_when_token_expires_at_is_past(): void
    {
        $integration = Integration::factory()->create([
            'token_expires_at' => now()->subHour(),
        ]);

        $this->assertTrue($integration->isExpired());
    }

    #[Test]
    public function is_expired_returns_false_when_token_expires_at_is_null(): void
    {
        $integration = Integration::factory()->create([
            'token_expires_at' => null,
        ]);

        $this->assertFalse($integration->isExpired());
    }

    #[Test]
    public function is_expired_returns_false_when_token_expires_at_is_future(): void
    {
        $integration = Integration::factory()->create([
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($integration->isExpired());
    }

    #[Test]
    public function is_active_returns_true_for_active_status(): void
    {
        $integration = Integration::factory()->create(['status' => 'active']);

        $this->assertTrue($integration->isActive());
    }

    #[Test]
    public function is_active_returns_false_for_non_active_status(): void
    {
        $expired = Integration::factory()->expired()->create();
        $revoked = Integration::factory()->revoked()->create();

        $this->assertFalse($expired->isActive());
        $this->assertFalse($revoked->isActive());
    }

    #[Test]
    public function needs_refresh_returns_true_when_expiring_within_five_minutes(): void
    {
        $integration = Integration::factory()->create([
            'token_expires_at' => now()->addMinutes(3),
        ]);

        $this->assertTrue($integration->needsRefresh());
    }

    #[Test]
    public function needs_refresh_returns_false_when_token_expires_at_is_null(): void
    {
        $integration = Integration::factory()->create([
            'token_expires_at' => null,
        ]);

        $this->assertFalse($integration->needsRefresh());
    }

    #[Test]
    public function needs_refresh_returns_false_when_expiry_is_far_future(): void
    {
        $integration = Integration::factory()->create([
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($integration->needsRefresh());
    }

    #[Test]
    public function integration_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $integration->user);
        $this->assertEquals($user->id, $integration->user->id);
    }

    #[Test]
    public function integration_has_many_actions(): void
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create(['user_id' => $user->id]);

        IntegrationAction::factory()->count(3)->create([
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $integration->actions);
        $this->assertInstanceOf(IntegrationAction::class, $integration->actions->first());
    }

    #[Test]
    public function metadata_is_cast_to_array(): void
    {
        $integration = Integration::factory()->create([
            'metadata' => ['team_id' => 'T123', 'team_name' => 'Test'],
        ]);

        $integration->refresh();

        $this->assertIsArray($integration->metadata);
        $this->assertEquals('T123', $integration->metadata['team_id']);
    }

    #[Test]
    public function token_expires_at_is_cast_to_datetime(): void
    {
        $integration = Integration::factory()->create([
            'token_expires_at' => '2026-06-01 12:00:00',
        ]);

        $integration->refresh();

        $this->assertInstanceOf(Carbon::class, $integration->token_expires_at);
    }

    // ==================
    // IntegrationAction Model Tests
    // ==================

    #[Test]
    public function action_belongs_to_integration(): void
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create(['user_id' => $user->id]);
        $action = IntegrationAction::factory()->create([
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Integration::class, $action->integration);
        $this->assertEquals($integration->id, $action->integration->id);
    }

    #[Test]
    public function action_belongs_to_video(): void
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create(['user_id' => $user->id]);
        $action = IntegrationAction::factory()->create([
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Video::class, $action->video);
        $this->assertEquals($video->id, $action->video->id);
    }

    #[Test]
    public function action_request_data_is_cast_to_array(): void
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create(['user_id' => $user->id]);
        $action = IntegrationAction::factory()->create([
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $user->id,
            'request_data' => ['target_id' => 'C123', 'message' => 'Hello'],
        ]);

        $action->refresh();

        $this->assertIsArray($action->request_data);
        $this->assertEquals('C123', $action->request_data['target_id']);
    }

    #[Test]
    public function action_response_data_is_cast_to_array(): void
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create(['user_id' => $user->id]);
        $action = IntegrationAction::factory()->create([
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $user->id,
            'response_data' => ['external_id' => 'msg-123'],
        ]);

        $action->refresh();

        $this->assertIsArray($action->response_data);
        $this->assertEquals('msg-123', $action->response_data['external_id']);
    }

    // ==================
    // User Relationship Test
    // ==================

    #[Test]
    public function user_has_many_integrations(): void
    {
        $user = User::factory()->create();
        Integration::factory()->create(['user_id' => $user->id, 'provider' => 'slack']);
        Integration::factory()->create(['user_id' => $user->id, 'provider' => 'trello']);

        $this->assertCount(2, $user->integrations);
        $this->assertInstanceOf(Integration::class, $user->integrations->first());
    }
}

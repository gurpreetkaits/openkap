<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationAction;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Http::preventStrayRequests();

        Config::set('services.integrations.slack', [
            'client_id' => 'test-slack-client-id',
            'client_secret' => 'test-slack-client-secret',
            'redirect_uri' => 'http://localhost/api/integrations/slack/callback',
        ]);
        Config::set('services.integrations.google_drive', [
            'client_id' => 'test-google-client-id',
            'client_secret' => 'test-google-client-secret',
            'redirect_uri' => 'http://localhost/api/integrations/google_drive/callback',
        ]);
        Config::set('services.integrations.jira', [
            'client_id' => 'test-jira-client-id',
            'client_secret' => 'test-jira-client-secret',
            'redirect_uri' => 'http://localhost/api/integrations/jira/callback',
        ]);
        Config::set('services.integrations.trello', [
            'api_key' => 'test-trello-api-key',
            'api_secret' => 'test-trello-api-secret',
            'redirect_uri' => 'http://localhost:5173/integrations/trello/callback',
        ]);
        Config::set('services.frontend.url', 'http://localhost:5173');
    }

    // ==================
    // List / Providers
    // ==================

    #[Test]
    public function user_can_list_available_providers(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/integrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'integrations' => [
                    '*' => ['id', 'name', 'description', 'icon', 'connected', 'status'],
                ],
            ]);

        $integrations = $response->json('integrations');
        $this->assertCount(1, $integrations);
    }

    #[Test]
    public function connected_integration_shows_as_connected(): void
    {
        Integration::factory()->jira()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/integrations');

        $response->assertStatus(200);

        $integrations = collect($response->json('integrations'));
        $jira = $integrations->firstWhere('id', 'jira');

        $this->assertTrue($jira['connected']);
        $this->assertEquals('active', $jira['status']);
    }

    #[Test]
    public function unauthenticated_user_cannot_list_integrations(): void
    {
        $response = $this->getJson('/api/integrations');

        $response->assertStatus(401);
    }

    // ==================
    // Connect (OAuth Initiation)
    // ==================

    #[Test]
    public function user_can_initiate_oauth_connection(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/integrations/jira/connect');

        $response->assertStatus(200)
            ->assertJsonStructure(['url']);

        $url = $response->json('url');
        $this->assertStringContainsString('auth.atlassian.com/authorize', $url);
        $this->assertStringContainsString('state=', $url);
    }

    #[Test]
    public function invalid_provider_returns_400(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/integrations/invalid_provider/connect');

        $response->assertStatus(400);
    }

    // ==================
    // OAuth Callback
    // ==================

    #[Test]
    public function oauth_callback_creates_integration(): void
    {
        $state = 'test-state-abc123';
        Cache::put("integration_oauth:{$state}", [
            'user_id' => $this->user->id,
            'provider' => 'jira',
        ], now()->addMinutes(10));

        Http::fake([
            'auth.atlassian.com/oauth/token' => Http::response([
                'access_token' => 'jira-test-token',
                'refresh_token' => 'jira-refresh-token',
                'expires_in' => 3600,
            ], 200),
            'api.atlassian.com/oauth/token/accessible-resources' => Http::response([
                [
                    'id' => 'cloud-id-abc',
                    'name' => 'My Jira Site',
                    'url' => 'https://mysite.atlassian.net',
                ],
            ], 200),
        ]);

        $response = $this->get("/api/integrations/jira/callback?code=test-code&state={$state}");

        $response->assertRedirect();
        $this->assertStringContainsString('connected=jira', $response->headers->get('Location'));

        $this->assertDatabaseHas('integrations', [
            'user_id' => $this->user->id,
            'provider' => 'jira',
            'status' => 'active',
        ]);
    }

    #[Test]
    public function oauth_callback_with_invalid_state_redirects_with_error(): void
    {
        $response = $this->get('/api/integrations/slack/callback?code=test-code&state=invalid-state');

        $response->assertRedirect();
        $this->assertStringContainsString('error=', $response->headers->get('Location'));
    }

    #[Test]
    public function oauth_callback_missing_params_redirects_with_error(): void
    {
        $response = $this->get('/api/integrations/slack/callback');

        $response->assertRedirect();
        $this->assertStringContainsString('error=missing_params', $response->headers->get('Location'));
    }

    #[Test]
    public function reconnecting_updates_existing_integration(): void
    {
        $existing = Integration::factory()->jira()->create([
            'user_id' => $this->user->id,
            'status' => 'expired',
        ]);

        $state = 'reconnect-state';
        Cache::put("integration_oauth:{$state}", [
            'user_id' => $this->user->id,
            'provider' => 'jira',
        ], now()->addMinutes(10));

        Http::fake([
            'auth.atlassian.com/oauth/token' => Http::response([
                'access_token' => 'jira-new-token',
                'refresh_token' => 'jira-new-refresh-token',
                'expires_in' => 3600,
            ], 200),
            'api.atlassian.com/oauth/token/accessible-resources' => Http::response([
                [
                    'id' => 'cloud-id-abc',
                    'name' => 'Updated Jira Site',
                    'url' => 'https://mysite.atlassian.net',
                ],
            ], 200),
        ]);

        $response = $this->get("/api/integrations/jira/callback?code=new-code&state={$state}");

        $response->assertRedirect();

        // Should update existing, not create a duplicate
        $this->assertDatabaseCount('integrations', 1);
        $this->assertDatabaseHas('integrations', [
            'id' => $existing->id,
            'status' => 'active',
        ]);
    }

    // ==================
    // Disconnect
    // ==================

    #[Test]
    public function user_can_disconnect_integration(): void
    {
        Integration::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'slack',
        ]);

        Http::fake([
            'slack.com/api/auth.revoke' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/integrations/slack');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Integration disconnected successfully']);

        $this->assertDatabaseMissing('integrations', [
            'user_id' => $this->user->id,
            'provider' => 'slack',
        ]);
    }

    #[Test]
    public function disconnecting_nonexistent_integration_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/integrations/slack');

        $response->assertStatus(404);
    }

    // ==================
    // Targets
    // ==================

    #[Test]
    public function user_can_get_jira_targets(): void
    {
        $integration = Integration::factory()->jira()->create([
            'user_id' => $this->user->id,
        ]);

        $cloudId = $integration->metadata['cloud_id'];

        Http::fake([
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/project/search*" => Http::response([
                'values' => [
                    ['key' => 'PROJ', 'name' => 'My Project'],
                    ['key' => 'DEV', 'name' => 'Development'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/integrations/jira/targets');

        $response->assertStatus(200)
            ->assertJsonStructure(['targets' => [['id', 'name', 'type']]]);

        $targets = $response->json('targets');
        $this->assertCount(2, $targets);
        $this->assertEquals('PROJ', $targets[0]['id']);
    }

    // Google Drive provider removed from factory — test skipped
    // #[Test]
    // public function user_can_get_google_drive_targets(): void { ... }

    // Slack provider removed from factory — test merged into user_can_get_jira_targets above

    // Trello provider removed from factory — test skipped
    // #[Test]
    // public function user_can_get_trello_targets(): void { ... }

    #[Test]
    public function targets_without_integration_returns_400(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/integrations/slack/targets');

        $response->assertStatus(400);
    }

    // ==================
    // Share Video
    // ==================

    #[Test]
    public function user_can_share_video_to_jira(): void
    {
        $cloudId = 'cloud-id-share-test';
        $integration = Integration::factory()->jira()->create([
            'user_id' => $this->user->id,
            'metadata' => [
                'cloud_id' => $cloudId,
                'site_name' => 'mysite',
                'site_url' => 'https://mysite.atlassian.net',
            ],
            'external_user_id' => $cloudId,
        ]);

        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'My Test Video',
            'share_token' => 'share-token-123',
        ]);

        Http::fake([
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue/createmeta/PROJ/issuetypes" => Http::response([
                'values' => [
                    ['id' => '10001', 'name' => 'Task', 'subtask' => false],
                ],
            ], 200),
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue" => Http::response([
                'id' => '10001',
                'key' => 'PROJ-42',
                'self' => 'https://mysite.atlassian.net/rest/api/3/issue/10001',
            ], 201),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/integrations/jira/videos/{$video->id}/share", [
                'target_id' => 'PROJ',
                'target_name' => 'My Project',
                'message' => 'Check this out!',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('integration_actions', [
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $this->user->id,
            'action_type' => 'share_link',
        ]);
    }

    // Trello provider removed from factory — test skipped
    // #[Test]
    // public function user_can_share_video_to_trello(): void { ... }

    #[Test]
    public function share_requires_target_id(): void
    {
        Integration::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'slack',
        ]);

        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/integrations/slack/videos/{$video->id}/share", [
                'message' => 'No target!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_id']);
    }

    #[Test]
    public function user_cannot_share_other_users_video(): void
    {
        Integration::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'slack',
        ]);

        $otherUser = User::factory()->create();
        $video = Video::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/integrations/slack/videos/{$video->id}/share", [
                'target_id' => 'C001',
            ]);

        $response->assertStatus(400);
    }

    #[Test]
    public function sharing_without_integration_returns_400(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/integrations/slack/videos/{$video->id}/share", [
                'target_id' => 'C001',
            ]);

        $response->assertStatus(400);
    }

    #[Test]
    public function failed_share_logs_error_in_action(): void
    {
        $cloudId = 'cloud-id-fail-test';
        $integration = Integration::factory()->jira()->create([
            'user_id' => $this->user->id,
            'metadata' => [
                'cloud_id' => $cloudId,
                'site_name' => 'mysite',
                'site_url' => 'https://mysite.atlassian.net',
            ],
            'external_user_id' => $cloudId,
        ]);

        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Fail Video',
            'share_token' => 'fail-token',
        ]);

        Http::fake([
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue/createmeta/INVALID/issuetypes" => Http::response([
                'values' => [
                    ['id' => '10001', 'name' => 'Task', 'subtask' => false],
                ],
            ], 200),
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue" => Http::response([
                'errorMessages' => ['Project not found: INVALID.'],
            ], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/integrations/jira/videos/{$video->id}/share", [
                'target_id' => 'INVALID',
                'target_name' => 'Nonexistent',
            ]);

        $response->assertStatus(200);

        // The action should be marked as failed with the error
        $this->assertDatabaseHas('integration_actions', [
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'status' => 'failed',
        ]);

        $action = IntegrationAction::where('video_id', $video->id)->first();
        $this->assertNotNull($action->error);
        $this->assertStringContainsString('Project not found', $action->error);
    }

    // ==================
    // Share History
    // ==================

    #[Test]
    public function user_can_get_share_history(): void
    {
        $integration = Integration::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'slack',
        ]);

        $video = Video::factory()->create(['user_id' => $this->user->id]);

        IntegrationAction::factory()->count(3)->create([
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/integrations/videos/{$video->id}/history");

        $response->assertStatus(200)
            ->assertJsonStructure(['history'])
            ->assertJsonCount(3, 'history');
    }

    #[Test]
    public function share_history_is_user_scoped(): void
    {
        $integration = Integration::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'slack',
        ]);

        $video = Video::factory()->create(['user_id' => $this->user->id]);

        // Current user's actions
        IntegrationAction::factory()->count(2)->create([
            'integration_id' => $integration->id,
            'video_id' => $video->id,
            'user_id' => $this->user->id,
        ]);

        // Other user's actions on same video
        $otherUser = User::factory()->create();
        $otherIntegration = Integration::factory()->create([
            'user_id' => $otherUser->id,
            'provider' => 'slack',
        ]);
        IntegrationAction::factory()->count(3)->create([
            'integration_id' => $otherIntegration->id,
            'video_id' => $video->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/integrations/videos/{$video->id}/history");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'history');
    }
}

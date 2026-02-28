<?php

namespace Tests\Feature;

use App\Data\IntegrationShareResultData;
use App\Data\IntegrationTokenData;
use App\Models\Integration;
use App\Models\User;
use App\Models\Video;
use App\Services\Integrations\Providers\GoogleDriveProvider;
use App\Services\Integrations\Providers\JiraProvider;
use App\Services\Integrations\Providers\SlackProvider;
use App\Services\Integrations\Providers\TrelloProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntegrationProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
    // Slack Provider
    // ==================

    #[Test]
    public function slack_auth_url_contains_required_params(): void
    {
        $provider = app(SlackProvider::class);
        $url = $provider->getAuthorizationUrl('test-state');

        $this->assertStringContainsString('slack.com/oauth/v2/authorize', $url);
        $this->assertStringContainsString('client_id=test-slack-client-id', $url);
        $this->assertStringContainsString('state=test-state', $url);
        $this->assertStringContainsString('scope=', $url);
    }

    #[Test]
    public function slack_callback_returns_token_data(): void
    {
        Http::fake([
            'slack.com/api/oauth.v2.access' => Http::response([
                'ok' => true,
                'access_token' => 'xoxb-slack-token',
                'authed_user' => ['id' => 'U123'],
                'team' => ['id' => 'T456', 'name' => 'Test Team'],
                'bot_user_id' => 'B789',
            ], 200),
        ]);

        $provider = app(SlackProvider::class);
        $result = $provider->handleCallback('test-code');

        $this->assertInstanceOf(IntegrationTokenData::class, $result);
        $this->assertEquals('xoxb-slack-token', $result->access_token);
        $this->assertNull($result->refresh_token);
        $this->assertNull($result->expires_in);
        $this->assertEquals('U123', $result->external_user_id);
        $this->assertEquals('Test Team', $result->external_user_name);
        $this->assertEquals('T456', $result->metadata['team_id']);
    }

    #[Test]
    public function slack_share_posts_message_successfully(): void
    {
        Http::fake([
            'slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'ts' => '1234567890.123456',
                'channel' => 'C001',
            ], 200),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Video',
            'share_token' => 'test-share-token',
        ]);

        $provider = app(SlackProvider::class);
        $result = $provider->shareVideo($integration, $video, [
            'target_id' => 'C001',
            'message' => 'Check this out',
        ]);

        $this->assertInstanceOf(IntegrationShareResultData::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('1234567890.123456', $result->external_id);
        $this->assertNull($result->error);
    }

    #[Test]
    public function slack_share_handles_api_error(): void
    {
        Http::fake([
            'slack.com/api/chat.postMessage' => Http::response([
                'ok' => false,
                'error' => 'channel_not_found',
            ], 200),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'share_token' => 'test-token',
        ]);

        $provider = app(SlackProvider::class);
        $result = $provider->shareVideo($integration, $video, [
            'target_id' => 'INVALID',
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('channel_not_found', $result->error);
    }

    #[Test]
    public function slack_get_targets_returns_channels(): void
    {
        Http::fake([
            'slack.com/api/conversations.list*' => Http::response([
                'ok' => true,
                'channels' => [
                    ['id' => 'C001', 'name' => 'general', 'is_private' => false],
                    ['id' => 'C002', 'name' => 'secret', 'is_private' => true],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);

        $provider = app(SlackProvider::class);
        $targets = $provider->getAvailableTargets($integration);

        $this->assertCount(2, $targets);
        $this->assertEquals('#general', $targets[0]['name']);
        $this->assertEquals('public_channel', $targets[0]['type']);
        $this->assertEquals('#secret', $targets[1]['name']);
        $this->assertEquals('private_channel', $targets[1]['type']);
    }

    // ==================
    // Google Drive Provider
    // ==================

    #[Test]
    public function google_drive_auth_url_has_offline_access(): void
    {
        $provider = app(GoogleDriveProvider::class);
        $url = $provider->getAuthorizationUrl('test-state');

        $this->assertStringContainsString('accounts.google.com/o/oauth2', $url);
        $this->assertStringContainsString('access_type=offline', $url);
        $this->assertStringContainsString('state=test-state', $url);
        $this->assertStringContainsString('prompt=consent', $url);
    }

    #[Test]
    public function google_drive_callback_returns_token_and_user_info(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
                'refresh_token' => 'google-refresh-token',
                'expires_in' => 3600,
            ], 200),
            'www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'id' => 'google-user-123',
                'email' => 'test@gmail.com',
            ], 200),
        ]);

        $provider = app(GoogleDriveProvider::class);
        $result = $provider->handleCallback('test-code');

        $this->assertInstanceOf(IntegrationTokenData::class, $result);
        $this->assertEquals('google-access-token', $result->access_token);
        $this->assertEquals('google-refresh-token', $result->refresh_token);
        $this->assertEquals(3600, $result->expires_in);
        $this->assertEquals('google-user-123', $result->external_user_id);
        $this->assertEquals('test@gmail.com', $result->external_user_name);
    }

    #[Test]
    public function google_drive_refresh_token_works(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-google-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $provider = app(GoogleDriveProvider::class);
        $result = $provider->refreshToken('old-refresh-token');

        $this->assertInstanceOf(IntegrationTokenData::class, $result);
        $this->assertEquals('new-google-access-token', $result->access_token);
        $this->assertNull($result->refresh_token);
        $this->assertEquals(3600, $result->expires_in);
    }

    #[Test]
    public function google_drive_get_targets_returns_folders(): void
    {
        Http::fake([
            'www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    ['id' => 'folder-1', 'name' => 'Documents'],
                    ['id' => 'folder-2', 'name' => 'Screenshots'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->googleDrive()->create([
            'user_id' => $user->id,
        ]);

        $provider = app(GoogleDriveProvider::class);
        $targets = $provider->getAvailableTargets($integration);

        // Root + 2 folders
        $this->assertCount(3, $targets);
        $this->assertEquals('root', $targets[0]['id']);
        $this->assertEquals('My Drive (root)', $targets[0]['name']);
        $this->assertEquals('Documents', $targets[1]['name']);
    }

    // ==================
    // Jira Provider
    // ==================

    #[Test]
    public function jira_callback_discovers_cloud_id(): void
    {
        Http::fake([
            'auth.atlassian.com/oauth/token' => Http::response([
                'access_token' => 'jira-access-token',
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

        $provider = app(JiraProvider::class);
        $result = $provider->handleCallback('test-code');

        $this->assertInstanceOf(IntegrationTokenData::class, $result);
        $this->assertEquals('jira-access-token', $result->access_token);
        $this->assertEquals('jira-refresh-token', $result->refresh_token);
        $this->assertEquals('cloud-id-abc', $result->external_user_id);
        $this->assertEquals('My Jira Site', $result->external_user_name);
        $this->assertEquals('cloud-id-abc', $result->metadata['cloud_id']);
        $this->assertEquals('https://mysite.atlassian.net', $result->metadata['site_url']);
    }

    #[Test]
    public function jira_share_creates_issue(): void
    {
        $cloudId = 'cloud-id-xyz';

        Http::fake([
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue/createmeta/PROJ/issuetypes" => Http::response([
                'values' => [
                    ['id' => '10001', 'name' => 'Task', 'subtask' => false],
                    ['id' => '10002', 'name' => 'Bug', 'subtask' => false],
                ],
            ], 200),
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue" => Http::response([
                'id' => '10001',
                'key' => 'PROJ-42',
                'self' => 'https://mysite.atlassian.net/rest/api/3/issue/10001',
            ], 201),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->jira()->create([
            'user_id' => $user->id,
            'metadata' => [
                'cloud_id' => $cloudId,
                'site_name' => 'mysite',
                'site_url' => 'https://mysite.atlassian.net',
            ],
            'external_user_id' => $cloudId,
        ]);
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'title' => 'Bug Report Video',
            'share_token' => 'jira-share-token',
        ]);

        $provider = app(JiraProvider::class);
        $result = $provider->shareVideo($integration, $video, [
            'target_id' => 'PROJ',
            'message' => 'See this bug',
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals('PROJ-42', $result->external_id);
        $this->assertStringContainsString('PROJ-42', $result->external_url);
    }

    #[Test]
    public function jira_get_targets_returns_projects(): void
    {
        $cloudId = 'cloud-id-test';

        Http::fake([
            "api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/project/search*" => Http::response([
                'values' => [
                    ['key' => 'PROJ', 'name' => 'My Project'],
                    ['key' => 'DEV', 'name' => 'Development'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->jira()->create([
            'user_id' => $user->id,
            'metadata' => [
                'cloud_id' => $cloudId,
                'site_name' => 'mysite',
                'site_url' => 'https://mysite.atlassian.net',
            ],
            'external_user_id' => $cloudId,
        ]);

        $provider = app(JiraProvider::class);
        $targets = $provider->getAvailableTargets($integration);

        $this->assertCount(2, $targets);
        $this->assertEquals('PROJ', $targets[0]['id']);
        $this->assertEquals('My Project (PROJ)', $targets[0]['name']);
        $this->assertEquals('project', $targets[0]['type']);
    }

    // ==================
    // Trello Provider
    // ==================

    #[Test]
    public function trello_auth_url_uses_api_key(): void
    {
        $provider = app(TrelloProvider::class);
        $url = $provider->getAuthorizationUrl('test-state');

        $this->assertStringContainsString('trello.com/1/authorize', $url);
        $this->assertStringContainsString('key=test-trello-api-key', $url);
        $this->assertStringContainsString('response_type=token', $url);
        // State is embedded in return_url param, not as a top-level query param
        $this->assertStringContainsString('test-state', $url);
    }

    #[Test]
    public function trello_callback_validates_token(): void
    {
        Http::fake([
            'api.trello.com/1/members/me*' => Http::response([
                'id' => 'trello-user-123',
                'fullName' => 'John Doe',
                'username' => 'johndoe',
            ], 200),
        ]);

        $provider = app(TrelloProvider::class);
        $result = $provider->handleCallback('trello-token-xyz');

        $this->assertInstanceOf(IntegrationTokenData::class, $result);
        $this->assertEquals('trello-token-xyz', $result->access_token);
        $this->assertNull($result->refresh_token);
        $this->assertNull($result->expires_in);
        $this->assertEquals('trello-user-123', $result->external_user_id);
        $this->assertEquals('John Doe', $result->external_user_name);
        $this->assertEquals('johndoe', $result->metadata['username']);
    }

    #[Test]
    public function trello_share_creates_card(): void
    {
        Http::fake([
            'api.trello.com/1/cards' => Http::response([
                'id' => 'card-abc',
                'shortUrl' => 'https://trello.com/c/abc123',
                'url' => 'https://trello.com/c/abc123/1-test-video',
            ], 200),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->trello()->create([
            'user_id' => $user->id,
        ]);
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Video',
            'share_token' => 'trello-share-token',
        ]);

        $provider = app(TrelloProvider::class);
        $result = $provider->shareVideo($integration, $video, [
            'target_id' => 'list-1',
            'message' => 'New recording',
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals('card-abc', $result->external_id);
        $this->assertEquals('https://trello.com/c/abc123', $result->external_url);
    }

    #[Test]
    public function trello_get_targets_returns_board_lists(): void
    {
        Http::fake([
            'api.trello.com/1/members/me/boards*' => Http::response([
                [
                    'id' => 'board-1',
                    'name' => 'Project Board',
                    'lists' => [
                        ['id' => 'list-1', 'name' => 'Backlog'],
                        ['id' => 'list-2', 'name' => 'In Progress'],
                    ],
                ],
                [
                    'id' => 'board-2',
                    'name' => 'Personal',
                    'lists' => [
                        ['id' => 'list-3', 'name' => 'Ideas'],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $integration = Integration::factory()->trello()->create([
            'user_id' => $user->id,
        ]);

        $provider = app(TrelloProvider::class);
        $targets = $provider->getAvailableTargets($integration);

        $this->assertCount(3, $targets);
        $this->assertEquals('Project Board / Backlog', $targets[0]['name']);
        $this->assertEquals('list', $targets[0]['type']);
        $this->assertEquals('Personal / Ideas', $targets[2]['name']);
    }
}

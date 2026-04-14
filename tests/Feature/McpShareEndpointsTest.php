<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for the public share API endpoints used by the OpenKap MCP server.
 * These endpoints require no authentication — anyone with a share token can access them.
 */
class McpShareEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Video $video;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Test Creator']);
        $this->video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'title' => 'Bug Report: Login Issue',
            'description' => 'Demonstrating the login bug on staging',
            'is_public' => true,
            'duration' => 185,
        ]);
    }

    // -------------------------------------------------------
    // GET /api/share/video/{token} — Video details + transcription
    // -------------------------------------------------------

    #[Test]
    public function shared_video_returns_basic_metadata(): void
    {
        $response = $this->getJson("/api/share/video/{$this->video->share_token}");

        $response->assertOk()
            ->assertJsonPath('video.title', 'Bug Report: Login Issue')
            ->assertJsonPath('video.description', 'Demonstrating the login bug on staging')
            ->assertJsonPath('video.duration', 185)
            ->assertJsonPath('video.user_name', 'Test Creator');
    }

    #[Test]
    public function shared_video_includes_transcription_when_available(): void
    {
        $video = Video::factory()->withHls()->withTranscription()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'transcription' => 'So if you look here, the login button does not respond.',
            'transcription_segments' => [
                ['start' => 0, 'end' => 5, 'text' => 'So if you look here,'],
                ['start' => 5, 'end' => 12, 'text' => 'the login button does not respond.'],
            ],
        ]);

        $response = $this->getJson("/api/share/video/{$video->share_token}");

        $response->assertOk()
            ->assertJsonPath('video.transcription', 'So if you look here, the login button does not respond.')
            ->assertJsonPath('video.transcription_segments.0.start', 0)
            ->assertJsonPath('video.transcription_segments.0.text', 'So if you look here,')
            ->assertJsonPath('video.transcription_segments.1.start', 5)
            ->assertJsonPath('video.transcription_segments.1.text', 'the login button does not respond.');
    }

    #[Test]
    public function shared_video_includes_summary_when_available(): void
    {
        $video = Video::factory()->withHls()->withTranscriptionAndSummary()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'summary' => 'User demonstrates a login bug on the staging server.',
        ]);

        $response = $this->getJson("/api/share/video/{$video->share_token}");

        $response->assertOk()
            ->assertJsonPath('video.summary', 'User demonstrates a login bug on the staging server.');
    }

    #[Test]
    public function shared_video_returns_null_transcription_when_not_generated(): void
    {
        $response = $this->getJson("/api/share/video/{$this->video->share_token}");

        $response->assertOk()
            ->assertJsonPath('video.transcription', null)
            ->assertJsonPath('video.transcription_segments', null);
    }

    #[Test]
    public function shared_video_includes_inline_comments(): void
    {
        $commenter = User::factory()->create(['name' => 'Jane Reviewer']);
        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $commenter->id,
            'content' => 'Same issue on my machine',
            'timestamp_seconds' => 45,
        ]);

        $response = $this->getJson("/api/share/video/{$this->video->share_token}");

        $response->assertOk();
        $comments = $response->json('video.comments');
        $this->assertCount(1, $comments);
        $this->assertEquals('Same issue on my machine', $comments[0]['content']);
        $this->assertEquals(45, $comments[0]['timestamp_seconds']);
    }

    #[Test]
    public function private_video_returns_403(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/share/video/{$video->share_token}");

        $response->assertStatus(403);
    }

    #[Test]
    public function expired_share_link_returns_403(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'share_expires_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/api/share/video/{$video->share_token}");

        $response->assertStatus(403);
    }

    #[Test]
    public function invalid_share_token_returns_404(): void
    {
        $response = $this->getJson('/api/share/video/nonexistent_token_12345');

        $response->assertStatus(404);
    }

    // -------------------------------------------------------
    // GET /api/share/video/{token}/comments — Comments endpoint
    // -------------------------------------------------------

    #[Test]
    public function comments_endpoint_returns_all_comments(): void
    {
        $commenter1 = User::factory()->create(['name' => 'Alice']);
        $commenter2 = User::factory()->create(['name' => 'Bob']);

        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $commenter1->id,
            'content' => 'The button is unresponsive at 0:45',
            'timestamp_seconds' => 45,
        ]);
        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $commenter2->id,
            'content' => 'I can reproduce this too',
            'timestamp_seconds' => 60,
        ]);

        $response = $this->getJson("/api/share/video/{$this->video->share_token}/comments");

        $response->assertOk();
        $comments = $response->json('comments');
        $this->assertCount(2, $comments);
    }

    #[Test]
    public function comments_endpoint_returns_empty_array_when_no_comments(): void
    {
        $response = $this->getJson("/api/share/video/{$this->video->share_token}/comments");

        $response->assertOk();
        $comments = $response->json('comments');
        $this->assertCount(0, $comments);
    }

    #[Test]
    public function comments_include_author_name_and_timestamp(): void
    {
        $commenter = User::factory()->create(['name' => 'Charlie']);
        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $commenter->id,
            'content' => 'Check the network tab',
            'timestamp_seconds' => 120,
        ]);

        $response = $this->getJson("/api/share/video/{$this->video->share_token}/comments");

        $response->assertOk();
        $comment = $response->json('comments.0');
        $this->assertEquals('Charlie', $comment['author_name']);
        $this->assertEquals(120, $comment['timestamp_seconds']);
        $this->assertEquals('Check the network tab', $comment['content']);
    }

    #[Test]
    public function anonymous_comments_show_author_name(): void
    {
        Comment::factory()->anonymous()->create([
            'video_id' => $this->video->id,
            'author_name' => 'Anonymous QA',
            'content' => 'Found another edge case',
            'timestamp_seconds' => 90,
        ]);

        $response = $this->getJson("/api/share/video/{$this->video->share_token}/comments");

        $response->assertOk();
        $comment = $response->json('comments.0');
        $this->assertEquals('Anonymous QA', $comment['author_name']);
    }

    #[Test]
    public function threaded_comments_include_replies(): void
    {
        $commenter = User::factory()->create(['name' => 'Alice']);
        $parent = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $commenter->id,
            'content' => 'What browser is this?',
        ]);

        $replier = User::factory()->create(['name' => 'Bob']);
        Comment::factory()->asReply($parent)->create([
            'user_id' => $replier->id,
            'content' => 'Chrome 120 on macOS',
        ]);

        $response = $this->getJson("/api/share/video/{$this->video->share_token}/comments");

        $response->assertOk();
        $comments = $response->json('comments');
        // Top-level comments only (replies are nested)
        $this->assertCount(1, $comments);
        $this->assertEquals('What browser is this?', $comments[0]['content']);
        $this->assertCount(1, $comments[0]['replies']);
        $this->assertEquals('Chrome 120 on macOS', $comments[0]['replies'][0]['content']);
    }

    #[Test]
    public function comments_on_private_video_returns_error(): void
    {
        $video = Video::factory()->withHls()->create([
            'user_id' => $this->user->id,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/share/video/{$video->share_token}/comments");

        // Should return 403 or 404 depending on implementation
        $response->assertStatus(403);
    }

    // -------------------------------------------------------
    // Full MCP workflow simulation
    // -------------------------------------------------------

    #[Test]
    public function full_mcp_workflow_get_info_then_transcription_then_comments(): void
    {
        $video = Video::factory()->withHls()->withTranscriptionAndSummary()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'title' => 'Deploy Process Walkthrough',
            'transcription' => 'First we run the test suite, then we deploy to staging.',
            'summary' => 'Walkthrough of the CI/CD deploy process.',
        ]);

        $commenter = User::factory()->create(['name' => 'DevOps Lead']);
        Comment::factory()->create([
            'video_id' => $video->id,
            'user_id' => $commenter->id,
            'content' => 'We should add a rollback step here',
            'timestamp_seconds' => 30,
        ]);

        // Step 1: MCP calls get_video_info (uses share/video endpoint)
        $infoResponse = $this->getJson("/api/share/video/{$video->share_token}");
        $infoResponse->assertOk();

        $videoData = $infoResponse->json('video');
        $this->assertEquals('Deploy Process Walkthrough', $videoData['title']);
        $this->assertNotNull($videoData['transcription']);
        $this->assertNotNull($videoData['summary']);

        // Step 2: MCP reads transcription from same response
        $this->assertEquals(
            'First we run the test suite, then we deploy to staging.',
            $videoData['transcription']
        );
        $this->assertEquals(
            'Walkthrough of the CI/CD deploy process.',
            $videoData['summary']
        );

        // Step 3: MCP fetches detailed comments
        $commentsResponse = $this->getJson("/api/share/video/{$video->share_token}/comments");
        $commentsResponse->assertOk();

        $comments = $commentsResponse->json('comments');
        $this->assertCount(1, $comments);
        $this->assertEquals('We should add a rollback step here', $comments[0]['content']);
        $this->assertEquals(30, $comments[0]['timestamp_seconds']);
        $this->assertEquals('DevOps Lead', $comments[0]['author_name']);
    }

    #[Test]
    public function video_with_multiple_transcription_segments_returns_all(): void
    {
        $segments = [
            ['start' => 0, 'end' => 10, 'text' => 'Welcome to this demo.'],
            ['start' => 10, 'end' => 25, 'text' => 'Today we are looking at the new auth flow.'],
            ['start' => 25, 'end' => 40, 'text' => 'First, the user clicks login.'],
            ['start' => 40, 'end' => 55, 'text' => 'Then they enter their credentials.'],
            ['start' => 55, 'end' => 70, 'text' => 'And finally, they are redirected to the dashboard.'],
        ];

        $video = Video::factory()->withHls()->withTranscription()->create([
            'user_id' => $this->user->id,
            'is_public' => true,
            'transcription_segments' => $segments,
        ]);

        $response = $this->getJson("/api/share/video/{$video->share_token}");

        $response->assertOk();
        $returnedSegments = $response->json('video.transcription_segments');
        $this->assertCount(5, $returnedSegments);
        $this->assertEquals('Welcome to this demo.', $returnedSegments[0]['text']);
        $this->assertEquals(0, $returnedSegments[0]['start']);
        $this->assertEquals(10, $returnedSegments[0]['end']);
        $this->assertEquals('And finally, they are redirected to the dashboard.', $returnedSegments[4]['text']);
    }
}

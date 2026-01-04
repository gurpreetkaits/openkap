<?php

namespace Tests\Feature;

use App\Managers\CommentManager;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $videoOwner;

    protected User $commenter;

    protected Video $video;

    protected CommentManager $commentManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->videoOwner = User::factory()->create(['name' => 'Video Owner']);
        $this->commenter = User::factory()->create(['name' => 'Commenter']);
        $this->video = Video::factory()->create([
            'user_id' => $this->videoOwner->id,
            'title' => 'Test Video',
        ]);
        $this->commentManager = app(CommentManager::class);
    }

    #[Test]
    public function video_owner_receives_notification_when_someone_comments(): void
    {
        $this->actingAs($this->commenter)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => 'Great video!',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->videoOwner->id,
            'type' => Notification::TYPE_COMMENT,
        ]);

        $notification = Notification::where('user_id', $this->videoOwner->id)->first();
        $this->assertStringContains('Commenter', $notification->message);
        $this->assertStringContains('Test Video', $notification->message);
    }

    #[Test]
    public function video_owner_does_not_receive_notification_for_own_comment(): void
    {
        $this->actingAs($this->videoOwner)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => 'My own comment',
            ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->videoOwner->id,
            'type' => Notification::TYPE_COMMENT,
        ]);
    }

    #[Test]
    public function mentioned_user_receives_notification(): void
    {
        $mentionedUser = User::factory()->create(['name' => 'Jane Doe']);

        // Create a previous comment so mentioned user appears in commenters
        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $mentionedUser->id,
        ]);

        $this->actingAs($this->commenter)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => "Hey @[Jane Doe]({$mentionedUser->id}), thoughts?",
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mentionedUser->id,
            'type' => Notification::TYPE_COMMENT,
        ]);

        $notification = Notification::where('user_id', $mentionedUser->id)->first();
        $this->assertStringContains('mentioned you', $notification->message);
    }

    #[Test]
    public function user_does_not_receive_notification_for_mentioning_themselves(): void
    {
        $this->actingAs($this->commenter)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => "Testing @[Commenter]({$this->commenter->id})",
            ]);

        // Commenter should not get a mention notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->commenter->id,
            'type' => Notification::TYPE_COMMENT,
        ]);
    }

    #[Test]
    public function parent_comment_author_receives_reply_notification(): void
    {
        $parentComment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->commenter->id,
            'content' => 'Original comment',
        ]);

        $replier = User::factory()->create(['name' => 'Replier']);

        $this->actingAs($replier)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => 'This is a reply',
                'parent_id' => $parentComment->id,
            ]);

        $notifications = Notification::where('user_id', $this->commenter->id)->get();

        // Should have reply notification
        $replyNotification = $notifications->first(function ($n) {
            return str_contains($n->message, 'replied');
        });

        $this->assertNotNull($replyNotification);
        $this->assertStringContains('Replier', $replyNotification->message);
    }

    #[Test]
    public function user_does_not_receive_notification_for_replying_to_own_comment(): void
    {
        $parentComment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->commenter->id,
            'content' => 'My comment',
        ]);

        // Same user replies to their own comment
        $this->actingAs($this->commenter)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => 'My own reply',
                'parent_id' => $parentComment->id,
            ]);

        // Commenter should not get a reply notification for their own reply
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->commenter->id,
            'type' => Notification::TYPE_COMMENT,
        ]);
    }

    #[Test]
    public function editing_comment_notifies_newly_mentioned_users(): void
    {
        $comment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->commenter->id,
            'content' => 'Original without mentions',
            'mentions' => [],
        ]);

        $newMention = User::factory()->create(['name' => 'New Mention']);

        $this->actingAs($this->commenter)
            ->putJson("/api/videos/{$this->video->id}/comments/{$comment->id}", [
                'content' => "Updated with @[New Mention]({$newMention->id})",
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $newMention->id,
            'type' => Notification::TYPE_COMMENT,
        ]);
    }

    #[Test]
    public function notifications_can_be_marked_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->videoOwner->id,
            'type' => Notification::TYPE_COMMENT,
            'read' => false,
        ]);

        $response = $this->actingAs($this->videoOwner)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read' => true,
        ]);
    }

    #[Test]
    public function notifications_can_be_deleted(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->videoOwner->id,
            'type' => Notification::TYPE_COMMENT,
        ]);

        $response = $this->actingAs($this->videoOwner)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    #[Test]
    public function unread_count_is_accurate(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->videoOwner->id,
            'read' => false,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->videoOwner->id,
            'read' => true,
        ]);

        $response = $this->actingAs($this->videoOwner)
            ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'unread_count' => 3,
            ]);
    }

    #[Test]
    public function mark_all_as_read_works(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->videoOwner->id,
            'read' => false,
        ]);

        $response = $this->actingAs($this->videoOwner)
            ->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200);

        $this->assertEquals(0, Notification::where('user_id', $this->videoOwner->id)->where('read', false)->count());
    }

    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}

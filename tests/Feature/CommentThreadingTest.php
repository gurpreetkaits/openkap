<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentThreadingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Video $video;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->video = Video::factory()->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function user_can_create_a_comment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => 'This is a test comment',
                'timestamp_seconds' => 30,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'content' => 'This is a test comment',
            ]);

        $this->assertDatabaseHas('comments', [
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment',
        ]);
    }

    #[Test]
    public function user_can_reply_to_a_comment(): void
    {
        $parentComment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Parent comment',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => 'This is a reply',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'content' => 'This is a reply',
                'parent_id' => $parentComment->id,
            ]);

        $this->assertDatabaseHas('comments', [
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
        ]);
    }

    #[Test]
    public function comments_are_returned_in_threaded_format(): void
    {
        $parentComment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
            'content' => 'Parent comment',
        ]);

        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Reply 1',
            'parent_id' => $parentComment->id,
        ]);

        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
            'content' => 'Reply 2',
            'parent_id' => $parentComment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$this->video->id}/comments");

        $response->assertStatus(200);

        $comments = $response->json('comments');
        $this->assertCount(1, $comments); // Only parent comment at top level
        $this->assertCount(2, $comments[0]['replies']); // Two replies nested
    }

    #[Test]
    public function user_can_edit_their_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/videos/{$this->video->id}/comments/{$comment->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'content' => 'Updated content',
                'is_edited' => true,
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);
    }

    #[Test]
    public function user_cannot_edit_others_comment(): void
    {
        $comment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Other user comment',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/videos/{$this->video->id}/comments/{$comment->id}", [
                'content' => 'Trying to edit',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Other user comment',
        ]);
    }

    #[Test]
    public function user_can_delete_their_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$this->video->id}/comments/{$comment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    #[Test]
    public function video_owner_can_delete_any_comment(): void
    {
        $comment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->otherUser->id,
        ]);

        // Video owner (user) deletes other user's comment
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$this->video->id}/comments/{$comment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    #[Test]
    public function user_cannot_delete_others_comment_on_others_video(): void
    {
        $thirdUser = User::factory()->create();
        $otherVideo = Video::factory()->create(['user_id' => $thirdUser->id]);

        $comment = Comment::factory()->create([
            'video_id' => $otherVideo->id,
            'user_id' => $thirdUser->id,
        ]);

        // Other user tries to delete comment on third user's video
        $response = $this->actingAs($this->otherUser)
            ->deleteJson("/api/videos/{$otherVideo->id}/comments/{$comment->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    }

    #[Test]
    public function commenters_endpoint_returns_unique_users(): void
    {
        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
        ]);

        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->otherUser->id,
        ]);

        // Same user comments again
        Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$this->video->id}/commenters");

        $response->assertStatus(200);

        $commenters = $response->json('commenters');
        $this->assertCount(2, $commenters); // Only 2 unique users
    }

    #[Test]
    public function comment_with_mentions_stores_mentioned_user_ids(): void
    {
        $mentionedUser = User::factory()->create(['name' => 'John Doe']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$this->video->id}/comments", [
                'content' => "Hey @[John Doe]({$mentionedUser->id}), check this out!",
                'timestamp_seconds' => 30,
            ]);

        $response->assertStatus(201);

        $comment = Comment::latest()->first();
        $this->assertEquals([$mentionedUser->id], $comment->mentions);
    }

    #[Test]
    public function deleting_parent_comment_cascades_to_replies(): void
    {
        $parentComment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
        ]);

        $replyComment = Comment::factory()->create([
            'video_id' => $this->video->id,
            'user_id' => $this->otherUser->id,
            'parent_id' => $parentComment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$this->video->id}/comments/{$parentComment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('comments', ['id' => $parentComment->id]);
        $this->assertDatabaseMissing('comments', ['id' => $replyComment->id]);
    }
}

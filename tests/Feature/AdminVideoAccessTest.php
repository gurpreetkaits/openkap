<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admins may VIEW any recording so they can check from the dashboard whether a
 * user's captures actually work. That escalation must stay strictly read-only —
 * the "cannot mutate" cases below are the point of this suite, not an afterthought.
 */
class AdminVideoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $owner;

    protected User $stranger;

    protected Video $privateVideo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->owner = User::factory()->create(['is_admin' => false]);
        $this->stranger = User::factory()->create(['is_admin' => false]);

        // Private: sharing explicitly off.
        $this->privateVideo = Video::factory()->withHls()->create([
            'user_id' => $this->owner->id,
            'is_public' => false,
            'duration' => 120,
            'file_size_bytes' => 5_000_000,
        ]);
    }

    // ---------------------------------------------------------------- viewing

    #[Test]
    public function an_admin_can_view_another_users_private_video(): void
    {
        $this->actingAs($this->admin)
            ->getJson("/api/videos/{$this->privateVideo->id}")
            ->assertOk()
            ->assertJsonPath('video.id', $this->privateVideo->id);
    }

    #[Test]
    public function the_owner_can_still_view_their_own_private_video(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/videos/{$this->privateVideo->id}")
            ->assertOk()
            ->assertJsonPath('video.id', $this->privateVideo->id);
    }

    #[Test]
    public function a_non_admin_stranger_still_cannot_view_a_private_video(): void
    {
        $this->actingAs($this->stranger)
            ->getJson("/api/videos/{$this->privateVideo->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function a_guest_still_cannot_view_a_private_video(): void
    {
        $this->getJson("/api/videos/{$this->privateVideo->id}")
            ->assertStatus(401);
    }

    #[Test]
    public function an_admin_can_read_shared_details_even_when_sharing_is_disabled(): void
    {
        $this->actingAs($this->admin)
            ->getJson("/api/share/video/{$this->privateVideo->share_token}")
            ->assertOk();
    }

    #[Test]
    public function a_stranger_cannot_read_shared_details_when_sharing_is_disabled(): void
    {
        $this->actingAs($this->stranger)
            ->getJson("/api/share/video/{$this->privateVideo->share_token}")
            ->assertStatus(403);
    }

    #[Test]
    public function a_guest_cannot_read_shared_details_when_sharing_is_disabled(): void
    {
        $this->getJson("/api/share/video/{$this->privateVideo->share_token}")
            ->assertStatus(403);
    }

    #[Test]
    public function a_genuinely_shared_video_is_still_readable_by_anyone(): void
    {
        $shared = Video::factory()->withHls()->create([
            'user_id' => $this->owner->id,
            'is_public' => true,
        ]);

        $this->getJson("/api/share/video/{$shared->share_token}")->assertOk();
    }

    // --------------------------------------------- review reads the player needs

    #[Test]
    public function an_admin_can_read_comments_on_another_users_private_video(): void
    {
        $this->actingAs($this->admin)
            ->getJson("/api/videos/{$this->privateVideo->id}/comments")
            ->assertOk();
    }

    #[Test]
    public function an_admin_can_read_the_transcription_of_another_users_private_video(): void
    {
        $this->actingAs($this->admin)
            ->getJson("/api/videos/{$this->privateVideo->id}/transcription")
            ->assertOk();
    }

    #[Test]
    public function a_stranger_still_cannot_read_comments_on_a_private_video(): void
    {
        $this->actingAs($this->stranger)
            ->getJson("/api/videos/{$this->privateVideo->id}/comments")
            ->assertStatus(403);
    }

    #[Test]
    public function a_stranger_still_cannot_read_the_transcription_of_a_private_video(): void
    {
        $this->actingAs($this->stranger)
            ->getJson("/api/videos/{$this->privateVideo->id}/transcription")
            ->assertStatus(403);
    }

    // ------------------------------------------------- read-only: no mutation

    #[Test]
    public function an_admin_cannot_post_a_comment_on_another_users_private_video(): void
    {
        // Reading comments is allowed for review; writing one is not.
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->privateVideo->id}/comments", [
                'content' => 'admin was here',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('comments', ['content' => 'admin was here']);
    }

    #[Test]
    public function an_admin_cannot_request_a_transcription_for_another_users_video(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->privateVideo->id}/transcription")
            ->assertStatus(403);
    }

    #[Test]
    public function an_admin_cannot_delete_another_users_video(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/api/videos/{$this->privateVideo->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('videos', ['id' => $this->privateVideo->id]);
    }

    #[Test]
    public function an_admin_cannot_update_another_users_video(): void
    {
        $this->actingAs($this->admin)
            ->putJson("/api/videos/{$this->privateVideo->id}", ['title' => 'Renamed by admin'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('videos', [
            'id' => $this->privateVideo->id,
            'title' => 'Renamed by admin',
        ]);
    }

    #[Test]
    public function an_admin_cannot_turn_on_sharing_for_another_users_video(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->privateVideo->id}/toggle-sharing")
            ->assertStatus(403);

        $this->assertDatabaseHas('videos', [
            'id' => $this->privateVideo->id,
            'is_public' => false,
        ]);
    }

    #[Test]
    public function an_admin_cannot_regenerate_another_users_share_token(): void
    {
        $original = $this->privateVideo->share_token;

        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->privateVideo->id}/regenerate-token")
            ->assertStatus(403);

        $this->assertSame($original, $this->privateVideo->fresh()->share_token);
    }

    #[Test]
    public function an_admin_cannot_trim_another_users_video(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->privateVideo->id}/trim", [
                'start_time' => 0,
                'end_time' => 10,
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function an_admin_cannot_duplicate_another_users_video(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->privateVideo->id}/duplicate")
            ->assertStatus(403);

        $this->assertSame(1, Video::where('user_id', $this->owner->id)->count());
    }
}

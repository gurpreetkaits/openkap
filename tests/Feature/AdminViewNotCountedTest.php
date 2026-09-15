<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * An admin reviewing recordings from the dashboard must not inflate the owner's
 * analytics. Owner self-views were already excluded; admin review views join them.
 */
class AdminViewNotCountedTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $owner;

    protected User $viewer;

    protected Video $video;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->owner = User::factory()->create(['is_admin' => false]);
        $this->viewer = User::factory()->create(['is_admin' => false]);

        $this->video = Video::factory()->withHls()->create([
            'user_id' => $this->owner->id,
            'is_public' => true,
            'duration' => 120,
        ]);
    }

    #[Test]
    public function an_admin_view_is_not_recorded(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->video->id}/view", ['watch_duration' => 30])
            ->assertOk()
            ->assertJsonPath('view', null);

        $this->assertDatabaseMissing('video_views', [
            'video_id' => $this->video->id,
            'user_id' => $this->admin->id,
        ]);
        $this->assertSame(0, $this->video->views()->count());
    }

    #[Test]
    public function an_admin_view_of_a_private_video_is_allowed_but_not_recorded(): void
    {
        $private = Video::factory()->withHls()->create([
            'user_id' => $this->owner->id,
            'is_public' => false,
        ]);

        // Not a 403 — the player should not error — but nothing is recorded.
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$private->id}/view", ['watch_duration' => 30])
            ->assertOk();

        $this->assertSame(0, $private->views()->count());
    }

    #[Test]
    public function an_admin_progress_ping_is_not_recorded(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->video->id}/progress", [
                'session_id' => 'sess-admin',
                'progress_seconds' => 42,
            ])
            ->assertOk()
            ->assertJsonPath('updated', false);

        $this->assertSame(0, $this->video->views()->count());
    }

    #[Test]
    public function an_admin_view_via_a_share_link_is_not_recorded(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/share/video/{$this->video->share_token}/view")
            ->assertOk();

        $this->assertSame(0, $this->video->views()->count());
    }

    #[Test]
    public function an_admin_view_does_not_notify_the_owner(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/videos/{$this->video->id}/view", ['watch_duration' => 30]);

        $this->assertDatabaseMissing('notifications', ['user_id' => $this->owner->id]);
    }

    // ------------------------------------------------- genuine views still count

    #[Test]
    public function an_ordinary_viewer_is_still_recorded(): void
    {
        $this->actingAs($this->viewer)
            ->postJson("/api/videos/{$this->video->id}/view", ['watch_duration' => 30])
            ->assertStatus(201);

        $this->assertDatabaseHas('video_views', [
            'video_id' => $this->video->id,
            'user_id' => $this->viewer->id,
        ]);
        $this->assertSame(1, $this->video->views()->count());
    }

    #[Test]
    public function an_anonymous_viewer_is_still_recorded(): void
    {
        $this->postJson("/api/share/video/{$this->video->share_token}/view")
            ->assertSuccessful();

        $this->assertSame(1, $this->video->views()->count());
    }

    #[Test]
    public function the_owner_is_still_excluded_as_before(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/videos/{$this->video->id}/view", ['watch_duration' => 30])
            ->assertOk()
            ->assertJsonPath('view', null);

        $this->assertSame(0, $this->video->views()->count());
    }
}

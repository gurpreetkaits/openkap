<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Video;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function workspace_generates_unique_slug_on_creation(): void
    {
        $user = User::factory()->create();

        $workspace = Workspace::create([
            'name' => 'My Workspace',
            'owner_id' => $user->id,
        ]);

        $this->assertNotNull($workspace->slug);
        $this->assertEquals('my-workspace', $workspace->slug);
    }

    #[Test]
    public function workspace_generates_unique_slug_when_duplicate_exists(): void
    {
        $user = User::factory()->create();

        $workspace1 = Workspace::create([
            'name' => 'My Workspace',
            'owner_id' => $user->id,
        ]);

        $workspace2 = Workspace::create([
            'name' => 'My Workspace',
            'owner_id' => $user->id,
        ]);

        $this->assertEquals('my-workspace', $workspace1->slug);
        $this->assertEquals('my-workspace-1', $workspace2->slug);
    }

    #[Test]
    public function workspace_has_owner_relationship(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

        $this->assertEquals($user->id, $workspace->owner->id);
    }

    #[Test]
    public function workspace_has_members_relationship(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $member = User::factory()->create();

        $workspace->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->assertEquals(2, $workspace->members()->count()); // owner + member
        $this->assertTrue($workspace->hasMember($member));
    }

    #[Test]
    public function workspace_has_videos_relationship(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $video = Video::factory()->create([
            'user_id' => $workspace->owner_id,
            'workspace_id' => $workspace->id,
        ]);

        $this->assertEquals(1, $workspace->videos()->count());
        $this->assertEquals($video->id, $workspace->videos->first()->id);
    }

    #[Test]
    public function workspace_has_invitations_relationship(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $invitation = WorkspaceInvitation::factory()->create([
            'workspace_id' => $workspace->id,
            'invited_by' => $workspace->owner_id,
        ]);

        $this->assertEquals(1, $workspace->invitations()->count());
    }

    #[Test]
    public function workspace_can_check_active_subscription(): void
    {
        $freeWorkspace = Workspace::factory()->create();
        $paidWorkspace = Workspace::factory()->withTeamSubscription()->create();
        $canceledWorkspace = Workspace::factory()->canceled()->create();
        $expiredWorkspace = Workspace::factory()->expired()->create();

        $this->assertFalse($freeWorkspace->hasActiveSubscription());
        $this->assertTrue($paidWorkspace->hasActiveSubscription());
        $this->assertTrue($canceledWorkspace->hasActiveSubscription()); // Grace period
        $this->assertFalse($expiredWorkspace->hasActiveSubscription());
    }

    #[Test]
    public function workspace_can_check_grace_period(): void
    {
        $canceledWorkspace = Workspace::factory()->canceled()->create();
        $expiredWorkspace = Workspace::factory()->expired()->create();

        $this->assertTrue($canceledWorkspace->isInGracePeriod());
        $this->assertFalse($expiredWorkspace->isInGracePeriod());
    }

    #[Test]
    public function workspace_can_check_if_user_is_owner(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($workspace->isOwner($owner));
        $this->assertFalse($workspace->isOwner($member));
    }

    #[Test]
    public function workspace_can_check_if_user_is_admin(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($workspace->isAdmin($workspace->owner)); // Owner is admin
        $this->assertTrue($workspace->isAdmin($admin));
        $this->assertFalse($workspace->isAdmin($member));
    }

    #[Test]
    public function workspace_can_get_user_role(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $stranger = User::factory()->create();

        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertEquals('owner', $workspace->getUserRole($workspace->owner));
        $this->assertEquals('admin', $workspace->getUserRole($admin));
        $this->assertEquals('member', $workspace->getUserRole($member));
        $this->assertNull($workspace->getUserRole($stranger));
    }

    #[Test]
    public function workspace_can_check_member_limits(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['max_members' => 3]);

        // Owner is already a member
        $this->assertEquals(2, $workspace->getRemainingSlots());
        $this->assertTrue($workspace->canAddMembers());

        // Add more members
        $workspace->members()->attach(User::factory()->create()->id, ['role' => 'member', 'joined_at' => now()]);
        $workspace->members()->attach(User::factory()->create()->id, ['role' => 'member', 'joined_at' => now()]);

        $workspace->refresh();
        $this->assertEquals(0, $workspace->getRemainingSlots());
        $this->assertFalse($workspace->canAddMembers());
    }

    #[Test]
    public function workspace_can_track_storage_usage(): void
    {
        $workspace = Workspace::factory()->create([
            'max_storage_gb' => 100,
            'storage_used_bytes' => 50 * 1024 * 1024 * 1024, // 50 GB
        ]);

        $this->assertEquals(50, $workspace->getStorageUsedGb());
        $this->assertEquals(50, $workspace->getStorageUsagePercent());
        $this->assertFalse($workspace->isStorageFull());
    }

    #[Test]
    public function workspace_can_detect_full_storage(): void
    {
        $workspace = Workspace::factory()->storageFull()->create();

        $this->assertEquals(100, $workspace->getStorageUsagePercent());
        $this->assertTrue($workspace->isStorageFull());
    }

    #[Test]
    public function workspace_can_check_member_recording_ability(): void
    {
        $freeWorkspace = Workspace::factory()->withOwnerAsMember()->create();
        $paidWorkspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();

        $this->assertFalse($freeWorkspace->canMemberRecord($freeWorkspace->owner));
        $this->assertTrue($paidWorkspace->canMemberRecord($paidWorkspace->owner));
    }

    #[Test]
    public function workspace_returns_max_recording_seconds(): void
    {
        $workspace = Workspace::factory()->create(['max_recording_minutes' => 60]);

        $this->assertEquals(3600, $workspace->getMaxRecordingSeconds());
    }
}

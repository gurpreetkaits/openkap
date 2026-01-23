<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withProSubscription()->create();
    }

    // ==================
    // Workspace CRUD Tests
    // ==================

    #[Test]
    public function user_can_create_workspace(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/workspaces', [
                'name' => 'My Team',
                'description' => 'A great team workspace',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'My Team',
                'description' => 'A great team workspace',
            ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'My Team',
            'owner_id' => $this->user->id,
        ]);

        // Owner should be added as member
        $this->assertDatabaseHas('workspace_members', [
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    #[Test]
    public function workspace_creation_requires_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/workspaces', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function cannot_create_workspace_with_duplicate_name(): void
    {
        // Create first workspace
        Workspace::factory()->withOwnerAsMember()->create([
            'owner_id' => $this->user->id,
            'name' => 'My Team',
        ]);

        // Try to create another with same name
        $response = $this->actingAs($this->user)
            ->postJson('/api/workspaces', [
                'name' => 'My Team',
                'description' => 'Another workspace',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function different_users_can_have_same_workspace_name(): void
    {
        $otherUser = User::factory()->withProSubscription()->create();

        // Other user creates a workspace
        Workspace::factory()->withOwnerAsMember()->create([
            'owner_id' => $otherUser->id,
            'name' => 'My Team',
        ]);

        // Current user should be able to create workspace with same name
        $response = $this->actingAs($this->user)
            ->postJson('/api/workspaces', [
                'name' => 'My Team',
                'description' => 'My own team',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'My Team']);
    }

    #[Test]
    public function can_update_workspace_without_changing_name(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create([
            'owner_id' => $this->user->id,
            'name' => 'My Team',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/workspaces/{$workspace->slug}", [
                'name' => 'My Team',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['description' => 'Updated description']);
    }

    #[Test]
    public function cannot_update_workspace_to_duplicate_name(): void
    {
        // Create two workspaces
        Workspace::factory()->withOwnerAsMember()->create([
            'owner_id' => $this->user->id,
            'name' => 'First Team',
        ]);

        $secondWorkspace = Workspace::factory()->withOwnerAsMember()->create([
            'owner_id' => $this->user->id,
            'name' => 'Second Team',
        ]);

        // Try to rename second workspace to first's name
        $response = $this->actingAs($this->user)
            ->patchJson("/api/workspaces/{$secondWorkspace->slug}", [
                'name' => 'First Team',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function user_can_list_their_workspaces(): void
    {
        $workspace1 = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);
        $workspace2 = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        // Workspace user is member of
        $otherWorkspace = Workspace::factory()->withOwnerAsMember()->create();
        $otherWorkspace->members()->attach($this->user->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/workspaces');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'workspaces');
    }

    #[Test]
    public function user_can_view_workspace_details(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/workspaces/{$workspace->slug}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'is_owner' => true,
            ]);
    }

    #[Test]
    public function non_member_cannot_view_workspace(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)
            ->getJson("/api/workspaces/{$workspace->slug}");

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_can_update_workspace(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/workspaces/{$workspace->slug}", [
                'name' => 'Updated Name',
                'description' => 'New description',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
            ]);
    }

    #[Test]
    public function member_cannot_update_workspace(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($member)
            ->patchJson("/api/workspaces/{$workspace->slug}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_can_delete_workspace(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/workspaces/{$workspace->slug}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('workspaces', ['id' => $workspace->id]);
    }

    // ==================
    // Member Management Tests
    // ==================

    #[Test]
    public function owner_can_invite_member(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/members/invite", [
                'email' => 'newmember@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);
    }

    #[Test]
    public function cannot_invite_when_workspace_is_full(): void
    {
        $workspace = Workspace::factory()
            ->withTeamSubscription()
            ->withOwnerAsMember()
            ->create([
                'owner_id' => $this->user->id,
                'max_members' => 2,
            ]);

        // Add another member to fill it
        $workspace->members()->attach(User::factory()->create()->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/members/invite", [
                'email' => 'newmember@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function cannot_invite_existing_member(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);
        $existingMember = User::factory()->create();
        $workspace->members()->attach($existingMember->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/members/invite", [
                'email' => $existingMember->email,
                'role' => 'member',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function admin_can_invite_member(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $admin = User::factory()->create();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson("/api/workspaces/{$workspace->slug}/members/invite", [
                'email' => 'newmember@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(201);
    }

    #[Test]
    public function member_cannot_invite(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($member)
            ->postJson("/api/workspaces/{$workspace->slug}/members/invite", [
                'email' => 'newmember@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_accept_invitation(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $invitation = WorkspaceInvitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => $this->user->email,
            'invited_by' => $workspace->owner_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/invitations/{$invitation->token}/accept");

        $response->assertStatus(200);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $this->user->id,
        ]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    #[Test]
    public function cannot_accept_expired_invitation(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $invitation = WorkspaceInvitation::factory()->expired()->create([
            'workspace_id' => $workspace->id,
            'email' => $this->user->email,
            'invited_by' => $workspace->owner_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/invitations/{$invitation->token}/accept");

        $response->assertStatus(422);
    }

    #[Test]
    public function cannot_accept_invitation_for_different_email(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $invitation = WorkspaceInvitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'other@example.com',
            'invited_by' => $workspace->owner_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/invitations/{$invitation->token}/accept");

        $response->assertStatus(422);
    }

    #[Test]
    public function owner_can_list_members(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/workspaces/{$workspace->slug}/members");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'members');
    }

    #[Test]
    public function owner_can_remove_member(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/workspaces/{$workspace->slug}/members/{$member->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
        ]);
    }

    #[Test]
    public function owner_cannot_be_removed(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);
        $admin = User::factory()->create();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        // Admin trying to remove owner
        $response = $this->actingAs($admin)
            ->deleteJson("/api/workspaces/{$workspace->slug}/members/{$this->user->id}");

        $response->assertStatus(422);
    }

    #[Test]
    public function member_can_leave_workspace(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create();
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($member)
            ->postJson("/api/workspaces/{$workspace->slug}/leave");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
        ]);
    }

    #[Test]
    public function owner_cannot_leave_workspace(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/leave");

        $response->assertStatus(422);
    }

    #[Test]
    public function owner_can_update_member_role(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/workspaces/{$workspace->slug}/members/{$member->id}", [
                'role' => 'admin',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);
    }

    // ==================
    // Invitation Tests
    // ==================

    #[Test]
    public function can_get_invitation_details_by_token(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $invitation = WorkspaceInvitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'test@example.com',
            'invited_by' => $workspace->owner_id,
        ]);

        $response = $this->getJson("/api/invitations/{$invitation->token}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'email' => 'test@example.com',
            ]);
    }

    #[Test]
    public function expired_invitation_returns_404(): void
    {
        $invitation = WorkspaceInvitation::factory()->expired()->create();

        $response = $this->getJson("/api/invitations/{$invitation->token}");

        $response->assertStatus(404);
    }

    #[Test]
    public function owner_can_cancel_invitation(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);
        $invitation = WorkspaceInvitation::factory()->create([
            'workspace_id' => $workspace->id,
            'invited_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/workspaces/{$workspace->slug}/invitations/{$invitation->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('workspace_invitations', ['id' => $invitation->id]);
    }

    #[Test]
    public function admin_can_create_invitation_via_invitations_endpoint(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $admin = User::factory()->create();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson("/api/workspaces/{$workspace->slug}/invitations", [
                'email' => 'newinvite@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['email' => 'newinvite@example.com']);

        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'newinvite@example.com',
            'role' => 'member',
        ]);
    }

    #[Test]
    public function member_cannot_create_invitation(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($member)
            ->postJson("/api/workspaces/{$workspace->slug}/invitations", [
                'email' => 'newinvite@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function cannot_create_duplicate_invitation(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        // Create first invitation
        WorkspaceInvitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'test@example.com',
            'invited_by' => $this->user->id,
        ]);

        // Try to create duplicate
        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/invitations", [
                'email' => 'test@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function invitation_requires_valid_email(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/invitations", [
                'email' => 'invalid-email',
                'role' => 'member',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function invitation_requires_valid_role(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/invitations", [
                'email' => 'test@example.com',
                'role' => 'superadmin',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function owner_can_resend_invitation(): void
    {
        $workspace = Workspace::factory()->withOwnerAsMember()->create(['owner_id' => $this->user->id]);

        // Create invitation that's expiring soon (in 1 day)
        $invitation = WorkspaceInvitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'test@example.com',
            'invited_by' => $this->user->id,
            'expires_at' => now()->addDay(),
        ]);

        $originalExpiry = $invitation->expires_at;

        $response = $this->actingAs($this->user)
            ->postJson("/api/workspaces/{$workspace->slug}/invitations/{$invitation->id}/resend");

        $response->assertStatus(200);

        // Check expiry was extended (resend gives 7 days, original was 1 day)
        $this->assertTrue($invitation->fresh()->expires_at->gt($originalExpiry));
    }

    // ==================
    // Video in Workspace Tests
    // ==================

    #[Test]
    public function workspace_member_can_view_workspace_videos(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        Video::factory()->count(3)->create([
            'user_id' => $workspace->owner_id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($member)
            ->getJson("/api/workspaces/{$workspace->slug}/videos");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'videos');
    }

    #[Test]
    public function non_member_cannot_view_workspace_videos(): void
    {
        $workspace = Workspace::factory()->withTeamSubscription()->withOwnerAsMember()->create();
        $stranger = User::factory()->create();

        Video::factory()->count(3)->create([
            'user_id' => $workspace->owner_id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($stranger)
            ->getJson("/api/workspaces/{$workspace->slug}/videos");

        $response->assertStatus(403);
    }
}

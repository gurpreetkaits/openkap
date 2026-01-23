<?php

namespace App\Managers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Repositories\WorkspaceInvitationRepository;
use App\Repositories\WorkspaceRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WorkspaceManager
{
    public function __construct(
        protected WorkspaceRepository $workspaces,
        protected WorkspaceInvitationRepository $invitations
    ) {}

    /**
     * Create a new workspace
     */
    public function createWorkspace(User $owner, array $data): Workspace
    {
        // Create the workspace
        $workspace = $this->workspaces->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null, // Will be auto-generated if null
            'description' => $data['description'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'owner_id' => $owner->id,
            'max_members' => $data['max_members'] ?? 5,
            'max_storage_gb' => $data['max_storage_gb'] ?? 100,
            'max_recording_minutes' => $data['max_recording_minutes'] ?? 60,
        ]);

        // Add owner as a member with 'owner' role
        $this->workspaces->addMember($workspace, $owner, 'owner');

        Log::info('Workspace created', [
            'workspace_id' => $workspace->id,
            'owner_id' => $owner->id,
            'name' => $workspace->name,
        ]);

        return $workspace;
    }

    /**
     * Update workspace details
     */
    public function updateWorkspace(Workspace $workspace, array $data): Workspace
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['slug'])) {
            // Validate slug is available
            if (! $this->workspaces->isSlugAvailable($data['slug'], $workspace->id)) {
                throw new \InvalidArgumentException('This URL is already taken.');
            }
            $updateData['slug'] = $data['slug'];
        }

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['logo_url'])) {
            $updateData['logo_url'] = $data['logo_url'];
        }

        $this->workspaces->update($workspace, $updateData);

        return $workspace->fresh();
    }

    /**
     * Delete workspace
     */
    public function deleteWorkspace(Workspace $workspace): void
    {
        // Videos will have workspace_id set to null (nullOnDelete)
        // Members will be cascade deleted
        $this->workspaces->delete($workspace);

        Log::info('Workspace deleted', [
            'workspace_id' => $workspace->id,
            'name' => $workspace->name,
        ]);
    }

    /**
     * Get workspace details for API response
     */
    public function getWorkspaceDetails(Workspace $workspace, ?User $user = null): array
    {
        $workspace->loadCount(['members', 'videos']);

        $data = [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'description' => $workspace->description,
            'logo_url' => $workspace->logo_url,
            'owner_id' => $workspace->owner_id,
            'members_count' => $workspace->members_count,
            'videos_count' => $workspace->videos_count,
            'max_members' => $workspace->max_members,
            'remaining_slots' => $workspace->getRemainingSlots(),
            'storage_used_gb' => $workspace->getStorageUsedGb(),
            'max_storage_gb' => $workspace->max_storage_gb,
            'storage_usage_percent' => $workspace->getStorageUsagePercent(),
            'max_recording_minutes' => $workspace->max_recording_minutes,
            'subscription_status' => $workspace->subscription_status,
            'subscription_plan' => $workspace->subscription_plan,
            'has_active_subscription' => $workspace->hasActiveSubscription(),
            'is_in_grace_period' => $workspace->isInGracePeriod(),
            'subscription_expires_at' => $workspace->subscription_expires_at?->toISOString(),
            'created_at' => $workspace->created_at->toISOString(),
        ];

        // Add user's role if user is provided
        if ($user) {
            $data['user_role'] = $workspace->getUserRole($user);
            $data['is_owner'] = $workspace->isOwner($user);
            $data['is_admin'] = $workspace->isAdmin($user);
            $data['can_manage_members'] = $workspace->isAdmin($user);
            $data['can_record'] = $workspace->canMemberRecord($user);
        }

        return $data;
    }

    /**
     * Get workspaces list for user
     */
    public function getUserWorkspaces(User $user): array
    {
        $workspaces = $this->workspaces->getForUser($user);

        return $workspaces->map(function ($workspace) use ($user) {
            return [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'logo_url' => $workspace->logo_url,
                'members_count' => $workspace->members_count,
                'videos_count' => $workspace->videos_count,
                'user_role' => $workspace->getUserRole($user),
                'is_owner' => $workspace->isOwner($user),
                'has_active_subscription' => $workspace->hasActiveSubscription(),
                'subscription_plan' => $workspace->subscription_plan,
            ];
        })->toArray();
    }

    /**
     * Get workspace members list
     */
    public function getWorkspaceMembers(Workspace $workspace): array
    {
        $members = $this->workspaces->getMembers($workspace);

        return $members->map(function ($member) {
            return [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'avatar_url' => $member->user->avatar_url,
                'role' => $member->role,
                'joined_at' => $member->joined_at->toISOString(),
                'invited_by' => $member->inviter?->name,
            ];
        })->toArray();
    }

    /**
     * Invite a user to workspace
     */
    public function inviteMember(Workspace $workspace, string $email, string $role, User $inviter): WorkspaceInvitation
    {
        $email = strtolower(trim($email));

        // Check if workspace can add more members
        if (! $workspace->canAddMembers()) {
            throw new \InvalidArgumentException(
                "Workspace has reached its member limit of {$workspace->max_members}. Please upgrade to add more members."
            );
        }

        // Check if user is already a member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $workspace->hasMember($existingUser)) {
            throw new \InvalidArgumentException('This user is already a member of the workspace.');
        }

        // Check if there's already a pending invitation
        $existingInvitation = $this->invitations->findPendingForEmail($workspace, $email);
        if ($existingInvitation) {
            throw new \InvalidArgumentException('An invitation has already been sent to this email.');
        }

        // Create invitation
        $invitation = $this->invitations->create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $inviter->id,
        ]);

        // TODO: Send invitation email
        // Mail::to($email)->send(new WorkspaceInvitationMail($invitation));

        Log::info('Workspace invitation created', [
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $inviter->id,
        ]);

        return $invitation;
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(WorkspaceInvitation $invitation, User $user): Workspace
    {
        if (! $invitation->isValid()) {
            throw new \InvalidArgumentException('This invitation is no longer valid.');
        }

        // Check email matches
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw new \InvalidArgumentException('This invitation was sent to a different email address.');
        }

        $workspace = $invitation->workspace;

        // Check if workspace can still accept members
        if (! $workspace->canAddMembers()) {
            throw new \InvalidArgumentException('This workspace has reached its member limit.');
        }

        // Check if user is already a member
        if ($workspace->hasMember($user)) {
            throw new \InvalidArgumentException('You are already a member of this workspace.');
        }

        // Add user as member
        $this->workspaces->addMember($workspace, $user, $invitation->role, $invitation->invited_by);

        // Mark invitation as accepted
        $this->invitations->markAccepted($invitation);

        Log::info('Workspace invitation accepted', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        return $workspace;
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation(WorkspaceInvitation $invitation): void
    {
        $this->invitations->delete($invitation);
    }

    /**
     * Resend an invitation
     */
    public function resendInvitation(WorkspaceInvitation $invitation): WorkspaceInvitation
    {
        $this->invitations->resend($invitation);

        // TODO: Send invitation email again
        // Mail::to($invitation->email)->send(new WorkspaceInvitationMail($invitation));

        return $invitation->fresh();
    }

    /**
     * Get pending invitations for workspace
     */
    public function getPendingInvitations(Workspace $workspace): array
    {
        $invitations = $this->invitations->getPendingForWorkspace($workspace);

        return $invitations->map(function ($invitation) {
            return [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'invited_by' => $invitation->inviter?->name,
                'expires_at' => $invitation->expires_at->toISOString(),
                'created_at' => $invitation->created_at->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Get invitation details by token
     */
    public function getInvitationByToken(string $token): ?array
    {
        $invitation = $this->invitations->findValidByToken($token);

        if (! $invitation) {
            return null;
        }

        return [
            'id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'workspace' => [
                'id' => $invitation->workspace->id,
                'name' => $invitation->workspace->name,
                'slug' => $invitation->workspace->slug,
                'logo_url' => $invitation->workspace->logo_url,
                'members_count' => $invitation->workspace->members()->count(),
            ],
            'invited_by' => [
                'name' => $invitation->inviter->name,
                'avatar_url' => $invitation->inviter->avatar_url,
            ],
            'expires_at' => $invitation->expires_at->toISOString(),
        ];
    }

    /**
     * Remove a member from workspace
     */
    public function removeMember(Workspace $workspace, User $userToRemove, User $removedBy): void
    {
        // Cannot remove the owner
        if ($workspace->isOwner($userToRemove)) {
            throw new \InvalidArgumentException('Cannot remove the workspace owner. Transfer ownership first.');
        }

        // Check if user is a member
        if (! $workspace->hasMember($userToRemove)) {
            throw new \InvalidArgumentException('User is not a member of this workspace.');
        }

        $this->workspaces->removeMember($workspace, $userToRemove);

        Log::info('Member removed from workspace', [
            'workspace_id' => $workspace->id,
            'removed_user_id' => $userToRemove->id,
            'removed_by' => $removedBy->id,
        ]);
    }

    /**
     * Leave workspace (member removes themselves)
     */
    public function leaveWorkspace(Workspace $workspace, User $user): void
    {
        // Owner cannot leave, must transfer ownership first
        if ($workspace->isOwner($user)) {
            throw new \InvalidArgumentException('Workspace owner cannot leave. Transfer ownership first.');
        }

        // Check if user is a member
        if (! $workspace->hasMember($user)) {
            throw new \InvalidArgumentException('You are not a member of this workspace.');
        }

        $this->workspaces->removeMember($workspace, $user);

        Log::info('Member left workspace', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(Workspace $workspace, User $user, string $newRole): void
    {
        // Cannot change owner's role
        if ($workspace->isOwner($user)) {
            throw new \InvalidArgumentException('Cannot change the owner\'s role. Transfer ownership instead.');
        }

        // Validate role
        if (! in_array($newRole, ['admin', 'member'])) {
            throw new \InvalidArgumentException('Invalid role. Must be admin or member.');
        }

        // Check if user is a member
        if (! $workspace->hasMember($user)) {
            throw new \InvalidArgumentException('User is not a member of this workspace.');
        }

        $this->workspaces->updateMemberRole($workspace, $user, $newRole);

        Log::info('Member role updated', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'new_role' => $newRole,
        ]);
    }

    /**
     * Transfer workspace ownership
     */
    public function transferOwnership(Workspace $workspace, User $newOwner, User $currentOwner): void
    {
        // Verify current owner
        if (! $workspace->isOwner($currentOwner)) {
            throw new \InvalidArgumentException('Only the current owner can transfer ownership.');
        }

        // Check if new owner is a member
        if (! $workspace->hasMember($newOwner)) {
            throw new \InvalidArgumentException('New owner must be a member of the workspace.');
        }

        // New owner should be an admin
        if (! $workspace->isAdmin($newOwner)) {
            throw new \InvalidArgumentException('New owner must be an admin of the workspace.');
        }

        $this->workspaces->transferOwnership($workspace, $newOwner);

        Log::info('Workspace ownership transferred', [
            'workspace_id' => $workspace->id,
            'from_user_id' => $currentOwner->id,
            'to_user_id' => $newOwner->id,
        ]);
    }

    /**
     * Find workspace by slug
     */
    public function findBySlug(string $slug): ?Workspace
    {
        return $this->workspaces->findBySlug($slug);
    }

    /**
     * Find workspace by slug or fail
     */
    public function findBySlugOrFail(string $slug): Workspace
    {
        return $this->workspaces->findBySlugOrFail($slug);
    }
}

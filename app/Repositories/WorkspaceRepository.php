<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceRepository
{
    /**
     * Create a new workspace
     */
    public function create(array $data): Workspace
    {
        return Workspace::create($data);
    }

    /**
     * Find workspace by ID
     */
    public function find(int $id): ?Workspace
    {
        return Workspace::find($id);
    }

    /**
     * Find workspace by ID or fail
     */
    public function findOrFail(int $id): Workspace
    {
        return Workspace::findOrFail($id);
    }

    /**
     * Find workspace by slug
     */
    public function findBySlug(string $slug): ?Workspace
    {
        return Workspace::where('slug', $slug)->first();
    }

    /**
     * Find workspace by slug or fail
     */
    public function findBySlugOrFail(string $slug): Workspace
    {
        return Workspace::where('slug', $slug)->firstOrFail();
    }

    /**
     * Update workspace
     */
    public function update(Workspace $workspace, array $data): bool
    {
        return $workspace->update($data);
    }

    /**
     * Delete workspace (soft delete)
     */
    public function delete(Workspace $workspace): bool
    {
        return $workspace->delete();
    }

    /**
     * Get workspaces for a user
     */
    public function getForUser(User $user): Collection
    {
        return $user->workspaces()
            ->withCount('members')
            ->withCount('videos')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get workspaces owned by a user
     */
    public function getOwnedByUser(User $user): Collection
    {
        return $user->ownedWorkspaces()
            ->withCount('members')
            ->withCount('videos')
            ->orderBy('name')
            ->get();
    }

    /**
     * Add member to workspace
     */
    public function addMember(Workspace $workspace, User $user, string $role = 'member', ?int $invitedBy = null): WorkspaceMember
    {
        return WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
            'invited_by' => $invitedBy,
        ]);
    }

    /**
     * Remove member from workspace
     */
    public function removeMember(Workspace $workspace, User $user): bool
    {
        return WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    /**
     * Update member role
     */
    public function updateMemberRole(Workspace $workspace, User $user, string $role): bool
    {
        return WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->update(['role' => $role]) > 0;
    }

    /**
     * Get workspace member record
     */
    public function getMember(Workspace $workspace, User $user): ?WorkspaceMember
    {
        return WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Get all members of a workspace
     */
    public function getMembers(Workspace $workspace): Collection
    {
        return WorkspaceMember::where('workspace_id', $workspace->id)
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 WHEN 'member' THEN 3 END")
            ->orderBy('joined_at')
            ->get();
    }

    /**
     * Check if slug is available
     */
    public function isSlugAvailable(string $slug, ?int $excludeId = null): bool
    {
        $query = Workspace::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Check if workspace name is available for a user (as owner or member)
     */
    public function isNameAvailableForUser(string $name, User $user, ?int $excludeId = null): bool
    {
        // Check workspaces owned by user
        $query = Workspace::where('owner_id', $user->id)
            ->where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Update storage used
     */
    public function updateStorageUsed(Workspace $workspace, int $bytes): bool
    {
        return $workspace->update(['storage_used_bytes' => $bytes]);
    }

    /**
     * Increment storage used
     */
    public function incrementStorage(Workspace $workspace, int $bytes): void
    {
        $workspace->increment('storage_used_bytes', $bytes);
    }

    /**
     * Decrement storage used
     */
    public function decrementStorage(Workspace $workspace, int $bytes): void
    {
        $workspace->decrement('storage_used_bytes', max(0, $bytes));
    }

    /**
     * Transfer ownership
     */
    public function transferOwnership(Workspace $workspace, User $newOwner): bool
    {
        // Update workspace owner
        $workspace->update(['owner_id' => $newOwner->id]);

        // Update old owner's role to admin
        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('role', 'owner')
            ->update(['role' => 'admin']);

        // Update new owner's role to owner
        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $newOwner->id)
            ->update(['role' => 'owner']);

        return true;
    }
}

<?php

namespace App\Repositories;

use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceInvitationRepository
{
    /**
     * Create a new invitation
     */
    public function create(array $data): WorkspaceInvitation
    {
        return WorkspaceInvitation::create($data);
    }

    /**
     * Find invitation by ID
     */
    public function find(int $id): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::find($id);
    }

    /**
     * Find invitation by token
     */
    public function findByToken(string $token): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::where('token', $token)->first();
    }

    /**
     * Find valid invitation by token
     */
    public function findValidByToken(string $token): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Get pending invitations for a workspace
     */
    public function getPendingForWorkspace(Workspace $workspace): Collection
    {
        return WorkspaceInvitation::where('workspace_id', $workspace->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending invitation for email in workspace
     */
    public function findPendingForEmail(Workspace $workspace, string $email): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::where('workspace_id', $workspace->id)
            ->where('email', strtolower($email))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Get all pending invitations for an email
     */
    public function getPendingForEmail(string $email): Collection
    {
        return WorkspaceInvitation::where('email', strtolower($email))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with(['workspace', 'inviter'])
            ->get();
    }

    /**
     * Mark invitation as accepted
     */
    public function markAccepted(WorkspaceInvitation $invitation): bool
    {
        return $invitation->update(['accepted_at' => now()]);
    }

    /**
     * Delete invitation
     */
    public function delete(WorkspaceInvitation $invitation): bool
    {
        return $invitation->delete();
    }

    /**
     * Delete all expired invitations
     */
    public function deleteExpired(): int
    {
        return WorkspaceInvitation::where('expires_at', '<', now())
            ->whereNull('accepted_at')
            ->delete();
    }

    /**
     * Resend invitation (update expiry)
     */
    public function resend(WorkspaceInvitation $invitation): bool
    {
        return $invitation->update([
            'expires_at' => now()->addDays(WorkspaceInvitation::EXPIRATION_DAYS),
        ]);
    }
}

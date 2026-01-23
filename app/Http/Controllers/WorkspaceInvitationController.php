<?php

namespace App\Http\Controllers;

use App\Managers\WorkspaceManager;
use App\Repositories\WorkspaceInvitationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WorkspaceInvitationController extends Controller
{
    public function __construct(
        protected WorkspaceManager $workspaceManager,
        protected WorkspaceInvitationRepository $invitations
    ) {}

    /**
     * Get invitation details by token (public - anyone with token can view)
     */
    public function show(string $token): JsonResponse
    {
        $invitation = $this->workspaceManager->getInvitationByToken($token);

        if (! $invitation) {
            return response()->json(['message' => 'Invitation not found or expired'], 404);
        }

        return response()->json([
            'invitation' => $invitation,
        ]);
    }

    /**
     * Accept invitation
     */
    public function accept(string $token): JsonResponse
    {
        $invitation = $this->invitations->findValidByToken($token);

        if (! $invitation) {
            return response()->json(['message' => 'Invitation not found or expired'], 422);
        }

        try {
            $workspace = $this->workspaceManager->acceptInvitation($invitation, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Invitation accepted successfully',
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
        ]);
    }

    /**
     * Create a new invitation
     */
    public function store(string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only admins can invite members
        if (! $workspace->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = request()->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,member',
        ]);

        try {
            $invitation = $this->workspaceManager->inviteMember(
                $workspace,
                $validated['email'],
                $validated['role'],
                Auth::user()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Invitation sent successfully',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * List pending invitations for workspace
     */
    public function index(string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only admins can view invitations
        if (! $workspace->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $invitations = $this->workspaceManager->getPendingInvitations($workspace);

        return response()->json([
            'invitations' => $invitations,
        ]);
    }

    /**
     * Cancel invitation
     */
    public function destroy(string $slug, int $invitationId): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only admins can cancel invitations
        if (! $workspace->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $invitation = $this->invitations->find($invitationId);

        if (! $invitation || $invitation->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Invitation not found'], 404);
        }

        $this->workspaceManager->cancelInvitation($invitation);

        return response()->json([
            'message' => 'Invitation cancelled successfully',
        ]);
    }

    /**
     * Resend invitation
     */
    public function resend(string $slug, int $invitationId): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only admins can resend invitations
        if (! $workspace->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $invitation = $this->invitations->find($invitationId);

        if (! $invitation || $invitation->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Invitation not found'], 404);
        }

        $invitation = $this->workspaceManager->resendInvitation($invitation);

        return response()->json([
            'message' => 'Invitation resent successfully',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
        ]);
    }
}

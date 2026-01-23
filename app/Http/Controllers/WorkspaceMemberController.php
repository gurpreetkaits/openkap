<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Managers\WorkspaceManager;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WorkspaceMemberController extends Controller
{
    public function __construct(
        protected WorkspaceManager $workspaceManager
    ) {}

    /**
     * List workspace members
     */
    public function index(string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Any member can view the member list
        if (! $workspace->hasMember(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $members = $this->workspaceManager->getWorkspaceMembers($workspace);

        return response()->json([
            'members' => $members,
        ]);
    }

    /**
     * Invite a new member
     */
    public function invite(InviteMemberRequest $request, string $slug): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only admins can invite
        if (! $workspace->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Check workspace has active subscription for team features
        if (! $workspace->hasActiveSubscription()) {
            return response()->json([
                'message' => 'A team subscription is required to invite members.',
            ], 422);
        }

        try {
            $invitation = $this->workspaceManager->inviteMember(
                $workspace,
                $request->validated('email'),
                $request->validated('role'),
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
     * Update member role
     */
    public function update(UpdateMemberRoleRequest $request, string $slug, int $userId): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only owner can change roles
        if (! $workspace->isOwner(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::findOrFail($userId);

        try {
            $this->workspaceManager->updateMemberRole(
                $workspace,
                $user,
                $request->validated('role')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Member role updated successfully',
        ]);
    }

    /**
     * Remove a member
     */
    public function destroy(string $slug, int $userId): JsonResponse
    {
        $workspace = $this->workspaceManager->findBySlugOrFail($slug);

        // Only admins can remove members
        if (! $workspace->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::findOrFail($userId);

        try {
            $this->workspaceManager->removeMember($workspace, $user, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }
}

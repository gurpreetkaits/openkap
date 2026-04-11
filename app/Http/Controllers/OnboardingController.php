<?php

namespace App\Http\Controllers;

use App\Managers\WorkspaceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(private WorkspaceManager $workspaceManager) {}

    /**
     * Complete onboarding: save survey data and create workspace.
     */
    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'heard_from'        => 'required|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'workspace_name'    => 'required|string|min:2|max:100',
        ]);

        $user = $request->user();

        // Save survey fields
        $user->update([
            'heard_from'        => $validated['heard_from'],
            'organization_name' => $validated['organization_name'] ?? null,
            'onboarded_at'      => now(),
        ]);

        // Create workspace
        $workspace = $this->workspaceManager->createWorkspace($user, [
            'name' => $validated['workspace_name'],
        ]);

        return response()->json([
            'message'   => 'Onboarding complete',
            'workspace' => [
                'id'   => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
        ], 201);
    }
}

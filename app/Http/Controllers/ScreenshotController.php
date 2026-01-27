<?php

namespace App\Http\Controllers;

use App\Managers\ScreenshotManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScreenshotController extends Controller
{
    public function __construct(
        protected ScreenshotManager $screenshotManager
    ) {}

    /**
     * List all screenshots for the authenticated user.
     */
    public function index(Request $request)
    {
        $screenshots = $this->screenshotManager->getUserScreenshots(Auth::id());

        return response()->json([
            'screenshots' => $screenshots,
        ]);
    }

    /**
     * Store a new screenshot.
     */
    public function store(Request $request)
    {
        Log::info('ScreenshotController::store called', [
            'user_id' => Auth::id(),
            'has_image' => $request->hasFile('image'),
        ]);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'image' => 'required|file|mimes:png,jpg,jpeg,webp|max:10240', // 10MB max
        ]);

        $screenshot = $this->screenshotManager->createScreenshot(
            Auth::user(),
            $request->file('image'),
            $request->input('title')
        );

        return response()->json([
            'message' => 'Screenshot uploaded successfully',
            'screenshot' => $this->screenshotManager->getScreenshotDetails($screenshot),
        ], 201);
    }

    /**
     * Get a specific screenshot.
     */
    public function show($id)
    {
        $screenshot = $this->screenshotManager->findScreenshot($id);

        if (! $screenshot) {
            return response()->json(['message' => 'Screenshot not found'], 404);
        }

        if ($screenshot->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'screenshot' => $this->screenshotManager->getScreenshotDetails($screenshot),
        ]);
    }

    /**
     * Update a screenshot.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
        ]);

        $screenshot = $this->screenshotManager->findScreenshotOrFail($id);

        if ($screenshot->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $screenshot = $this->screenshotManager->updateScreenshot($screenshot, $request->only(['title']));

        return response()->json([
            'message' => 'Screenshot updated successfully',
            'screenshot' => $this->screenshotManager->getScreenshotDetails($screenshot),
        ]);
    }

    /**
     * Delete a screenshot.
     */
    public function destroy($id)
    {
        $screenshot = $this->screenshotManager->findScreenshotOrFail($id);

        if ($screenshot->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->screenshotManager->deleteScreenshot($screenshot, Auth::user());

        return response()->json([
            'message' => 'Screenshot deleted successfully',
        ]);
    }

    /**
     * Toggle sharing for a screenshot.
     */
    public function toggleSharing($id)
    {
        $screenshot = $this->screenshotManager->findScreenshotOrFail($id);

        if ($screenshot->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $screenshot = $this->screenshotManager->toggleSharing($screenshot);

        return response()->json([
            'message' => $screenshot->is_public ? 'Sharing enabled' : 'Sharing disabled',
            'is_public' => $screenshot->is_public,
            'share_url' => $screenshot->is_public ? $screenshot->getShareUrl() : null,
        ]);
    }

    /**
     * View a shared screenshot (public access).
     */
    public function viewShared($token)
    {
        $screenshot = $this->screenshotManager->findByShareToken($token);

        if (! $screenshot) {
            return response()->json(['message' => 'Screenshot not found'], 404);
        }

        if (! $screenshot->is_public) {
            return response()->json(['message' => 'This screenshot is not publicly shared'], 403);
        }

        return response()->json([
            'screenshot' => [
                'id' => $screenshot->id,
                'title' => $screenshot->title,
                'image_url' => $screenshot->getImageUrl(),
                'created_at' => $screenshot->created_at->toISOString(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeedbackResource;
use App\Managers\FeedbackManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\RateLimiter;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackManager $feedbackManager
    ) {}

    /**
     * Store new feedback (rate limited: 3 per hour per user)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Rate limiting: 3 feedback per hour per user
        $key = 'feedback:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many feedback submissions. Please try again in '.ceil($seconds / 60).' minutes.',
            ], 429);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:bug,feature,general',
            'description' => 'required|string|max:5000',
        ]);

        $feedback = $this->feedbackManager->submitFeedback(
            $user,
            $validated['title'],
            $validated['type'],
            $validated['description']
        );

        RateLimiter::hit($key, 3600); // 1 hour

        return response()->json([
            'message' => 'Thank you for your feedback!',
            'feedback' => new FeedbackResource($feedback),
        ], 201);
    }

    /**
     * Get user's feedback history (paginated)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $feedback = $this->feedbackManager->getUserFeedback($request->user()->id);

        return FeedbackResource::collection($feedback);
    }

    /**
     * Delete user's own feedback (only pending status)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->feedbackManager->deleteFeedback($id, $request->user()->id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Feedback not found or cannot be deleted.',
            ], 404);
        }

        return response()->json([
            'message' => 'Feedback deleted successfully.',
        ]);
    }
}

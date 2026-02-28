<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareVideoRequest;
use App\Jobs\CreateBugInIntegrationJob;
use App\Managers\IntegrationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function __construct(
        protected IntegrationManager $integrationManager,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $integrations = $this->integrationManager->getAvailableProviders($user->id);

        return response()->json([
            'integrations' => $integrations,
        ]);
    }

    public function availableProviders(): JsonResponse
    {
        $factory = app(\App\Services\Integrations\IntegrationProviderFactory::class);

        return response()->json([
            'providers' => $factory->getSupportedProviders(),
        ]);
    }

    public function connect(Request $request, string $provider): JsonResponse
    {
        try {
            $url = $this->integrationManager->initiateOAuth($request->user(), $provider);

            return response()->json(['url' => $url]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function callback(Request $request, string $provider): mixed
    {
        $code = $request->input('code') ?? $request->input('token');
        $state = $request->input('state');

        if (! $code || ! $state) {
            $frontendUrl = config('services.frontend.url');

            return redirect("{$frontendUrl}/integrations?error=missing_params");
        }

        try {
            $this->integrationManager->handleOAuthCallback($provider, $code, $state);
            $frontendUrl = config('services.frontend.url');

            return redirect("{$frontendUrl}/integrations?connected={$provider}");
        } catch (\Exception $e) {
            $frontendUrl = config('services.frontend.url');

            return redirect("{$frontendUrl}/integrations?error=".urlencode($e->getMessage()));
        }
    }

    public function disconnect(Request $request, string $provider): JsonResponse
    {
        $result = $this->integrationManager->disconnectIntegration($request->user(), $provider);

        if (! $result) {
            return response()->json(['message' => 'Integration not found'], 404);
        }

        return response()->json(['message' => 'Integration disconnected successfully']);
    }

    public function targets(Request $request, string $provider): JsonResponse
    {
        try {
            $targets = $this->integrationManager->getIntegrationTargets($request->user(), $provider);

            return response()->json(['targets' => $targets]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function shareVideo(ShareVideoRequest $request, string $provider, int $videoId): JsonResponse
    {
        try {
            $result = $this->integrationManager->shareVideoToIntegration(
                $request->user(),
                $videoId,
                $provider,
                $request->validated(),
            );

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createBug(Request $request, string $provider, int $videoId): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => 'required|string',
            'bug_id' => 'nullable|string',
            'bug_title' => 'required|string|max:255',
            'bug_description' => 'nullable|string',
            'bug_severity' => 'nullable|string|in:critical,high,medium,low',
            'steps_to_reproduce' => 'nullable|array',
            'steps_to_reproduce.*' => 'string',
        ]);

        CreateBugInIntegrationJob::dispatch(
            $request->user(),
            $videoId,
            $provider,
            $validated,
        );

        return response()->json([
            'success' => true,
            'message' => 'Bug is being created in Jira. You\'ll see it in your project shortly.',
        ]);
    }

    public function shareHistory(Request $request, int $videoId): JsonResponse
    {
        $history = $this->integrationManager->getVideoShareHistory($videoId, $request->user()->id);

        return response()->json(['history' => $history]);
    }
}

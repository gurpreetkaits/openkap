<?php

namespace App\Http\Controllers;

use App\Managers\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Polar\Models\Errors\APIException as PolarAPIException;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionManager $subscriptionManager
    ) {}

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'subscription' => $this->subscriptionManager->getSubscriptionStatus($request->user()),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json([
            'history' => $this->subscriptionManager->getSubscriptionHistory($request->user()),
        ]);
    }

    public function createCheckout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'sometimes|in:monthly,yearly,teams_monthly',
        ]);

        $plan = $request->input('plan', 'monthly');

        // Surface a clean 503 instead of a raw 500 when billing isn't configured
        // on this instance (e.g. self-hosted without Polar credentials).
        if (! $this->isBillingConfigured($plan)) {
            return response()->json([
                'error' => 'Billing is not configured on this instance. Please contact the administrator.',
            ], 503);
        }

        try {
            $result = $this->subscriptionManager->createCheckout($request->user(), $plan);

            return response()->json($result);
        } catch (PolarAPIException $e) {
            // Polar SDK swallows the response body inside a generic "API error occurred"
            // message — log the real status + body so we don't have to tinker to diagnose.
            Log::error('Polar checkout failed', [
                'user_id' => $request->user()?->id,
                'plan' => $plan,
                'status' => $e->statusCode ?? null,
                'body' => $e->body ?? null,
                'message' => $e->getMessage(),
            ]);

            $isAuthError = ($e->statusCode ?? null) === 401;

            return response()->json([
                'error' => $isAuthError
                    ? 'Billing service is temporarily unavailable. Please try again later or contact support.'
                    : 'We couldn\'t start your checkout. Please try again in a moment.',
            ], 502);
        } catch (\Exception $e) {
            Log::error('Checkout failed', [
                'user_id' => $request->user()?->id,
                'plan' => $plan,
                'exception' => $e,
            ]);

            return response()->json(['error' => 'We couldn\'t start your checkout. Please try again in a moment.'], 500);
        }
    }

    private function isBillingConfigured(string $plan): bool
    {
        if (empty(config('services.polar.api_key'))) {
            return false;
        }

        $productKey = match ($plan) {
            'yearly' => 'services.polar.product_id_yearly',
            'teams_monthly' => 'services.polar.product_id_teams_monthly',
            default => 'services.polar.product_id_monthly',
        };

        return ! empty(config($productKey));
    }

    public function getCheckoutUrl(Request $request): JsonResponse
    {
        return $this->createCheckout($request);
    }

    public function handleCheckoutSuccess(Request $request): JsonResponse
    {
        $checkoutId = $request->input('checkout_id');

        if (! $checkoutId) {
            return response()->json(['error' => 'Missing checkout_id parameter'], 400);
        }

        try {
            $subscription = $this->subscriptionManager->handleCheckoutSuccess(
                $request->user(),
                $checkoutId
            );

            return response()->json([
                'success' => true,
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        try {
            $result = $this->subscriptionManager->cancelSubscription($request->user());

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPortalUrl(Request $request): JsonResponse
    {
        try {
            $portalUrl = $this->subscriptionManager->getPortalUrl($request->user());

            return response()->json(['portal_url' => $portalUrl]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

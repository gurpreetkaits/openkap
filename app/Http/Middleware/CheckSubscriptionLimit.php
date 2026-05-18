<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimit
{
    /**
     * Handle an incoming request to check if user can record videos
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'unauthenticated',
                'message' => 'Authentication required to record videos.',
            ], 401);
        }

        // Check if user can record more videos
        if (! $user->canRecordVideo()) {
            $subscription = $user->subscription();
            $currentPlan = $subscription && $subscription->active() ? 'pro' : 'free';
            $minutesCapped = $user->hasExceededMonthlyRecordingMinutes();

            return response()->json([
                'error' => $minutesCapped ? 'monthly_minutes_limit_reached' : 'video_limit_reached',
                'message' => 'Limit Reached',
                'detail' => $minutesCapped
                    ? 'You have used all of your monthly recording minutes. They will reset at the start of next month.'
                    : 'You have reached your video limit. Upgrade to Pro to continue recording.',
                'current_plan' => $currentPlan,
                'is_active' => $user->hasActiveSubscription(),
                'videos_count' => $user->getVideosCount(),
                'remaining_quota' => $user->getRemainingVideoQuota(),
                'monthly_recording_minutes_used' => $user->getMonthlyRecordingMinutesUsed(),
                'monthly_recording_minutes_limit' => $user->getMonthlyRecordingMinutesLimit(),
                'remaining_monthly_recording_minutes' => $user->getRemainingMonthlyRecordingMinutes(),
                'upgrade_url' => config('services.frontend.url').'/subscription',
            ], 403);
        }

        return $next($request);
    }
}

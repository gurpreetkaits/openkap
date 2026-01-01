<?php

namespace App\Http\Controllers;

use App\Managers\SettingManager;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(
        private SettingManager $settingManager
    ) {}

    /**
     * Get public settings (subscription prices, limits)
     * This endpoint doesn't require authentication
     */
    public function publicSettings(): JsonResponse
    {
        $subscriptionSettings = $this->settingManager->getSubscriptionSettings();
        $freePlanLimits = $this->settingManager->getFreePlanLimits();

        return response()->json([
            'subscription' => $subscriptionSettings,
            'free_plan' => $freePlanLimits,
        ]);
    }
}

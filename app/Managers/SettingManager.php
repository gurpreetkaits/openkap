<?php

namespace App\Managers;

use App\Models\Setting;
use App\Repositories\SettingRepository;
use Illuminate\Support\Collection;

class SettingManager
{
    public function __construct(
        private SettingRepository $settingRepository
    ) {}

    /**
     * Get a setting value by key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settingRepository->getValue($key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): Setting
    {
        return $this->settingRepository->setValue($key, $value, $type, $group, $description);
    }

    /**
     * Get all settings for a group
     */
    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    /**
     * Get all settings
     */
    public function getAll(): Collection
    {
        return $this->settingRepository->getAll();
    }

    /**
     * Get subscription settings for frontend
     */
    public function getSubscriptionSettings(): array
    {
        return [
            'free_video_limit' => Setting::getFreeVideoLimit(),
            'free_recording_duration_limit' => Setting::getFreeRecordingDurationLimit(),
            'monthly_price' => Setting::getMonthlyPrice(),
            'yearly_price' => Setting::getYearlyPrice(),
            'yearly_monthly_price' => round(Setting::getYearlyPrice() / 12, 2),
            'yearly_savings_percent' => $this->calculateYearlySavings(),
        ];
    }

    /**
     * Calculate yearly savings percentage
     */
    private function calculateYearlySavings(): int
    {
        $monthlyPrice = Setting::getMonthlyPrice();
        $yearlyPrice = Setting::getYearlyPrice();
        $yearlyMonthlyEquivalent = $monthlyPrice * 12;

        if ($yearlyMonthlyEquivalent <= 0) {
            return 0;
        }

        return (int) round((($yearlyMonthlyEquivalent - $yearlyPrice) / $yearlyMonthlyEquivalent) * 100);
    }

    /**
     * Get free plan limits
     */
    public function getFreePlanLimits(): array
    {
        return [
            'max_videos' => Setting::getFreeVideoLimit(),
            'max_duration_seconds' => Setting::getFreeRecordingDurationLimit(),
            'max_duration_minutes' => (int) round(Setting::getFreeRecordingDurationLimit() / 60),
        ];
    }
}

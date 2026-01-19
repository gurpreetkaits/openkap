<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Danestves\LaravelPolar\Billable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
        'username',
        'bio',
        'avatar',
        'website',
        'location',
        'email_verified_at',
        // Subscription fields
        'polar_customer_id',
        'polar_subscription_id',
        'subscription_status',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_canceled_at',
        'polar_product_id',
        'polar_price_id',
        'videos_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Hide Polar sensitive IDs
        'polar_customer_id',
        'polar_subscription_id',
        'polar_product_id',
        'polar_price_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Subscription timestamp casts
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'subscription_canceled_at' => 'datetime',
            'videos_count' => 'integer',
        ];
    }

    /**
     * Check if user has an active paid subscription
     * Uses the package's subscribed() method with fallback to old subscription_status field
     */
    public function hasActiveSubscription(): bool
    {
        // Try using the package's subscription relationship first
        $subscription = $this->subscription();
        if ($subscription) {
            return $subscription->active() || $subscription->onGracePeriod();
        }

        // Fall back to the old subscription_status field for backward compatibility
        return $this->subscription_status === 'active';
    }

    /**
     * Check if user can record more videos based on subscription status
     */
    public function canRecordVideo(): bool
    {
        // Paid users have unlimited recording
        if ($this->hasActiveSubscription()) {
            return true;
        }

        // Free users limited by admin setting
        $limit = Setting::getFreeVideoLimit();
        $actualVideoCount = $this->videos()->count();

        return $actualVideoCount < $limit;
    }

    /**
     * Get remaining video quota
     * Returns null for unlimited, otherwise remaining count
     */
    public function getRemainingVideoQuota(): ?int
    {
        if ($this->hasActiveSubscription()) {
            return null; // Unlimited
        }

        // Free tier: limited by admin setting
        $limit = Setting::getFreeVideoLimit();
        $actualVideoCount = $this->videos()->count();
        $remaining = $limit - $actualVideoCount;

        return max(0, $remaining);
    }

    /**
     * Get max videos allowed for free plan
     */
    public function getMaxVideos(): ?int
    {
        if ($this->hasActiveSubscription()) {
            return null; // Unlimited
        }

        return Setting::getFreeVideoLimit();
    }

    /**
     * Check if subscription is in grace period (canceled but not expired)
     * Uses the package's onGracePeriod() method with fallback
     */
    public function isSubscriptionInGracePeriod(): bool
    {
        // Try using the package's subscription relationship first
        $subscription = $this->subscription();
        if ($subscription) {
            return $subscription->onGracePeriod();
        }

        // Fall back to the old logic for backward compatibility
        if ($this->subscription_status !== 'canceled') {
            return false;
        }

        if (! $this->subscription_expires_at) {
            return false;
        }

        return $this->subscription_expires_at->isFuture();
    }

    /**
     * Get video count (returns actual count from database)
     */
    public function getVideosCount(): int
    {
        // Always return actual count from database for accuracy
        return $this->videos()->count();
    }

    /**
     * Recalculate and sync video count from database
     */
    public function syncVideosCount(): int
    {
        $count = $this->videos()->count();
        $this->update(['videos_count' => $count]);

        return $count;
    }

    /**
     * Relationship: User has many videos
     */
    public function videos()
    {
        return $this->hasMany(\App\Models\Video::class);
    }

    /**
     * Relationship: User has many settings (key-value pairs)
     */
    public function settings()
    {
        return $this->hasMany(UserSetting::class);
    }

    /**
     * Get a specific setting value for this user.
     */
    public function getSetting(string $key): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();

        if ($setting) {
            return $setting->typed_value;
        }

        return UserSetting::getDefault($key);
    }

    /**
     * Get user settings or defaults if not set.
     */
    public function getSettingsOrDefaults(): array
    {
        $settings = $this->settings()->pluck('value', 'key')->toArray();
        $defaults = UserSetting::getDefaults();
        $result = [];

        foreach ($defaults as $key => $default) {
            if (isset($settings[$key])) {
                $result[$key] = UserSetting::castValue($settings[$key], $default['type']);
            } else {
                $result[$key] = $default['value'];
            }
        }

        return $result;
    }

    /**
     * Check if auto zoom is enabled for this user.
     */
    public function isAutoZoomEnabled(): bool
    {
        return (bool) $this->getSetting('auto_zoom_enabled');
    }

    /**
     * Relationship: User has many subscription history records
     */
    public function subscriptionHistory()
    {
        return $this->hasMany(SubscriptionHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relationship: User has many notifications
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relationship: User has many playlists
     */
    public function playlists()
    {
        return $this->hasMany(\App\Models\Playlist::class);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCount(): int
    {
        return $this->notifications()->where('read', false)->count();
    }
}

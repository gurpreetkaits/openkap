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
     * Get the user's plan type based on their subscription product
     * Returns: 'free', 'pro', or 'teams'
     */
    public function getPlanType(): string
    {
        if (! $this->hasActiveSubscription()) {
            return 'free';
        }

        // Get product ID from the package subscription or fallback to user field
        $subscription = $this->subscription();
        $productId = $subscription?->product_id ?? $this->polar_product_id;

        // Check if it's a teams product
        $teamsProductId = config('services.polar.product_id_teams_monthly');
        if ($teamsProductId && $productId === $teamsProductId) {
            return 'teams';
        }

        // Default to pro for any other active subscription
        return 'pro';
    }

    /**
     * Check if user has a Teams subscription
     */
    public function hasTeamsSubscription(): bool
    {
        return $this->getPlanType() === 'teams';
    }

    /**
     * Check if user has a Pro subscription
     */
    public function hasProSubscription(): bool
    {
        return $this->getPlanType() === 'pro';
    }

    /**
     * Recalculate and sync video count from database
     */
    public function syncVideosCount(): int
    {
        $count = $this->videos()->count();
        $this->forceFill(['videos_count' => $count])->save();

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
     * Relationship: User has many chat conversations
     */
    public function chatConversations()
    {
        return $this->hasMany(ChatConversation::class);
    }

    /**
     * Relationship: User has many integrations
     */
    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCount(): int
    {
        return $this->notifications()->where('read', false)->count();
    }

    // ==================
    // Workspace Relationships
    // ==================

    /**
     * Workspaces owned by this user
     */
    public function ownedWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    /**
     * All workspaces this user is a member of (including owned)
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot(['role', 'joined_at', 'invited_by']);
    }

    /**
     * Get workspaces where user is admin or owner
     */
    public function adminWorkspaces()
    {
        return $this->workspaces()
            ->wherePivotIn('role', ['owner', 'admin']);
    }

    /**
     * Check if user is member of a workspace
     */
    public function isMemberOf(Workspace $workspace): bool
    {
        return $this->workspaces()->where('workspaces.id', $workspace->id)->exists();
    }

    /**
     * Check if user is owner of a workspace
     */
    public function isOwnerOf(Workspace $workspace): bool
    {
        return $workspace->owner_id === $this->id;
    }

    /**
     * Check if user is admin of a workspace
     */
    public function isAdminOf(Workspace $workspace): bool
    {
        return $workspace->isAdmin($this);
    }

    /**
     * Get user's role in a workspace
     */
    public function getRoleIn(Workspace $workspace): ?string
    {
        return $workspace->getUserRole($this);
    }

    /**
     * Check if user can record in a specific context (personal or workspace)
     */
    public function canRecordIn(?Workspace $workspace = null): bool
    {
        if ($workspace) {
            // Workspace context: check workspace subscription
            return $workspace->canMemberRecord($this);
        }

        // Personal context: check user's own subscription
        return $this->canRecordVideo();
    }

    /**
     * Get max recording duration in seconds based on context
     */
    public function getMaxRecordingSeconds(?Workspace $workspace = null): int
    {
        if ($workspace && $workspace->hasActiveSubscription()) {
            return $workspace->getMaxRecordingSeconds();
        }

        // Personal context
        if ($this->hasActiveSubscription()) {
            return 30 * 60; // 30 minutes for Pro
        }

        return Setting::getFreeRecordingDurationLimit(); // 5 minutes for Free
    }

    /**
     * Check if user should get HLS encoding (paid plan only)
     */
    public function shouldEncodeVideos(?Workspace $workspace = null): bool
    {
        if ($workspace) {
            return $workspace->hasActiveSubscription();
        }

        return $this->hasActiveSubscription();
    }
}

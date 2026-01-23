<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'owner_id',
        'polar_customer_id',
        'polar_subscription_id',
        'subscription_status',
        'subscription_plan',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_canceled_at',
        'max_members',
        'max_storage_gb',
        'max_recording_minutes',
        'storage_used_bytes',
    ];

    protected $casts = [
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'subscription_canceled_at' => 'datetime',
        'max_members' => 'integer',
        'max_storage_gb' => 'integer',
        'max_recording_minutes' => 'integer',
        'storage_used_bytes' => 'integer',
    ];

    protected $hidden = [
        'polar_customer_id',
        'polar_subscription_id',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Workspace $workspace) {
            if (empty($workspace->slug)) {
                $workspace->slug = static::generateUniqueSlug($workspace->name);
            }
        });
    }

    /**
     * Generate a unique slug from workspace name
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the route key name for Laravel's implicit model binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ==================
    // Relationships
    // ==================

    /**
     * Owner of the workspace
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * All members of the workspace (including owner)
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot(['role', 'joined_at', 'invited_by']);
    }

    /**
     * Workspace member records
     */
    public function memberRecords(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /**
     * Videos in this workspace
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Pending invitations
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /**
     * Pending (not accepted) invitations
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    // ==================
    // Subscription Helpers
    // ==================

    /**
     * Check if workspace has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active'
            || $this->isInGracePeriod();
    }

    /**
     * Check if subscription is in grace period (canceled but not expired)
     */
    public function isInGracePeriod(): bool
    {
        return $this->subscription_status === 'canceled'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    /**
     * Get the plan name
     */
    public function getPlanName(): string
    {
        if (! $this->hasActiveSubscription()) {
            return 'free';
        }

        return $this->subscription_plan ?? 'team';
    }

    /**
     * Check if workspace is on Team Plus plan
     */
    public function isTeamPlus(): bool
    {
        return $this->hasActiveSubscription()
            && $this->subscription_plan === 'team_plus';
    }

    // ==================
    // Member Helpers
    // ==================

    /**
     * Check if user is a member of this workspace
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is owner of this workspace
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Check if user is admin (or owner) of this workspace
     */
    public function isAdmin(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->where('workspace_members.role', 'admin')
            ->exists();
    }

    /**
     * Get user's role in workspace
     */
    public function getUserRole(User $user): ?string
    {
        if ($this->isOwner($user)) {
            return 'owner';
        }

        $member = $this->members()->where('user_id', $user->id)->first();

        return $member?->pivot?->role;
    }

    /**
     * Get current member count
     */
    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    /**
     * Check if workspace can add more members
     */
    public function canAddMembers(): bool
    {
        return $this->getMemberCount() < $this->max_members;
    }

    /**
     * Get remaining member slots
     */
    public function getRemainingSlots(): int
    {
        return max(0, $this->max_members - $this->getMemberCount());
    }

    // ==================
    // Storage Helpers
    // ==================

    /**
     * Get storage used in GB
     */
    public function getStorageUsedGb(): float
    {
        return round($this->storage_used_bytes / (1024 * 1024 * 1024), 2);
    }

    /**
     * Get storage usage percentage
     */
    public function getStorageUsagePercent(): float
    {
        if ($this->max_storage_gb <= 0) {
            return 0;
        }

        return min(100, round(($this->getStorageUsedGb() / $this->max_storage_gb) * 100, 1));
    }

    /**
     * Check if storage limit reached
     */
    public function isStorageFull(): bool
    {
        return $this->getStorageUsedGb() >= $this->max_storage_gb;
    }

    /**
     * Get remaining storage in bytes
     */
    public function getRemainingStorageBytes(): int
    {
        $maxBytes = $this->max_storage_gb * 1024 * 1024 * 1024;

        return max(0, $maxBytes - $this->storage_used_bytes);
    }

    /**
     * Update storage used (call after video upload/delete)
     */
    public function recalculateStorage(): void
    {
        $totalBytes = $this->videos()->sum('file_size_bytes');
        $this->update(['storage_used_bytes' => $totalBytes]);
    }

    // ==================
    // Recording Helpers
    // ==================

    /**
     * Check if a member can record (based on workspace subscription)
     */
    public function canMemberRecord(User $user): bool
    {
        if (! $this->hasMember($user)) {
            return false;
        }

        return $this->hasActiveSubscription();
    }

    /**
     * Get max recording duration in seconds
     */
    public function getMaxRecordingSeconds(): int
    {
        return $this->max_recording_minutes * 60;
    }
}

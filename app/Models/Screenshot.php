<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Screenshot extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'folder_id',
        'workspace_id',
        'title',
        'share_token',
        'is_public',
        'file_size_bytes',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'file_size_bytes' => 'integer',
    ];

    protected $hidden = [
        'share_token',
    ];

    /**
     * Boot the model and generate share token on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($screenshot) {
            if (! $screenshot->share_token) {
                $screenshot->share_token = Str::random(64);
            }
        });
    }

    /**
     * Get the user that owns the screenshot.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the folder this screenshot belongs to (if any).
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the workspace this screenshot belongs to (if any).
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Check if this screenshot belongs to a workspace.
     */
    public function isWorkspaceScreenshot(): bool
    {
        return $this->workspace_id !== null;
    }

    /**
     * Check if user can access this screenshot.
     */
    public function canBeAccessedBy(User $user): bool
    {
        // Owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // If workspace screenshot, check membership
        if ($this->isWorkspaceScreenshot()) {
            return $this->workspace->hasMember($user);
        }

        // Public screenshots can be accessed by anyone
        return $this->is_public;
    }

    /**
     * Check if user can edit this screenshot.
     */
    public function canBeEditedBy(User $user): bool
    {
        // Owner can always edit
        if ($this->user_id === $user->id) {
            return true;
        }

        // Workspace admins can edit any screenshot in the workspace
        if ($this->isWorkspaceScreenshot()) {
            return $this->workspace->isAdmin($user);
        }

        return false;
    }

    /**
     * Check if user can delete this screenshot.
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $this->canBeEditedBy($user);
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size_bytes ?? 0;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('screenshots')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->useDisk('public');

        $this->addMediaCollection('thumbnails')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->useDisk('public');
    }

    /**
     * Get the image URL for the screenshot.
     */
    public function getImageUrl(): ?string
    {
        $media = $this->getFirstMedia('screenshots');
        if ($media) {
            return $media->getUrl();
        }

        return null;
    }

    /**
     * Get the thumbnail URL for the screenshot.
     */
    public function getThumbnailUrl(): ?string
    {
        // Try to get thumbnail from thumbnails collection first
        $thumbnail = $this->getFirstMedia('thumbnails');
        if ($thumbnail) {
            return $thumbnail->getUrl();
        }

        // Fallback to main image
        return $this->getImageUrl();
    }

    /**
     * Generate a shareable URL for this screenshot.
     */
    public function getShareUrl(): string
    {
        return url("/share/screenshot/{$this->share_token}");
    }

    /**
     * Generate a frontend URL for this screenshot (for internal redirects).
     */
    public function getFrontendShareUrl(): string
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        return "{$frontendUrl}/share/screenshot/{$this->share_token}";
    }

    /**
     * Check if the share link is still valid.
     */
    public function isShareLinkValid(): bool
    {
        return $this->is_public;
    }

    /**
     * Regenerate the share token.
     */
    public function regenerateShareToken(): void
    {
        $this->share_token = Str::random(64);
        $this->save();
    }
}

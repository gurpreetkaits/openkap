<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'is_public',
        'share_token',
        'share_password',
        'sort_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected $hidden = [
        'share_token',
        'share_password',
    ];

    /**
     * Boot the model and generate share token on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($playlist) {
            if (! $playlist->share_token) {
                $playlist->share_token = Str::random(64);
            }
        });
    }

    /**
     * Get the user that owns the playlist.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the videos in this playlist.
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class)
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Get ordered videos based on sort_by setting.
     */
    public function getOrderedVideos()
    {
        if ($this->sort_by === 'date_added') {
            return $this->videos()->orderByPivot('created_at', 'desc')->get();
        }

        return $this->videos()->orderByPivot('position', 'asc')->get();
    }

    /**
     * Generate a shareable URL for this playlist.
     */
    public function getShareUrl(): string
    {
        return url("/share/playlist/{$this->share_token}");
    }

    /**
     * Generate a frontend URL for this playlist.
     */
    public function getFrontendShareUrl(): string
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        return "{$frontendUrl}/share/playlist/{$this->share_token}";
    }

    /**
     * Check if the playlist has a share password set.
     */
    public function hasPassword(): bool
    {
        return $this->share_password !== null;
    }

    /**
     * Check if the share link is valid.
     */
    public function isShareLinkValid(): bool
    {
        return $this->is_public && $this->share_token !== null;
    }

    /**
     * Regenerate the share token.
     */
    public function regenerateShareToken(): void
    {
        $this->share_token = Str::random(64);
        $this->save();
    }

    /**
     * Get the video count for this playlist.
     */
    public function getVideoCountAttribute(): int
    {
        return $this->videos()->count();
    }
}

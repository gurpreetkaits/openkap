<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Video extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'duration',
        'user_id',
        'is_public',
        'is_favourite',
        'share_expires_at',
        'conversion_status',
        'original_extension',
        'conversion_progress',
        'conversion_error',
        'converted_at',
        'hls_status',
        'hls_progress',
        'hls_path',
        'hls_error',
        'hls_converted_at',
        'transcription_status',
        'transcription',
        'transcription_segments',
        'transcription_error',
        'transcription_progress',
        'transcription_generated_at',
        'summary_status',
        'summary',
        'summary_error',
        'summary_generated_at',
    ];

    protected $casts = [
        'duration' => 'integer',
        'is_public' => 'boolean',
        'is_favourite' => 'boolean',
        'share_expires_at' => 'datetime',
        'conversion_progress' => 'integer',
        'converted_at' => 'datetime',
        'hls_progress' => 'integer',
        'hls_converted_at' => 'datetime',
        'transcription_progress' => 'integer',
        'transcription_generated_at' => 'datetime',
        'transcription_segments' => 'array',
        'summary_generated_at' => 'datetime',
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

        static::creating(function ($video) {
            if (! $video->share_token) {
                $video->share_token = Str::random(64);
            }
        });
    }

    /**
     * Get the user that owns the video.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all comments for the video.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all reactions for the video.
     */
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Get all views for the video.
     */
    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    /**
     * Get all playlists that contain this video.
     */
    public function playlists()
    {
        return $this->belongsToMany(Playlist::class)
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Get unique view count (distinct users/IPs).
     */
    public function getViewCountAttribute(): int
    {
        return $this->views()->count();
    }

    /**
     * Alias for view_count to match frontend expectations.
     */
    public function getViewsCountAttribute(): int
    {
        return $this->view_count;
    }

    /**
     * Get unique viewer count.
     */
    public function getUniqueViewersAttribute(): int
    {
        return $this->views()
            ->distinct()
            ->count('COALESCE(user_id, ip_address)');
    }

    /**
     * Get reaction counts grouped by type.
     */
    public function getReactionCountsAttribute(): array
    {
        return $this->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('videos')
            ->singleFile()
            ->acceptsMimeTypes(['video/webm', 'video/mp4', 'video/quicktime'])
            ->withResponsiveImages(false)
            ->useDisk('public');

        $this->addMediaCollection('thumbnails')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->useDisk('public');
    }

    /**
     * Register media conversions.
     * Generate video thumbnail using FFmpeg.
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // Thumbnails are generated manually in generateThumbnailFromMidpoint()
        // to have precise control over the timestamp
    }

    /**
     * Generate thumbnail from the midpoint of the video.
     */
    public function generateThumbnailFromMidpoint(): void
    {
        $videoMedia = $this->getFirstMedia('videos');
        if (! $videoMedia) {
            return;
        }

        $videoPath = $videoMedia->getPath();

        // Calculate midpoint timestamp
        $duration = $this->duration ?? 0;
        $midpoint = max(1, floor($duration / 2)); // Get half of duration, minimum 1 second

        // Generate thumbnail using FFmpeg
        try {
            $thumbnailPath = storage_path('app/temp/thumbnail-'.$this->id.'-'.time().'.jpg');

            // Ensure temp directory exists
            if (! file_exists(dirname($thumbnailPath))) {
                mkdir(dirname($thumbnailPath), 0755, true);
            }

            // Use FFmpeg to extract frame at midpoint
            // IMPORTANT: Use config() not env() - env() doesn't work when config is cached
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries' => config('media-library.ffmpeg_path'),
                'ffprobe.binaries' => config('media-library.ffprobe_path'),
                'timeout' => config('media-library.ffmpeg_timeout', 3600),
                'ffmpeg.threads' => config('media-library.ffmpeg_threads', 12),
            ]);

            $video = $ffmpeg->open($videoPath);
            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($midpoint));
            $frame->save($thumbnailPath);

            // Add thumbnail to media library
            if (file_exists($thumbnailPath)) {
                $this->addMedia($thumbnailPath)
                    ->toMediaCollection('thumbnails');

                // Clean up temp file (use @ to suppress errors if file was already moved by addMedia)
                @unlink($thumbnailPath);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to generate thumbnail for video '.$this->id.': '.$e->getMessage());
        }
    }

    /**
     * Get the thumbnail URL for the video.
     */
    public function getThumbnailUrl(): ?string
    {
        // Try to get thumbnail from thumbnails collection first
        $thumbnail = $this->getFirstMedia('thumbnails');
        if ($thumbnail) {
            return $thumbnail->getUrl();
        }

        // Fallback: return null (frontend can show placeholder)
        return null;
    }

    /**
     * Generate a shareable URL for this video.
     * Returns backend URL so social media crawlers can see OG meta tags.
     * The backend page (share.blade.php) redirects human users to the frontend via JS.
     */
    public function getShareUrl(): string
    {
        return url("/share/video/{$this->share_token}");
    }

    /**
     * Generate a frontend URL for this video (for internal redirects).
     */
    public function getFrontendShareUrl(): string
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        return "{$frontendUrl}/share/video/{$this->share_token}";
    }

    /**
     * Generate an embeddable URL for this video (for og:video tags).
     * Returns backend URL for the minimal embed player.
     */
    public function getEmbedUrl(): string
    {
        return url("/embed/video/{$this->share_token}");
    }

    /**
     * Check if the share link is still valid.
     */
    public function isShareLinkValid(): bool
    {
        if (! $this->is_public) {
            return false;
        }

        if ($this->share_expires_at && $this->share_expires_at->isPast()) {
            return false;
        }

        return true;
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
     * Check if the video is still being converted.
     */
    public function isConverting(): bool
    {
        return in_array($this->conversion_status, ['pending', 'processing']);
    }

    /**
     * Check if the video conversion is complete.
     */
    public function isConversionComplete(): bool
    {
        return $this->conversion_status === 'completed';
    }

    /**
     * Check if the video conversion failed.
     */
    public function isConversionFailed(): bool
    {
        return $this->conversion_status === 'failed';
    }

    /**
     * Get a human-readable conversion status message.
     */
    public function getConversionStatusMessage(): string
    {
        return match ($this->conversion_status) {
            'pending' => 'Waiting to process...',
            'processing' => "Converting... {$this->conversion_progress}%",
            'completed' => 'Ready for instant playback',
            'failed' => 'Conversion failed: '.($this->conversion_error ?? 'Unknown error'),
            default => 'Unknown status',
        };
    }

    /**
     * Check if HLS conversion is in progress.
     */
    public function isHlsConverting(): bool
    {
        return in_array($this->hls_status, ['pending', 'processing']);
    }

    /**
     * Check if HLS conversion is complete.
     */
    public function isHlsReady(): bool
    {
        return $this->hls_status === 'completed' && $this->hls_path !== null;
    }

    /**
     * Check if HLS conversion failed.
     */
    public function isHlsFailed(): bool
    {
        return $this->hls_status === 'failed';
    }

    /**
     * Get the HLS master playlist URL.
     * Returns API-based URL to ensure CORS headers are included.
     */
    public function getHlsUrl(): ?string
    {
        if (! $this->isHlsReady()) {
            return null;
        }

        // Use API route for CORS support (cross-origin playback)
        return url("/api/share/video/{$this->share_token}/hls/master.m3u8");
    }

    /**
     * Get HLS status message.
     */
    public function getHlsStatusMessage(): string
    {
        return match ($this->hls_status) {
            'pending' => 'Waiting for HLS conversion...',
            'processing' => "Converting to HLS... {$this->hls_progress}%",
            'completed' => 'HLS streaming ready',
            'failed' => 'HLS conversion failed: '.($this->hls_error ?? 'Unknown error'),
            default => 'Unknown status',
        };
    }

    /**
     * Check if transcription is in progress.
     */
    public function isTranscribing(): bool
    {
        return in_array($this->transcription_status, ['pending', 'processing']);
    }

    /**
     * Check if transcription is complete.
     */
    public function isTranscriptionReady(): bool
    {
        return $this->transcription_status === 'completed' && $this->transcription !== null;
    }

    /**
     * Check if transcription failed.
     */
    public function isTranscriptionFailed(): bool
    {
        return $this->transcription_status === 'failed';
    }

    /**
     * Check if summary is in progress.
     */
    public function isSummarizing(): bool
    {
        return in_array($this->summary_status, ['pending', 'processing']);
    }

    /**
     * Check if summary is complete.
     */
    public function isSummaryReady(): bool
    {
        return $this->summary_status === 'completed' && $this->summary !== null;
    }

    /**
     * Check if summary failed.
     */
    public function isSummaryFailed(): bool
    {
        return $this->summary_status === 'failed';
    }

    /**
     * Get transcription status message.
     */
    public function getTranscriptionStatusMessage(): string
    {
        return match ($this->transcription_status) {
            'pending' => 'Waiting for transcription...',
            'processing' => "Transcribing... {$this->transcription_progress}%",
            'completed' => 'Transcription ready',
            'failed' => 'Transcription failed: '.($this->transcription_error ?? 'Unknown error'),
            default => 'Unknown status',
        };
    }

    /**
     * Get summary status message.
     */
    public function getSummaryStatusMessage(): string
    {
        return match ($this->summary_status) {
            'pending' => 'Waiting for summary...',
            'processing' => 'Generating summary...',
            'completed' => 'Summary ready',
            'failed' => 'Summary failed: '.($this->summary_error ?? 'Unknown error'),
            default => 'Unknown status',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoZoomSetting extends Model
{
    protected $fillable = [
        'video_id',
        'enabled',
        'zoom_level',
        'duration_ms',
        'events',
        'recording_resolution',
        'status',
        'progress',
        'error',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'zoom_level' => 'float',
        'duration_ms' => 'integer',
        'events' => 'array',
        'recording_resolution' => 'array',
        'progress' => 'integer',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    // ============================================
    // STATUS HELPERS
    // ============================================

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getStatusMessage(): string
    {
        return match ($this->status) {
            'pending' => 'Waiting for zoom processing...',
            'processing' => "Applying zoom effects... {$this->progress}%",
            'completed' => 'Zoom effects applied',
            'failed' => 'Zoom processing failed: '.($this->error ?? 'Unknown error'),
            default => 'Unknown status',
        };
    }

    // ============================================
    // EVENT HELPERS
    // ============================================

    public function getEventCount(): int
    {
        return is_array($this->events) ? count($this->events) : 0;
    }

    public function getEnabledEventCount(): int
    {
        if (! is_array($this->events)) {
            return 0;
        }

        return count(array_filter($this->events, fn ($e) => $e['zoom_enabled'] ?? true));
    }

    public function hasEventsToProcess(): bool
    {
        return $this->enabled
            && $this->getEventCount() > 0
            && ! $this->isCompleted()
            && ! $this->isFailed();
    }

    public function getResolution(): array
    {
        return $this->recording_resolution ?? ['width' => 1920, 'height' => 1080];
    }

    public function getZoomDurationSeconds(): float
    {
        return $this->duration_ms / 1000;
    }
}

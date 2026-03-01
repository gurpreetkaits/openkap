<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VideoEdit extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'video_id',
        'user_id',
        'output_video_id',
        'blur_regions',
        'overlay_configs',
        'text_overlays',
        'trim_start',
        'trim_end',
        'merge_video_id',
        'status',
        'progress',
        'error',
    ];

    protected $casts = [
        'blur_regions' => 'array',
        'overlay_configs' => 'array',
        'text_overlays' => 'array',
        'trim_start' => 'float',
        'trim_end' => 'float',
        'progress' => 'integer',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function outputVideo()
    {
        return $this->belongsTo(Video::class, 'output_video_id');
    }

    public function mergeVideo()
    {
        return $this->belongsTo(Video::class, 'merge_video_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('overlays')
            ->acceptsMimeTypes(['video/webm', 'video/mp4', 'video/quicktime'])
            ->useDisk('public');
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}

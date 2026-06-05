<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Download extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'user_id',
        'token',
        'status',
        'progress',
        'format',
        'file_path',
        'file_size',
        'error_message',
        'expires_at',
        'downloaded_at',
    ];

    protected $casts = [
        'progress' => 'integer',
        'file_size' => 'integer',
        'expires_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Download $download) {
            if (empty($download->token)) {
                $download->token = (string) Str::uuid();
            }
        });
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['queued', 'converting']);
    }
}

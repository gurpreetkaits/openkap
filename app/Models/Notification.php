<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    public const TYPE_VIEWER = 'viewer';

    public const TYPE_COMMENT = 'comment';

    public const TYPE_WARNING = 'warning';

    public const TYPE_SUCCESS = 'success';

    public const TYPE_INFO = 'info';

    public const TYPE_FEEDBACK = 'feedback';

    public const TYPE_DOWNLOAD = 'download';

    public const TYPES = [
        self::TYPE_VIEWER,
        self::TYPE_COMMENT,
        self::TYPE_WARNING,
        self::TYPE_SUCCESS,
        self::TYPE_INFO,
        self::TYPE_FEEDBACK,
        self::TYPE_DOWNLOAD,
    ];

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'notifiable_type',
        'notifiable_id',
        'actor_id',
        'link',
        'read',
        'read_at',
    ];

    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the actor (user who triggered the notification).
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Get the notifiable model (e.g., Video).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if (! $this->read) {
            $this->update([
                'read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update([
            'read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope a query to only include read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }
}

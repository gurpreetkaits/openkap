<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'user_id',
        'parent_id',
        'author_name',
        'content',
        'mentions',
        'timestamp_seconds',
        'edited_at',
    ];

    protected $casts = [
        'timestamp_seconds' => 'integer',
        'mentions' => 'array',
        'edited_at' => 'datetime',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get all mentioned users.
     */
    public function mentionedUsers()
    {
        if (empty($this->mentions)) {
            return collect();
        }

        return User::whereIn('id', $this->mentions)->get();
    }

    /**
     * Check if comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if comment was edited.
     */
    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    /**
     * Check if user can edit this comment.
     */
    public function canEdit(?int $userId): bool
    {
        return $userId && $this->user_id === $userId;
    }

    /**
     * Check if user can delete this comment.
     */
    public function canDelete(?int $userId, ?int $videoOwnerId = null): bool
    {
        if (! $userId) {
            return false;
        }

        // User can delete their own comment or video owner can delete any comment
        return $this->user_id === $userId || $videoOwnerId === $userId;
    }

    /**
     * Get the display name for the comment author.
     */
    public function getAuthorDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->author_name ?? 'Anonymous';
    }
}

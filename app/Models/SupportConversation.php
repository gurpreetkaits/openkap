<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'last_message_at',
        'last_admin_read_at',
        'last_user_read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'last_admin_read_at' => 'datetime',
            'last_user_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    public function unreadCountForAdmin(): int
    {
        return $this->messages()
            ->where('sender_type', 'user')
            ->when(
                $this->last_admin_read_at,
                fn ($query) => $query->where('created_at', '>', $this->last_admin_read_at)
            )
            ->count();
    }

    public function unreadCountForUser(): int
    {
        return $this->messages()
            ->where('sender_type', 'admin')
            ->when(
                $this->last_user_read_at,
                fn ($query) => $query->where('created_at', '>', $this->last_user_read_at)
            )
            ->count();
    }
}

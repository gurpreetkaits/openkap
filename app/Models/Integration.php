<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'status',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'metadata',
        'external_user_id',
        'external_user_name',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(IntegrationAction::class);
    }

    public function isExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function needsRefresh(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        // Refresh if expiring within 5 minutes
        return $this->token_expires_at->subMinutes(5)->isPast();
    }
}

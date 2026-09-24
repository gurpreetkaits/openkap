<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtensionError extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'extension_version',
        'browser',
        'platform',
        'context',
        'code',
        'message',
        'stack',
        'session_id',
        'detail',
        'occurred_at',
    ];

    protected $casts = [
        'detail' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

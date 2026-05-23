<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class VideoView
 *
 * @property int $id
 * @property int $video_id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $country_code
 * @property string|null $country
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $os
 * @property string|null $referrer_source
 * @property string|null $referrer_url
 * @property string|null $session_id
 * @property int $watch_duration
 * @property int $progress_max_seconds
 * @property bool $completed
 * @property \Carbon\Carbon $viewed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class VideoView extends Model
{
    protected $fillable = [
        'video_id',
        'user_id',
        'ip_address',
        'user_agent',
        'country_code',
        'country',
        'device_type',
        'browser',
        'os',
        'referrer_source',
        'referrer_url',
        'session_id',
        'watch_duration',
        'progress_max_seconds',
        'completed',
        'viewed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'viewed_at' => 'datetime',
        'watch_duration' => 'integer',
        'progress_max_seconds' => 'integer',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

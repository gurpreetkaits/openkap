<?php

namespace Database\Factories;

use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DownloadFactory extends Factory
{
    protected $model = Download::class;

    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'user_id' => User::factory(),
            'token' => (string) Str::uuid(),
            'status' => 'queued',
            'progress' => 0,
            'format' => 'mp4',
            'expires_at' => now()->addHours(24),
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready',
            'progress' => 100,
            'file_path' => 'downloads/test.mp4',
            'file_size' => 1024000,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'FFmpeg conversion failed',
        ]);
    }
}

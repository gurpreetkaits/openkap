<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VideoEdit>
 */
class VideoEditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'user_id' => User::factory(),
            'blur_regions' => [],
            'overlay_configs' => [],
            'text_overlays' => [],
            'status' => 'pending',
            'progress' => 0,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'progress' => 50,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error' => 'FFmpeg processing failed',
        ]);
    }

    public function withBlurRegions(int $count = 1): static
    {
        $regions = [];
        for ($i = 0; $i < $count; $i++) {
            $regions[] = [
                'x' => fake()->randomFloat(1, 0, 50),
                'y' => fake()->randomFloat(1, 0, 50),
                'width' => fake()->randomFloat(1, 10, 40),
                'height' => fake()->randomFloat(1, 10, 40),
                'start_time' => null,
                'end_time' => null,
            ];
        }

        return $this->state(fn (array $attributes) => [
            'blur_regions' => $regions,
        ]);
    }

    public function withTextOverlays(int $count = 1): static
    {
        $overlays = [];
        for ($i = 0; $i < $count; $i++) {
            $overlays[] = [
                'text' => fake()->words(3, true),
                'x' => fake()->randomFloat(1, 5, 80),
                'y' => fake()->randomFloat(1, 5, 80),
                'font_size' => fake()->numberBetween(16, 72),
                'font_color' => '#ffffff',
                'background_color' => '#000000',
                'start_time' => null,
                'end_time' => null,
            ];
        }

        return $this->state(fn (array $attributes) => [
            'text_overlays' => $overlays,
        ]);
    }
}

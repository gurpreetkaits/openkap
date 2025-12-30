<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'duration' => fake()->numberBetween(10, 600),
            'is_public' => true,
            'conversion_status' => 'pending',
            'conversion_progress' => 0,
            'original_extension' => 'webm',
            'hls_status' => 'pending',
            'hls_progress' => 0,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'conversion_status' => 'completed',
            'conversion_progress' => 100,
            'converted_at' => now(),
        ]);
    }

    public function withHls(): static
    {
        return $this->state(fn (array $attributes) => [
            'conversion_status' => 'completed',
            'conversion_progress' => 100,
            'converted_at' => now(),
            'hls_status' => 'completed',
            'hls_progress' => 100,
            'hls_path' => 'hls/'.fake()->uuid(),
            'hls_converted_at' => now(),
        ]);
    }
}

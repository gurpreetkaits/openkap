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

    public function withTranscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'transcription_status' => 'completed',
            'transcription' => fake()->paragraphs(3, true),
            'transcription_segments' => [
                ['start' => 0, 'end' => 10, 'text' => fake()->sentence()],
                ['start' => 10, 'end' => 20, 'text' => fake()->sentence()],
                ['start' => 20, 'end' => 30, 'text' => fake()->sentence()],
            ],
            'transcription_progress' => 100,
            'transcription_generated_at' => now(),
        ]);
    }

    public function withSummary(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary_status' => 'completed',
            'summary' => fake()->paragraph(),
            'summary_generated_at' => now(),
        ]);
    }

    public function withTranscriptionAndSummary(): static
    {
        return $this->withTranscription()->withSummary();
    }

    /**
     * Video stored on Bunny (pending upload)
     */
    public function bunnyPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_type' => 'bunny',
            'bunny_status' => 'pending',
            'bunny_video_id' => null,
            'bunny_library_id' => null,
        ]);
    }

    /**
     * Video uploaded to Bunny (processing)
     */
    public function bunnyProcessing(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_type' => 'bunny',
            'bunny_status' => 'processing',
            'bunny_video_id' => fake()->uuid(),
            'bunny_library_id' => 'test-library-id',
        ]);
    }

    /**
     * Video ready on Bunny CDN
     */
    public function bunnyReady(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_type' => 'bunny',
            'bunny_status' => 'ready',
            'bunny_video_id' => fake()->uuid(),
            'bunny_library_id' => 'test-library-id',
            'bunny_resolution' => '1080p',
            'bunny_file_size' => fake()->numberBetween(1000000, 100000000),
        ]);
    }

    /**
     * Video failed on Bunny
     */
    public function bunnyError(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_type' => 'bunny',
            'bunny_status' => 'error',
            'bunny_video_id' => fake()->uuid(),
            'bunny_library_id' => 'test-library-id',
            'bunny_error' => 'Transcoding failed: Invalid video format',
        ]);
    }
}

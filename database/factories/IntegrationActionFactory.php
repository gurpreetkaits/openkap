<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\IntegrationAction;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationActionFactory extends Factory
{
    protected $model = IntegrationAction::class;

    public function definition(): array
    {
        return [
            'integration_id' => Integration::factory(),
            'video_id' => Video::factory(),
            'user_id' => User::factory(),
            'action_type' => 'share_link',
            'status' => 'completed',
            'request_data' => [
                'target_id' => fake()->bothify('C##??????'),
                'target_name' => '#'.fake()->word(),
                'message' => fake()->sentence(),
            ],
            'response_data' => [
                'external_url' => null,
                'external_id' => fake()->numerify('####.######'),
                'metadata' => ['channel' => fake()->bothify('C##??????')],
            ],
            'error' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'response_data' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'response_data' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'response_data' => null,
            'error' => 'channel_not_found',
        ]);
    }
}

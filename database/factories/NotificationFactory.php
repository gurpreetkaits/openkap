<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(Notification::TYPES),
            'message' => fake()->sentence(),
            'read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => true,
            'read_at' => now(),
        ]);
    }

    public function comment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Notification::TYPE_COMMENT,
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Notification::TYPE_VIEWER,
        ]);
    }
}

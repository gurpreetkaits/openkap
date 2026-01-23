<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * User with active Pro subscription
     */
    public function withProSubscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'active',
            'subscription_started_at' => now()->subMonth(),
            'subscription_expires_at' => now()->addMonth(),
        ]);
    }

    /**
     * User with canceled subscription (grace period)
     */
    public function withCanceledSubscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'canceled',
            'subscription_started_at' => now()->subMonths(2),
            'subscription_expires_at' => now()->addDays(15),
            'subscription_canceled_at' => now()->subDays(15),
        ]);
    }

    /**
     * Free user (default, but explicit)
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'free',
            'subscription_started_at' => null,
            'subscription_expires_at' => null,
        ]);
    }
}

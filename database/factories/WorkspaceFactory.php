<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->randomNumber(4),
            'description' => fake()->sentence(),
            'logo_url' => null,
            'owner_id' => User::factory(),
            'subscription_status' => 'free',
            'subscription_plan' => null,
            'max_members' => 5,
            'max_storage_gb' => 100,
            'max_recording_minutes' => 60,
            'storage_used_bytes' => 0,
        ];
    }

    /**
     * Workspace with active Team subscription
     */
    public function withTeamSubscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'active',
            'subscription_plan' => 'team',
            'subscription_started_at' => now()->subMonth(),
            'subscription_expires_at' => now()->addMonth(),
            'max_members' => 5,
            'max_storage_gb' => 100,
            'max_recording_minutes' => 60,
        ]);
    }

    /**
     * Workspace with active Team Plus subscription
     */
    public function withTeamPlusSubscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'active',
            'subscription_plan' => 'team_plus',
            'subscription_started_at' => now()->subMonth(),
            'subscription_expires_at' => now()->addMonth(),
            'max_members' => 15,
            'max_storage_gb' => 500,
            'max_recording_minutes' => 120,
        ]);
    }

    /**
     * Workspace with canceled subscription (grace period)
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'canceled',
            'subscription_plan' => 'team',
            'subscription_started_at' => now()->subMonths(2),
            'subscription_expires_at' => now()->addDays(15),
            'subscription_canceled_at' => now()->subDays(15),
        ]);
    }

    /**
     * Workspace with expired subscription
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'canceled',
            'subscription_plan' => 'team',
            'subscription_started_at' => now()->subMonths(3),
            'subscription_expires_at' => now()->subDays(5),
            'subscription_canceled_at' => now()->subMonth(),
        ]);
    }

    /**
     * Workspace at storage limit
     */
    public function storageFull(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_storage_gb' => 100,
            'storage_used_bytes' => 100 * 1024 * 1024 * 1024, // 100 GB
        ]);
    }

    /**
     * Workspace at member limit
     */
    public function membersFull(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_members' => 5,
        ]);
    }

    /**
     * Configure workspace with owner as member
     */
    public function withOwnerAsMember(): static
    {
        return $this->afterCreating(function (Workspace $workspace) {
            // Add owner as member with 'owner' role
            $workspace->members()->attach($workspace->owner_id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);
        });
    }
}

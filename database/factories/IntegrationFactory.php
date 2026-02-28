<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'slack',
            'status' => 'active',
            'access_token' => fake()->sha256(),
            'refresh_token' => null,
            'token_expires_at' => null,
            'metadata' => [
                'team_id' => 'T'.fake()->bothify('##??????'),
                'team_name' => fake()->company(),
                'bot_user_id' => 'U'.fake()->bothify('##??????'),
            ],
            'external_user_id' => 'U'.fake()->bothify('##??????'),
            'external_user_name' => fake()->company(),
        ];
    }

    public function googleDrive(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'google_drive',
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
            'metadata' => null,
            'external_user_id' => (string) fake()->numberBetween(100000000000, 999999999999),
            'external_user_name' => fake()->safeEmail(),
        ]);
    }

    public function jira(): static
    {
        $cloudId = fake()->uuid();

        return $this->state(fn (array $attributes) => [
            'provider' => 'jira',
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
            'metadata' => [
                'cloud_id' => $cloudId,
                'site_name' => fake()->domainWord(),
                'site_url' => 'https://'.fake()->domainWord().'.atlassian.net',
            ],
            'external_user_id' => $cloudId,
            'external_user_name' => fake()->domainWord(),
        ]);
    }

    public function trello(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'trello',
            'refresh_token' => null,
            'token_expires_at' => null,
            'metadata' => [
                'username' => fake()->userName(),
            ],
            'external_user_id' => fake()->bothify('##??##??##??##??##??##??'),
            'external_user_name' => fake()->name(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'token_expires_at' => now()->subHour(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
        ]);
    }
}

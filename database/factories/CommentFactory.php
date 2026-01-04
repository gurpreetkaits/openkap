<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'timestamp_seconds' => fake()->numberBetween(0, 300),
        ];
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'author_name' => fake()->name(),
        ]);
    }

    public function asReply(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'video_id' => $parent->video_id,
        ]);
    }

    public function withMentions(array $userIds): static
    {
        return $this->state(fn (array $attributes) => [
            'mentions' => $userIds,
        ]);
    }
}

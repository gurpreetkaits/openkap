<?php

namespace Database\Seeders;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $user = User::factory()->create();
        }

        $conversations = ChatConversation::factory()
            ->count(3)
            ->for($user)
            ->create();

        foreach ($conversations as $conversation) {
            ChatMessage::factory()
                ->count(4)
                ->for($conversation, 'conversation')
                ->sequence(
                    ['role' => 'user'],
                    ['role' => 'assistant'],
                    ['role' => 'user'],
                    ['role' => 'assistant'],
                )
                ->create();
        }
    }
}

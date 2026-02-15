<?php

namespace App\Repositories;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatConversationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new ChatConversation);
    }

    public function getUserConversations(User $user, int $limit = 20): Collection
    {
        return ChatConversation::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function findByUserAndId(int $userId, int $conversationId): ?ChatConversation
    {
        return ChatConversation::where('user_id', $userId)
            ->where('id', $conversationId)
            ->first();
    }

    public function createConversation(array $data): ChatConversation
    {
        return ChatConversation::create($data);
    }

    public function deleteConversation(ChatConversation $conversation): bool
    {
        return $conversation->delete();
    }

    public function addMessage(ChatConversation $conversation, array $data): \App\Models\ChatMessage
    {
        $message = $conversation->messages()->create($data);

        $conversation->increment('total_tokens_used', $data['tokens_used'] ?? 0);

        return $message;
    }

    public function getMessages(ChatConversation $conversation): Collection
    {
        return $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();
    }
}

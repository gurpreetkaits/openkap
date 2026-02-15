<?php

namespace App\Managers;

use App\Data\ChatSendData;
use App\Models\User;
use App\Repositories\ChatConversationRepository;
use App\Services\ChatService;

class ChatManager
{
    public function __construct(
        protected ChatConversationRepository $conversations,
        protected ChatService $chatService
    ) {}

    public function getConversations(User $user): array
    {
        $conversations = $this->conversations->getUserConversations($user);

        return $conversations->map(fn ($conv) => [
            'id' => $conv->id,
            'title' => $conv->title,
            'total_tokens_used' => $conv->total_tokens_used,
            'created_at' => $conv->created_at->toISOString(),
            'updated_at' => $conv->updated_at->toISOString(),
        ])->toArray();
    }

    public function getMessages(int $conversationId, User $user): ?array
    {
        $conversation = $this->conversations->findByUserAndId($user->id, $conversationId);

        if (! $conversation) {
            return null;
        }

        $messages = $this->conversations->getMessages($conversation);

        return [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'messages' => $messages->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'tokens_used' => $msg->tokens_used,
                'created_at' => $msg->created_at->toISOString(),
            ])->toArray(),
        ];
    }

    public function sendMessage(ChatSendData $data, User $user): array
    {
        // Get or create conversation
        if ($data->conversationId) {
            $conversation = $this->conversations->findByUserAndId($user->id, $data->conversationId);
            if (! $conversation) {
                throw new \RuntimeException('Conversation not found');
            }
        } else {
            $conversation = $this->conversations->createConversation([
                'user_id' => $user->id,
                'title' => mb_substr($data->message, 0, 50),
            ]);
        }

        // Save user message
        $this->conversations->addMessage($conversation, [
            'role' => 'user',
            'content' => $data->message,
            'tokens_used' => 0,
        ]);

        // Build message history for OpenAI (last 20 messages + system prompt)
        $history = $this->conversations->getMessages($conversation);
        $messages = [
            ['role' => 'system', 'content' => $this->chatService->getSystemPrompt()],
        ];

        foreach ($history->take(-20) as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        // Call OpenAI
        $response = $this->chatService
            ->forUser($user)
            ->sendMessage($messages);

        // Save assistant response
        $assistantMessage = $this->conversations->addMessage($conversation, [
            'role' => 'assistant',
            'content' => $response->content,
            'tokens_used' => $response->totalTokens,
        ]);

        return [
            'conversation_id' => $conversation->id,
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $response->content,
                'tokens_used' => $response->totalTokens,
                'created_at' => $assistantMessage->created_at->toISOString(),
            ],
        ];
    }

    public function deleteConversation(int $conversationId, User $user): bool
    {
        $conversation = $this->conversations->findByUserAndId($user->id, $conversationId);

        if (! $conversation) {
            return false;
        }

        return $this->conversations->deleteConversation($conversation);
    }

    public function createConversation(User $user): array
    {
        $conversation = $this->conversations->createConversation([
            'user_id' => $user->id,
            'title' => 'New Chat',
        ]);

        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'total_tokens_used' => $conversation->total_tokens_used,
            'created_at' => $conversation->created_at->toISOString(),
            'updated_at' => $conversation->updated_at->toISOString(),
        ];
    }
}

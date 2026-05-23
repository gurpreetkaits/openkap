<?php

namespace App\Managers;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Repositories\SupportConversationRepository;
use App\Repositories\SupportMessageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SupportChatManager
{
    public function __construct(
        protected SupportConversationRepository $conversations,
        protected SupportMessageRepository $messages,
    ) {}

    public function getOrCreateConversationForUser(User $user): SupportConversation
    {
        return $this->conversations->firstOrCreateForUser($user);
    }

    public function getMessagesForConversation(SupportConversation $conversation): Collection
    {
        return $this->messages->getForConversation($conversation);
    }

    public function sendMessageAsUser(User $user, string $body): SupportMessage
    {
        $conversation = $this->conversations->firstOrCreateForUser($user);

        $message = $this->messages->createInConversation($conversation, [
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'body' => $body,
        ]);

        $this->conversations->touchLastMessageAt($conversation);
        $this->conversations->markReadByUser($conversation);

        return $message->load('sender:id,name,email,avatar_url');
    }

    public function sendMessageAsAdmin(User $admin, SupportConversation $conversation, string $body): SupportMessage
    {
        $message = $this->messages->createInConversation($conversation, [
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'body' => $body,
        ]);

        $this->conversations->touchLastMessageAt($conversation);
        $this->conversations->markReadByAdmin($conversation);

        return $message->load('sender:id,name,email,avatar_url');
    }

    public function markConversationReadByUser(User $user): SupportConversation
    {
        $conversation = $this->conversations->firstOrCreateForUser($user);

        return $this->conversations->markReadByUser($conversation);
    }

    public function markConversationReadByAdmin(SupportConversation $conversation): SupportConversation
    {
        return $this->conversations->markReadByAdmin($conversation);
    }

    public function listConversationsForAdmin(int $perPage = 25, ?string $search = null): LengthAwarePaginator
    {
        return $this->conversations->paginateForAdmin($perPage, $search);
    }
}

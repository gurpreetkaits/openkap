<?php

namespace App\Repositories;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Database\Eloquent\Collection;

class SupportMessageRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SupportMessage);
    }

    /**
     * @param  array{sender_id:int, sender_type:string, body:string}  $data
     */
    public function createInConversation(SupportConversation $conversation, array $data): SupportMessage
    {
        return SupportMessage::create([
            'support_conversation_id' => $conversation->id,
            'sender_id' => $data['sender_id'],
            'sender_type' => $data['sender_type'],
            'body' => $data['body'],
        ]);
    }

    public function getForConversation(SupportConversation $conversation): Collection
    {
        return SupportMessage::query()
            ->with('sender:id,name,email,avatar_url')
            ->where('support_conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->get();
    }
}

<?php

namespace App\Http\Resources;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SupportConversation
 */
class SupportConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latest = $this->whenLoaded('latestMessage');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_admin_read_at' => $this->last_admin_read_at?->toIso8601String(),
            'last_user_read_at' => $this->last_user_read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar_url' => $this->user->avatar_url,
                ];
            }),
            'latest_message' => $latest instanceof SupportMessage
                ? [
                    'id' => $latest->id,
                    'body' => $latest->body,
                    'sender_type' => $latest->sender_type,
                    'created_at' => $latest->created_at?->toIso8601String(),
                ]
                : null,
            'unread_count_admin' => $this->unreadCountForAdmin(),
            'unread_count_user' => $this->unreadCountForUser(),
        ];
    }
}

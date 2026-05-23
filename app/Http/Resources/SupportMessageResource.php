<?php

namespace App\Http\Resources;

use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SupportMessage
 */
class SupportMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'support_conversation_id' => $this->support_conversation_id,
            'sender_id' => $this->sender_id,
            'sender_type' => $this->sender_type,
            'sender_name' => $this->sender?->name,
            'sender_email' => $this->sender?->email,
            'sender_avatar_url' => $this->sender?->avatar_url,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

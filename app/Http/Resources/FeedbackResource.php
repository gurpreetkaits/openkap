<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'reply' => $this->reply,
            'replied_at' => $this->replied_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

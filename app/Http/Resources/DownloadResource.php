<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownloadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'status' => $this->status,
            'progress' => $this->progress,
            'format' => $this->format,
            'file_size' => $this->file_size,
            'error_message' => $this->error_message,
            'expires_at' => $this->expires_at?->toISOString(),
            'downloaded_at' => $this->downloaded_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

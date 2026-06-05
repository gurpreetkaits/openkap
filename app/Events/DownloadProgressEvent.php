<?php

namespace App\Events;

use App\Models\Download;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DownloadProgressEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Download $download,
    ) {}

    public function broadcastOn(): array
    {
        return ['downloads'];
    }

    public function broadcastAs(): string
    {
        return 'download.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'download_id' => $this->download->id,
            'token' => $this->download->token,
            'status' => $this->download->status,
            'progress' => $this->download->progress,
            'error_message' => $this->download->error_message,
        ];
    }
}

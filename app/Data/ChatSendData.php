<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ChatSendData extends Data
{
    public function __construct(
        public string $message,
        public ?int $conversationId = null,
    ) {}
}

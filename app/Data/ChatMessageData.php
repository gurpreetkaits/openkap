<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ChatMessageData extends Data
{
    public function __construct(
        public string $role,
        public string $content,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
    ) {}
}

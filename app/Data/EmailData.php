<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EmailData extends Data
{
    public function __construct(
        public string $from,
        public string|array $to,
        public string $subject,
        public ?string $html = null,
        public ?string $text = null,
        public string|array|null $cc = null,
        public string|array|null $bcc = null,
        public string|array|null $replyTo = null,
        public ?array $headers = null,
        public ?array $attachments = null,
        public ?array $tags = null,
        public ?string $scheduledAt = null,
    ) {}

    /**
     * Convert to array for Resend API payload
     */
    public function toApiPayload(): array
    {
        $payload = [
            'from' => $this->from,
            'to' => is_array($this->to) ? $this->to : [$this->to],
            'subject' => $this->subject,
        ];

        if ($this->html !== null) {
            $payload['html'] = $this->html;
        }

        if ($this->text !== null) {
            $payload['text'] = $this->text;
        }

        if ($this->cc !== null) {
            $payload['cc'] = is_array($this->cc) ? $this->cc : [$this->cc];
        }

        if ($this->bcc !== null) {
            $payload['bcc'] = is_array($this->bcc) ? $this->bcc : [$this->bcc];
        }

        if ($this->replyTo !== null) {
            $payload['reply_to'] = is_array($this->replyTo) ? $this->replyTo : [$this->replyTo];
        }

        if ($this->headers !== null) {
            $payload['headers'] = $this->headers;
        }

        if ($this->attachments !== null) {
            $payload['attachments'] = $this->attachments;
        }

        if ($this->tags !== null) {
            $payload['tags'] = $this->tags;
        }

        if ($this->scheduledAt !== null) {
            $payload['scheduled_at'] = $this->scheduledAt;
        }

        return $payload;
    }
}

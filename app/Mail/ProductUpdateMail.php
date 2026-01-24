<?php

namespace App\Mail;

use App\Data\ProductUpdateEmailData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProductUpdateEmailData $data,
        public string $emailSubject = 'Product Update'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.product-update',
            with: [
                'badgeIcon' => $this->data->badgeIcon,
                'badgeText' => $this->data->badgeText,
                'headline' => $this->data->headline,
                'subheadline' => $this->data->subheadline,
                'description' => $this->data->description,
                'features' => $this->data->features,
                'ctaText' => $this->data->ctaText,
                'ctaUrl' => $this->data->ctaUrl,
                'ctaIcon' => $this->data->ctaIcon,
                'showVisualCard' => $this->data->showVisualCard,
                'visualCardItems' => $this->data->visualCardItems,
                'previewText' => $this->data->previewText,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

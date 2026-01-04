<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Feedback $feedback,
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Feedback: '.$this->feedback->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feedback-received',
            with: [
                'feedback' => $this->feedback,
                'user' => $this->user,
            ],
        );
    }
}

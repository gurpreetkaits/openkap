<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BulkEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $view,
        public string $emailSubject,
        public array $data = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->emailSubject)
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader(
                    'List-Unsubscribe',
                    '<mailto:unsubscribe@screensense.in>'
                );
            })
            ->view($this->view, array_merge($this->data, [
                'user' => $notifiable,
            ]));
    }
}

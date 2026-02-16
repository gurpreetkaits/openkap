<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class UnosendTransport extends AbstractTransport
{
    protected string $apiKey;

    protected string $apiUrl = 'https://www.unosend.co/api/v1/emails';

    public function __construct(
        string $apiKey,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($dispatcher, $logger);
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $payload = $this->buildPayload($email, $envelope);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, $payload);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Unosend API error: ' . $response->body()
            );
        }
    }

    protected function buildPayload(Email $email, Envelope $envelope): array
    {
        $from = $envelope->getSender();

        $payload = [
            'from' => $from->getAddress(),
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject(),
        ];

        if ($email->getTextBody()) {
            $payload['text'] = $email->getTextBody();
        }

        if ($email->getHtmlBody()) {
            $payload['html'] = $email->getHtmlBody();
        }

        if ($cc = $email->getCc()) {
            $payload['cc'] = $this->formatAddresses($cc);
        }

        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = $this->formatAddresses($bcc);
        }

        if ($replyTo = $email->getReplyTo()) {
            $addresses = $this->formatAddresses($replyTo);
            $payload['reply_to'] = $addresses[0] ?? null;
        }

        return $payload;
    }

    /**
     * @param Address[] $addresses
     * @return string[]
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_map(fn(Address $address) => $address->getAddress(), $addresses);
    }

    public function __toString(): string
    {
        return 'unosend';
    }
}

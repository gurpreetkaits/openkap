<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Unosend\Unosend;

class UnosendTransport extends AbstractTransport
{
    public function __construct(
        protected Unosend $client,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toNotificationEmail($message->getOriginalMessage());

        $envelope = $message->getEnvelope();

        $params = [
            'from' => $this->formatAddress($envelope->getSender()),
            'to' => $this->formatAddresses($this->getRecipients($email, $envelope)),
            'subject' => $email->getSubject() ?? '',
        ];

        if ($html = $email->getHtmlBody()) {
            $params['html'] = $html;
        }

        if ($text = $email->getTextBody()) {
            $params['text'] = $text;
        }

        if ($cc = $email->getCc()) {
            $params['cc'] = $this->formatAddresses($cc);
        }

        if ($bcc = $email->getBcc()) {
            $params['bcc'] = $this->formatAddresses($bcc);
        }

        if ($replyTo = $email->getReplyTo()) {
            $params['replyTo'] = $this->formatAddress($replyTo[0]);
        }

        $this->client->emails->send($params);
    }

    public function __toString(): string
    {
        return 'unosend';
    }

    protected function formatAddress(Address $address): string
    {
        if ($address->getName()) {
            return $address->getName().' <'.$address->getAddress().'>';
        }

        return $address->getAddress();
    }

    /**
     * @param  Address[]  $addresses
     * @return string[]
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_map(fn (Address $address) => $this->formatAddress($address), $addresses);
    }

    /**
     * @return Address[]
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        $recipients = $email->getTo();

        return ! empty($recipients) ? $recipients : $envelope->getRecipients();
    }
}

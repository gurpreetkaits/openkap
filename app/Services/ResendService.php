<?php

namespace App\Services;

use App\Data\EmailData;
use App\Data\EmailResponseData;
use App\Http\Integrations\Resend\Requests\SendEmailRequest;
use App\Http\Integrations\Resend\ResendConnector;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResendService
{
    protected ResendConnector $connector;

    protected ?User $user = null;

    protected ?string $correlationId = null;

    public function __construct(?ResendConnector $connector = null)
    {
        $this->connector = $connector ?? new ResendConnector;
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Set the user for logging
     */
    public function forUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set correlation ID for tracing related requests
     */
    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Get the connector with logging configured
     */
    protected function getConnector(): ResendConnector
    {
        return $this->connector
            ->forUser($this->user)
            ->withCorrelationId($this->correlationId);
    }

    /**
     * Check if Resend is configured
     */
    public function isConfigured(): bool
    {
        return $this->connector->isConfigured();
    }

    /**
     * Send an email via Resend
     *
     * @throws \Exception
     */
    public function sendEmail(EmailData $emailData): EmailResponseData
    {
        if (! $this->isConfigured()) {
            Log::error('Resend: Not configured - missing RESEND_KEY');
            throw new \Exception('Resend is not configured. Check RESEND_KEY environment variable.');
        }

        $connector = $this->getConnector()
            ->withLogContext([
                'operation' => 'send_email',
                'to' => is_array($emailData->to) ? implode(', ', $emailData->to) : $emailData->to,
                'subject' => $emailData->subject,
            ]);

        $request = new SendEmailRequest($emailData);

        $response = $connector->send($request);

        if (! $response->successful()) {
            Log::error('Resend: Failed to send email', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $emailData->to,
                'subject' => $emailData->subject,
            ]);
            throw new \Exception('Failed to send email via Resend (HTTP '.$response->status().'): '.$response->body());
        }

        $data = $response->json();

        return new EmailResponseData(
            id: $data['id']
        );
    }
}

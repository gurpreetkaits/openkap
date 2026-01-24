<?php

namespace App\Http\Integrations\Resend\Requests;

use App\Data\EmailData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class SendEmailRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected EmailData $emailData
    ) {}

    public function resolveEndpoint(): string
    {
        return '/emails';
    }

    protected function defaultBody(): array
    {
        return $this->emailData->toApiPayload();
    }
}

<?php

namespace App\Http\Integrations\Bunny\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateVideoRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $libraryId,
        protected string $videoId,
        protected array $data
    ) {}

    public function resolveEndpoint(): string
    {
        return "/library/{$this->libraryId}/videos/{$this->videoId}";
    }

    protected function defaultBody(): array
    {
        return $this->data;
    }
}

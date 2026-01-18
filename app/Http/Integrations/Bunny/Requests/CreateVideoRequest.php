<?php

namespace App\Http\Integrations\Bunny\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateVideoRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $libraryId,
        protected string $title,
        protected ?string $collectionId = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/library/{$this->libraryId}/videos";
    }

    protected function defaultBody(): array
    {
        $body = ['title' => $this->title];

        if ($this->collectionId) {
            $body['collectionId'] = $this->collectionId;
        }

        return $body;
    }
}

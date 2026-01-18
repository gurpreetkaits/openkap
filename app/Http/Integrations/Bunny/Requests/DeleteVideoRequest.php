<?php

namespace App\Http\Integrations\Bunny\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteVideoRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected string $libraryId,
        protected string $videoId
    ) {}

    public function resolveEndpoint(): string
    {
        return "/library/{$this->libraryId}/videos/{$this->videoId}";
    }
}

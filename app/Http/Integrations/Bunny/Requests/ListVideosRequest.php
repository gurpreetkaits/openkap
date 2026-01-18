<?php

namespace App\Http\Integrations\Bunny\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListVideosRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $libraryId,
        protected int $page = 1,
        protected int $perPage = 100,
        protected ?string $search = null,
        protected ?string $collectionId = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/library/{$this->libraryId}/videos";
    }

    protected function defaultQuery(): array
    {
        $query = [
            'page' => $this->page,
            'itemsPerPage' => min($this->perPage, 100),
        ];

        if ($this->search) {
            $query['search'] = $this->search;
        }

        if ($this->collectionId) {
            $query['collection'] = $this->collectionId;
        }

        return $query;
    }
}

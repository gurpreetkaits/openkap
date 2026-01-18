<?php

namespace App\Http\Integrations\OpenAi\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasMultipartBody;

class TranscribeAudioRequest extends Request implements HasBody
{
    use HasMultipartBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $audioPath,
        protected string $model = 'whisper-1',
        protected string $language = 'en',
        protected string $responseFormat = 'verbose_json'
    ) {}

    public function resolveEndpoint(): string
    {
        return '/audio/transcriptions';
    }

    protected function defaultBody(): array
    {
        return [
            new MultipartValue(
                name: 'file',
                value: fopen($this->audioPath, 'r'),
                filename: basename($this->audioPath)
            ),
            new MultipartValue(name: 'model', value: $this->model),
            new MultipartValue(name: 'language', value: $this->language),
            new MultipartValue(name: 'response_format', value: $this->responseFormat),
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 600, // 10 minutes for long videos
        ];
    }
}

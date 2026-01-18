<?php

namespace App\Http\Integrations\OpenAi\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ChatCompletionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected array $messages,
        protected string $model = 'gpt-4o-mini',
        protected int $maxTokens = 1000,
        protected float $temperature = 0.5
    ) {}

    public function resolveEndpoint(): string
    {
        return '/chat/completions';
    }

    protected function defaultBody(): array
    {
        return [
            'model' => $this->model,
            'messages' => $this->messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 120,
        ];
    }

    /**
     * Create a request for generating a summary
     */
    public static function forSummary(string $transcription, string $model = 'gpt-4o-mini'): static
    {
        $systemPrompt = <<<'PROMPT'
You are an expert at summarizing video content. Given a video transcription, create a clear and concise summary that captures the key points, main topics discussed, and any important details.

Guidelines:
- Keep the summary between 100-300 words
- Use bullet points for key takeaways if appropriate
- Highlight any action items or important conclusions
- Maintain a professional tone
- If the transcription is unclear or contains errors, do your best to infer meaning
PROMPT;

        $userPrompt = <<<PROMPT
Please summarize the following video transcription:

---
{$transcription}
---

Provide a clear, concise summary of the main points.
PROMPT;

        return new static(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            model: $model,
            maxTokens: 1000,
            temperature: 0.5
        );
    }

    /**
     * Create a request for generating a title
     */
    public static function forTitle(string $transcription, string $model = 'gpt-4o-mini'): static
    {
        $systemPrompt = <<<'PROMPT'
You are an expert at creating concise, descriptive titles for videos. Given a video transcription, create a short, engaging title that captures the main topic or purpose of the video.

Guidelines:
- Keep the title between 5-12 words
- Make it descriptive and specific
- Avoid generic titles like "Video Recording" or "Screen Recording"
- Use sentence case (capitalize first word and proper nouns only)
- Do not use quotes or punctuation at the end
- Focus on the main topic or action in the video
PROMPT;

        $userPrompt = <<<PROMPT
Based on this video transcription, generate a concise title:

---
{$transcription}
---

Respond with ONLY the title, nothing else.
PROMPT;

        return new static(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            model: $model,
            maxTokens: 50,
            temperature: 0.7
        );
    }
}

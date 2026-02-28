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
You are an expert at summarizing video content. Given a video transcription, create a short, scannable summary in markdown format.

Guidelines:
- Start with a 1-2 sentence overview paragraph
- Follow with 3-5 bullet points of key takeaways using markdown list syntax (- )
- Use **bold** for important terms or action items
- Total length: 50-120 words maximum — be concise
- Do not use headings (no # or ##)
- If the transcription is unclear, summarize what you can infer
PROMPT;

        $userPrompt = <<<PROMPT
Summarize this video transcription in short markdown format:

---
{$transcription}
---
PROMPT;

        return new static(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            model: $model,
            maxTokens: 300,
            temperature: 0.5
        );
    }

    /**
     * Create a request for detecting bugs from a transcript
     */
    public static function forBugDetection(string $transcription, string $model = 'gpt-4o-mini'): static
    {
        $systemPrompt = <<<'PROMPT'
You are an expert QA analyst. Given a video transcription of a screen recording, analyze it for any bugs, issues, or problems that the user mentions, encounters, or demonstrates.

Guidelines:
- Only extract bugs/issues that are clearly mentioned or demonstrated in the transcript
- Do NOT fabricate or infer bugs that are not present in the transcript
- For each bug, extract as much detail as possible from the transcript
- If no bugs are found, return an empty bugs array
- Severity levels: critical, high, medium, low
- If the user mentions a timestamp or you can infer when in the video a bug was discussed, include mentioned_at_seconds as a number
- steps_to_reproduce should be an array of strings, each being one step

Return your response as valid JSON with this exact structure:
{"bugs": [{"title": "Brief bug title", "description": "Detailed description of the bug", "severity": "high", "steps_to_reproduce": ["Step 1", "Step 2"], "mentioned_at_seconds": null}]}

If no bugs are found:
{"bugs": []}
PROMPT;

        $userPrompt = <<<PROMPT
Analyze the following video transcription for any bugs, issues, or problems mentioned or demonstrated:

---
{$transcription}
---

Extract all bugs as JSON. Only include bugs that are clearly present in the transcript.
PROMPT;

        return new static(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            model: $model,
            maxTokens: 2000,
            temperature: 0.3
        );
    }

    /**
     * Create a request for generating a title
     */
    public static function forTitle(string $transcription, string $model = 'gpt-4o-mini'): static
    {
        $systemPrompt = <<<'PROMPT'
You are an expert at creating concise, descriptive titles for screen recordings and video content. Given a video transcription, create a clear, meaningful title that accurately describes what the video is about.

Guidelines:
- Keep the title between 4-10 words
- The title must be specific and descriptive — a reader should understand the video's topic from the title alone
- Capture the main subject, action, or workflow shown in the video (e.g. "Setting up authentication in the Next.js dashboard", "Debugging the payment webhook integration")
- Use sentence case (capitalize first word and proper nouns only)
- Do not use quotes or punctuation at the end
- Do not use filler words like "video about" or "recording of"

CRITICAL: If the transcription is too short (just a few words), mostly silence/filler words (um, uh, like, okay, so), unintelligible, or does not contain enough meaningful content to determine what the video is about — respond with exactly: SKIP
Do NOT guess or make up a vague title. Only generate a title when you can confidently describe the video's content.
PROMPT;

        $userPrompt = <<<PROMPT
Based on this video transcription, generate a concise, descriptive title:

---
{$transcription}
---

Respond with ONLY the title, or SKIP if you cannot determine a meaningful title.
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

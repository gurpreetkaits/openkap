<?php

namespace App\Services;

use App\Data\ChatMessageData;
use App\Http\Integrations\OpenAi\OpenAiConnector;
use App\Http\Integrations\OpenAi\Requests\ChatCompletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatService
{
    protected ?User $user = null;

    protected ?string $correlationId = null;

    protected ?OpenAiConnector $connector = null;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function forUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    protected function getConnector(): OpenAiConnector
    {
        if ($this->connector === null) {
            $this->connector = new OpenAiConnector;
        }

        $this->connector
            ->forUser($this->user)
            ->withCorrelationId($this->correlationId);

        return $this->connector;
    }

    public function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are ScreenSense Assistant, a helpful AI assistant for the ScreenSense screen recording platform. You help users with:

- Recording and managing screen recordings
- Understanding features like video sharing, playlists, workspaces, and collaboration
- Troubleshooting common issues with recording, playback, or uploads
- Tips for creating better screen recordings
- Understanding subscription plans and features
- Using workspace features for team collaboration

Guidelines:
- Be concise and helpful
- If you don't know something specific about ScreenSense, say so honestly
- Focus on being practical and actionable
- Use markdown formatting for clarity when helpful
- Keep responses under 300 words unless the user asks for more detail
PROMPT;
    }

    public function sendMessage(array $messages): ChatMessageData
    {
        $model = config('services.openai.chat_model', 'gpt-4o-mini');

        $connector = $this->getConnector()
            ->withLogContext([
                'operation' => 'chat',
                'model' => $model,
                'user_id' => $this->user?->id,
            ]);

        if (! $connector->isConfigured()) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        Log::info('Sending chat message', [
            'user_id' => $this->user?->id,
            'message_count' => count($messages),
            'model' => $model,
        ]);

        $request = new ChatCompletionRequest(
            messages: $messages,
            model: $model,
            maxTokens: 1000,
            temperature: 0.7
        );

        $response = $connector->send($request);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::error('Chat completion failed', [
                'user_id' => $this->user?->id,
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new \RuntimeException('Chat request failed: '.$error);
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        Log::info('Chat response received', [
            'user_id' => $this->user?->id,
            'response_length' => strlen($content),
            'tokens_used' => $usage['total_tokens'] ?? 0,
        ]);

        return new ChatMessageData(
            role: 'assistant',
            content: $content,
            promptTokens: $usage['prompt_tokens'] ?? 0,
            completionTokens: $usage['completion_tokens'] ?? 0,
            totalTokens: $usage['total_tokens'] ?? 0,
        );
    }
}

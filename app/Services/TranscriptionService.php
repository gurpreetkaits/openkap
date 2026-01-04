<?php

namespace App\Services;

use App\Data\SummaryData;
use App\Data\TranscriptionData;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TranscriptionService
{
    protected ApiLogger $logger;

    protected ?User $user = null;

    protected ?string $correlationId = null;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function forUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Extract audio from video file for transcription.
     */
    public function extractAudio(Video $video): string
    {
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            throw new \RuntimeException('Video file not found');
        }

        $videoPath = $media->getPath();
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $audioPath = $tempDir.'/audio_'.$video->id.'_'.time().'.mp3';
        $ffmpegPath = config('media-library.ffmpeg_path');

        // Extract audio with optimized settings for speech recognition
        $command = sprintf(
            '%s -y -i %s -vn -acodec libmp3lame -ar 16000 -ac 1 -b:a 64k %s 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($audioPath)
        );

        Log::info('Extracting audio for transcription', [
            'video_id' => $video->id,
            'command' => $command,
        ]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || ! file_exists($audioPath)) {
            throw new \RuntimeException('Failed to extract audio: '.implode("\n", $output));
        }

        return $audioPath;
    }

    /**
     * Transcribe audio using OpenAI Whisper API.
     */
    public function transcribe(string $audioPath, Video $video): TranscriptionData
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.whisper_model', 'whisper-1');

        if (! $apiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $fileSize = filesize($audioPath);

        Log::info('Starting transcription', [
            'video_id' => $video->id,
            'audio_file_size' => $fileSize,
            'model' => $model,
        ]);

        $logger = ApiLogger::make()
            ->forService('openai')
            ->forUser($this->user)
            ->withCorrelationId($this->correlationId)
            ->withContext([
                'video_id' => $video->id,
                'operation' => 'transcription',
                'model' => $model,
            ]);

        $response = $logger->http()
            ->timeout(600) // 10 minutes for long videos
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])
            ->attach('file', file_get_contents($audioPath), basename($audioPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => $model,
                'response_format' => 'verbose_json',
                'language' => 'en',
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::error('Transcription failed', [
                'video_id' => $video->id,
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new \RuntimeException('Transcription failed: '.$error);
        }

        $data = $response->json();

        Log::info('Transcription completed', [
            'video_id' => $video->id,
            'text_length' => strlen($data['text'] ?? ''),
            'language' => $data['language'] ?? 'unknown',
            'duration' => $data['duration'] ?? 0,
        ]);

        return new TranscriptionData(
            text: $data['text'] ?? '',
            language: $data['language'] ?? 'en',
            duration: $data['duration'] ?? 0,
            segments: $data['segments'] ?? null,
        );
    }

    /**
     * Generate summary using OpenAI Chat API.
     */
    public function generateSummary(string $transcription, Video $video): SummaryData
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.chat_model', 'gpt-4o-mini');

        if (! $apiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        Log::info('Generating summary', [
            'video_id' => $video->id,
            'transcription_length' => strlen($transcription),
            'model' => $model,
        ]);

        $logger = ApiLogger::make()
            ->forService('openai')
            ->forUser($this->user)
            ->withCorrelationId($this->correlationId)
            ->withContext([
                'video_id' => $video->id,
                'operation' => 'summary',
                'model' => $model,
            ]);

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

        $response = $logger->http()
            ->timeout(120)
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.5,
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::error('Summary generation failed', [
                'video_id' => $video->id,
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new \RuntimeException('Summary generation failed: '.$error);
        }

        $data = $response->json();
        $summary = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        Log::info('Summary generated', [
            'video_id' => $video->id,
            'summary_length' => strlen($summary),
            'tokens_used' => $usage['total_tokens'] ?? 0,
        ]);

        return new SummaryData(
            summary: $summary,
            promptTokens: $usage['prompt_tokens'] ?? 0,
            completionTokens: $usage['completion_tokens'] ?? 0,
            totalTokens: $usage['total_tokens'] ?? 0,
        );
    }

    /**
     * Generate a title for the video based on transcription.
     */
    public function generateTitle(string $transcription, Video $video): string
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.chat_model', 'gpt-4o-mini');

        if (! $apiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        Log::info('Generating title', [
            'video_id' => $video->id,
            'transcription_length' => strlen($transcription),
        ]);

        $logger = ApiLogger::make()
            ->forService('openai')
            ->forUser($this->user)
            ->withCorrelationId($this->correlationId)
            ->withContext([
                'video_id' => $video->id,
                'operation' => 'title_generation',
                'model' => $model,
            ]);

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

        $response = $logger->http()
            ->timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => 50,
                'temperature' => 0.7,
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::warning('Title generation failed, using fallback', [
                'video_id' => $video->id,
                'error' => $error,
            ]);

            return $video->title; // Keep existing title on failure
        }

        $data = $response->json();
        $title = trim($data['choices'][0]['message']['content'] ?? '');

        // Clean up the title
        $title = trim($title, '"\'');
        $title = preg_replace('/[.!?]$/', '', $title);

        Log::info('Title generated', [
            'video_id' => $video->id,
            'title' => $title,
        ]);

        return $title ?: $video->title;
    }

    /**
     * Clean up temporary audio file.
     */
    public function cleanupAudio(string $audioPath): void
    {
        if (file_exists($audioPath)) {
            @unlink($audioPath);
        }
    }
}

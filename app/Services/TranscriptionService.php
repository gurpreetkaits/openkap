<?php

namespace App\Services;

use App\Data\SummaryData;
use App\Data\TranscriptionData;
use App\Http\Integrations\OpenAi\OpenAiConnector;
use App\Http\Integrations\OpenAi\Requests\ChatCompletionRequest;
use App\Http\Integrations\OpenAi\Requests\TranscribeAudioRequest;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TranscriptionService
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

    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Get or create the OpenAI connector with logging configured
     */
    protected function getConnector(): OpenAiConnector
    {
        if ($this->connector === null) {
            $this->connector = new OpenAiConnector;
        }

        // Configure logging context
        $this->connector
            ->forUser($this->user)
            ->withCorrelationId($this->correlationId);

        return $this->connector;
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
        $model = config('services.openai.whisper_model', 'whisper-1');

        $connector = $this->getConnector()
            ->withLogContext([
                'video_id' => $video->id,
                'operation' => 'transcription',
                'model' => $model,
            ]);

        if (! $connector->isConfigured()) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $fileSize = filesize($audioPath);

        Log::info('Starting transcription', [
            'video_id' => $video->id,
            'audio_file_size' => $fileSize,
            'model' => $model,
        ]);

        $request = new TranscribeAudioRequest(
            audioPath: $audioPath,
            model: $model,
            language: 'en',
            responseFormat: 'verbose_json'
        );

        $response = $connector->send($request);

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
        $model = config('services.openai.chat_model', 'gpt-4o-mini');

        $connector = $this->getConnector()
            ->withLogContext([
                'video_id' => $video->id,
                'operation' => 'summary',
                'model' => $model,
            ]);

        if (! $connector->isConfigured()) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        Log::info('Generating summary', [
            'video_id' => $video->id,
            'transcription_length' => strlen($transcription),
            'model' => $model,
        ]);

        $request = ChatCompletionRequest::forSummary($transcription, $model);
        $response = $connector->send($request);

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
        $model = config('services.openai.chat_model', 'gpt-4o-mini');

        $connector = $this->getConnector()
            ->withLogContext([
                'video_id' => $video->id,
                'operation' => 'title_generation',
                'model' => $model,
            ]);

        if (! $connector->isConfigured()) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        Log::info('Generating title', [
            'video_id' => $video->id,
            'transcription_length' => strlen($transcription),
        ]);

        $request = ChatCompletionRequest::forTitle($transcription, $model);
        $response = $connector->send($request);

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

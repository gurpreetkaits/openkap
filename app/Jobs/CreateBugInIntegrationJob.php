<?php

namespace App\Jobs;

use App\Managers\IntegrationManager;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBugInIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(
        public User $user,
        public int $videoId,
        public string $provider,
        public array $bugData,
    ) {}

    public function handle(IntegrationManager $manager): void
    {
        Log::info('CreateBugInIntegrationJob started', [
            'user_id' => $this->user->id,
            'video_id' => $this->videoId,
            'provider' => $this->provider,
            'bug_title' => $this->bugData['bug_title'] ?? null,
        ]);

        try {
            $manager->createBugInIntegration(
                $this->user,
                $this->videoId,
                $this->provider,
                $this->bugData,
            );

            Log::info('CreateBugInIntegrationJob completed', [
                'video_id' => $this->videoId,
                'provider' => $this->provider,
            ]);
        } catch (\Exception $e) {
            Log::error('CreateBugInIntegrationJob failed', [
                'video_id' => $this->videoId,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('CreateBugInIntegrationJob failed permanently', [
            'user_id' => $this->user->id,
            'video_id' => $this->videoId,
            'provider' => $this->provider,
            'error' => $exception?->getMessage(),
        ]);
    }
}

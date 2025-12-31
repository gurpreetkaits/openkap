<?php

namespace App\Console\Commands;

use App\Jobs\ProcessHlsConversionJob;
use App\Models\Video;
use App\Services\HlsService;
use Illuminate\Console\Command;

class ConvertVideosToHlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:convert-hls
                            {--video= : Convert a specific video by ID}
                            {--force : Force re-conversion even if HLS exists}
                            {--sync : Run synchronously instead of queuing}
                            {--limit=50 : Maximum number of videos to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert videos to HLS format for adaptive streaming';

    /**
     * Execute the console command.
     */
    public function handle(HlsService $hlsService): int
    {
        $videoId = $this->option('video');
        $force = $this->option('force');
        $sync = $this->option('sync');
        $limit = (int) $this->option('limit');

        // Single video conversion
        if ($videoId) {
            $video = Video::find($videoId);

            if (! $video) {
                $this->error("Video #{$videoId} not found.");

                return self::FAILURE;
            }

            return $this->convertVideo($video, $hlsService, $force, $sync);
        }

        // Batch conversion
        $query = Video::where('conversion_status', 'completed');

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('hls_status')
                    ->orWhere('hls_status', 'pending')
                    ->orWhere('hls_status', 'failed');
            });
        }

        $videos = $query->limit($limit)->get();

        if ($videos->isEmpty()) {
            $this->info('No videos need HLS conversion.');

            return self::SUCCESS;
        }

        $this->info("Found {$videos->count()} videos to convert to HLS.");

        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $queued = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($videos as $video) {
            try {
                if ($sync) {
                    $result = $this->convertVideoSync($video, $hlsService, $force);
                    if ($result === 'converted') {
                        $queued++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    }
                } else {
                    // Queue the job
                    if (! $force && $video->hls_status === 'completed' && $hlsService->hlsFilesExist($video)) {
                        $skipped++;
                    } else {
                        ProcessHlsConversionJob::dispatch($video);
                        $queued++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("\nFailed to process video #{$video->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($sync) {
            $this->info("Conversion complete: {$queued} converted, {$skipped} skipped, {$failed} failed.");
        } else {
            $this->info("Queued {$queued} videos for HLS conversion. {$skipped} skipped.");
        }

        return self::SUCCESS;
    }

    /**
     * Convert a single video.
     */
    protected function convertVideo(Video $video, HlsService $hlsService, bool $force, bool $sync): int
    {
        $this->info("Processing video #{$video->id}: {$video->title}");

        // Check if MP4 conversion is complete
        if ($video->conversion_status !== 'completed') {
            $this->error("Video MP4 conversion not complete (status: {$video->conversion_status})");

            return self::FAILURE;
        }

        // Check if already converted
        if (! $force && $video->hls_status === 'completed' && $hlsService->hlsFilesExist($video)) {
            $this->info('Video already has HLS. Use --force to re-convert.');

            return self::SUCCESS;
        }

        if ($sync) {
            return $this->convertVideoSync($video, $hlsService, $force) === 'converted'
                ? self::SUCCESS
                : self::FAILURE;
        }

        // Queue the job
        ProcessHlsConversionJob::dispatch($video);
        $this->info('HLS conversion job queued successfully.');

        return self::SUCCESS;
    }

    /**
     * Convert video synchronously with progress output.
     */
    protected function convertVideoSync(Video $video, HlsService $hlsService, bool $force): string
    {
        // Check if already converted
        if (! $force && $video->hls_status === 'completed' && $hlsService->hlsFilesExist($video)) {
            return 'skipped';
        }

        $video->update([
            'hls_status' => 'processing',
            'hls_progress' => 0,
            'hls_error' => null,
        ]);

        try {
            $result = $hlsService->convertToHls($video, function ($progress) use ($video) {
                $video->update(['hls_progress' => $progress]);
            });

            $video->update([
                'hls_status' => 'completed',
                'hls_progress' => 100,
                'hls_path' => $result['hls_path'],
                'hls_converted_at' => now(),
            ]);

            return 'converted';

        } catch (\Exception $e) {
            $video->update([
                'hls_status' => 'failed',
                'hls_error' => substr($e->getMessage(), 0, 1000),
            ]);

            throw $e;
        }
    }
}

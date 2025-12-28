<?php

namespace App\Console\Commands;

use App\Jobs\ProcessVideoZoomEffects;
use App\Models\Video;
use Illuminate\Console\Command;

class ReprocessVideoZoom extends Command
{
    protected $signature = 'video:reprocess-zoom
                            {video_id? : The video ID to reprocess (defaults to latest)}
                            {--sync : Run synchronously instead of queuing}';

    protected $description = 'Reprocess zoom effects for a video';

    public function handle(): int
    {
        $videoId = $this->argument('video_id');

        if ($videoId) {
            $video = Video::find($videoId);
            if (! $video) {
                $this->error("Video {$videoId} not found");

                return 1;
            }
        } else {
            $video = Video::latest()->first();
            if (! $video) {
                $this->error('No videos found');

                return 1;
            }
        }

        $this->info("Video ID: {$video->id}");
        $this->info("Title: {$video->title}");
        $this->info("Duration: {$video->duration}s");
        $this->info('Click events: '.count($video->click_events ?? []));

        if (empty($video->click_events)) {
            $this->warn('No click events found for this video');

            return 1;
        }

        // Show click events
        $this->table(
            ['Timestamp', 'X', 'Y'],
            collect($video->click_events)->map(fn ($c) => [
                $c['timestamp'].'s',
                round($c['normalizedX'] ?? 0.5, 2),
                round($c['normalizedY'] ?? 0.5, 2),
            ])->toArray()
        );

        // Reset status and clear processed video
        $video->update(['processing_status' => 'ready']);
        $video->clearMediaCollection('processed_videos');
        $this->info('Cleared previous processed video');

        if ($this->option('sync')) {
            $this->info('Processing synchronously...');
            ProcessVideoZoomEffects::dispatchSync($video);
            $video->refresh();
            $this->info("Status: {$video->processing_status}");
        } else {
            ProcessVideoZoomEffects::dispatch($video);
            $this->info('Job dispatched to queue');
            $this->info("Run 'docker logs -f screensense-queue' to watch progress");
        }

        return 0;
    }
}

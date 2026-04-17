<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBunnyVideoStatusCommand extends Command
{
    protected $signature = 'bunny:sync-status {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Sync bunny_status for stuck videos by checking actual status from Bunny API';

    public function handle(BunnyStreamService $bunnyService): int
    {
        $stuckVideos = Video::where('storage_type', 'bunny')
            ->whereNotNull('bunny_video_id')
            ->whereNotIn('bunny_status', ['ready', 'error'])
            ->get();

        $this->info("Found {$stuckVideos->count()} videos not in ready/error state.");

        $dryRun = $this->option('dry-run');
        $updated = 0;

        foreach ($stuckVideos as $video) {
            try {
                $status = $bunnyService->getVideoStatus($video->bunny_video_id);
                $actualStatus = $status['status'];

                if ($actualStatus !== $video->bunny_status) {
                    $this->line("Video #{$video->id}: {$video->bunny_status} → {$actualStatus}" . ($dryRun ? ' (dry run)' : ''));

                    if (! $dryRun) {
                        $updateData = ['bunny_status' => $actualStatus];

                        if ($actualStatus === 'ready') {
                            $updateData['bunny_resolution'] = ($status['height'] ?? 720) . 'p';
                            if (! empty($status['size'])) {
                                $updateData['bunny_file_size'] = $status['size'];
                                $updateData['file_size_bytes'] = $status['size'];
                            }
                        }

                        $video->update($updateData);
                        $updated++;
                    }
                } else {
                    $this->line("Video #{$video->id}: already {$actualStatus} on Bunny (local matches)");
                }
            } catch (\Exception $e) {
                $this->error("Video #{$video->id}: Failed - {$e->getMessage()}");
            }
        }

        $this->info($dryRun ? "Dry run complete. {$updated} would be updated." : "Done. Updated {$updated} videos.");

        return 0;
    }
}

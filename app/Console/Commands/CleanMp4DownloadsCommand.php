<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanMp4DownloadsCommand extends Command
{
    protected $signature = 'mp4-downloads:cleanup {--max-age=24 : Maximum age in hours before deletion}';

    protected $description = 'Delete MP4 download files older than the specified age';

    public function handle(): int
    {
        $maxAgeHours = (int) $this->option('max-age');
        $directory = storage_path('app/mp4-downloads');

        if (! is_dir($directory)) {
            $this->info('No mp4-downloads directory found. Nothing to clean.');

            return self::SUCCESS;
        }

        $cutoff = now()->subHours($maxAgeHours)->timestamp;
        $deleted = 0;

        $files = glob($directory.'/*.mp4');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }

        $this->info("Cleaned up {$deleted} MP4 download file(s) older than {$maxAgeHours} hour(s).");

        if ($deleted > 0) {
            Log::info('MP4 downloads cleanup completed', [
                'deleted' => $deleted,
                'max_age_hours' => $maxAgeHours,
            ]);
        }

        return self::SUCCESS;
    }
}

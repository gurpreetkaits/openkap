<?php

namespace App\Console\Commands;

use App\Services\ClipForgeService;
use Illuminate\Console\Command;

class ClipForgeCleanupCommand extends Command
{
    protected $signature = 'clipforge:cleanup {--purge : Delete all files regardless of age}';

    protected $description = 'Clean up stale ClipForge temporary files';

    public function handle(ClipForgeService $clipForgeService): int
    {
        if ($this->option('purge')) {
            $count = $clipForgeService->purgeAllFiles();
            $this->info("Purged {$count} ClipForge file(s).");
        } else {
            $clipForgeService->cleanupStaleFiles(10);
            $this->info('Cleaned up ClipForge files older than 10 minutes.');
        }

        return self::SUCCESS;
    }
}

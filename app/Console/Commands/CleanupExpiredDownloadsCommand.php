<?php

namespace App\Console\Commands;

use App\Managers\DownloadManager;
use Illuminate\Console\Command;

class CleanupExpiredDownloadsCommand extends Command
{
    protected $signature = 'downloads:cleanup';

    protected $description = 'Delete expired download files and mark them as expired';

    public function handle(DownloadManager $downloadManager): int
    {
        $this->info('Cleaning up expired downloads...');

        $count = $downloadManager->cleanupExpired();

        $this->info("Cleaned up {$count} expired download(s).");

        return Command::SUCCESS;
    }
}

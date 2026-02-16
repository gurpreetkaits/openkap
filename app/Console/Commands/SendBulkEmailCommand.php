<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BulkEmailNotification;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class SendBulkEmailCommand extends Command
{
    protected $signature = 'mail:send-bulk 
                            {view? : Email view name (e.g. emails.extension-launch)}
                            {--subject= : Email subject}
                            {--to= : Send to specific email instead of all users}
                            {--limit= : Limit number of users}
                            {--offset=0 : Start from this offset}
                            {--dry-run : Preview without sending}';

    protected $description = 'Queue bulk email notifications to users';

    public function handle(): int
    {
        $view = $this->argument('view') ?? text(
            label: 'Email view name',
            placeholder: 'emails.extension-launch',
            required: true,
            hint: 'View path relative to resources/views/'
        );

        // Check if view exists
        if (!view()->exists($view)) {
            $this->error("View [{$view}] not found!");
            $this->newLine();
            $this->info("Available email views:");
            $this->listEmailViews();
            return self::FAILURE;
        }

        $subject = $this->option('subject') ?? text(
            label: 'Email subject',
            required: true
        );

        $dryRun = $this->option('dry-run');
        $specificEmail = $this->option('to');
        $limit = $this->option('limit');
        $offset = (int) $this->option('offset');

        if ($specificEmail) {
            return $this->sendToEmail($specificEmail, $view, $subject, $dryRun);
        }

        return $this->queueForAllUsers($view, $subject, $limit, $offset, $dryRun);
    }

    private function listEmailViews(): void
    {
        $path = resource_path('views/emails');
        if (!is_dir($path)) {
            $this->warn("  No emails directory found");
            return;
        }

        $files = glob($path . '/*.blade.php');
        foreach ($files as $file) {
            $name = 'emails.' . basename($file, '.blade.php');
            $this->line("  - {$name}");
        }
    }

    private function sendToEmail(string $email, string $view, string $subject, bool $dryRun): int
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create a temporary notifiable for testing
            $user = new User(['email' => $email, 'name' => 'Test User']);
        }

        $this->info("Queueing email to: {$email}");
        $this->info("  View: {$view}");
        $this->info("  Subject: {$subject}");

        if ($dryRun) {
            $this->warn('[DRY RUN] Would queue notification');
            return self::SUCCESS;
        }

        try {
            $user->notify(new BulkEmailNotification($view, $subject));
            $this->info("✓ Queued for {$email}");
            $this->newLine();
            $this->comment("Run `php artisan queue:work` to process the queue");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function queueForAllUsers(string $view, string $subject, ?int $limit, int $offset, bool $dryRun): int
    {
        $query = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id');

        $totalUsers = $query->count();
        
        if ($offset > 0) {
            $query->skip($offset);
        }

        if ($limit) {
            $query->take($limit);
        }

        $users = $query->get();

        $this->info("View: {$view}");
        $this->info("Subject: {$subject}");
        $this->newLine();
        $this->info("Total users in database: {$totalUsers}");
        $this->info("Queueing for {$users->count()} users (offset: {$offset})");
        
        if ($dryRun) {
            $this->warn('[DRY RUN MODE]');
            $this->newLine();
            $this->info("Would queue {$users->count()} notifications");
            return self::SUCCESS;
        }

        if (!confirm("Queue {$users->count()} email notifications?")) {
            $this->warn('Cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $queued = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $user->notify(new BulkEmailNotification($view, $subject));
                $queued++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed for {$user->email}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Completed!");
        $this->info("  Queued: {$queued}");
        if ($failed > 0) {
            $this->error("  Failed: {$failed}");
        }

        $nextOffset = $offset + $users->count();
        if ($nextOffset < $totalUsers) {
            $this->newLine();
            $this->info("To continue, run:");
            $this->comment("  php artisan mail:send-bulk {$view} --subject=\"{$subject}\" --offset={$nextOffset}");
        }

        $this->newLine();
        $this->comment("Run `php artisan queue:work` to process the queue");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BulkEmailNotification;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class SendJiraIntegrationMailCommand extends Command
{
    protected $signature = 'mail:send-jira-integration
                            {--to= : Send to a specific email instead of all users}
                            {--limit= : Limit number of users}
                            {--offset=0 : Start from this offset}
                            {--dry-run : Preview without sending}';

    protected $description = 'Send the Jira Integration announcement email to users';

    public function handle(): int
    {
        $subject = 'Introducing Jira Integration — Detect Bugs & Create Issues from Recordings';
        $view = 'emails.jira-integration';
        $dryRun = $this->option('dry-run');
        $specificEmail = $this->option('to');

        $this->info('Jira Integration Announcement Email');
        $this->info("Subject: {$subject}");
        $this->newLine();

        if ($specificEmail) {
            return $this->sendToUser($specificEmail, $view, $subject, $dryRun);
        }

        return $this->sendToAllUsers($view, $subject, $dryRun);
    }

    private function sendToUser(string $email, string $view, string $subject, bool $dryRun): int
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found: {$email}");

            return self::FAILURE;
        }

        $this->info("Sending to: {$user->name} <{$user->email}>");

        if ($dryRun) {
            $this->warn('[DRY RUN] Would queue notification');

            return self::SUCCESS;
        }

        try {
            $user->notify(new BulkEmailNotification($view, $subject));
            $this->info("Queued successfully for {$user->email}");
            $this->newLine();
            $this->comment('Run `php artisan queue:work` to process the queue');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function sendToAllUsers(string $view, string $subject, bool $dryRun): int
    {
        $limit = $this->option('limit');
        $offset = (int) $this->option('offset');

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

        $this->info("Total users: {$totalUsers}");
        $this->info("Sending to: {$users->count()} users (offset: {$offset})");

        if ($dryRun) {
            $this->warn("[DRY RUN] Would queue {$users->count()} notifications");

            return self::SUCCESS;
        }

        if (! confirm("Queue {$users->count()} email notifications?")) {
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

        $this->info("Queued: {$queued}");
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        $nextOffset = $offset + $users->count();
        if ($nextOffset < $totalUsers) {
            $this->newLine();
            $this->info('To continue:');
            $this->comment("  php artisan mail:send-jira-integration --offset={$nextOffset}");
        }

        $this->newLine();
        $this->comment('Run `php artisan queue:work` to process the queue');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExtensionLaunchEmailCommand extends Command
{
    protected $signature = 'mail:send-extension-launch 
                            {--to= : Send to specific email instead of all users}
                            {--limit= : Limit number of users}
                            {--offset=0 : Start from this offset}
                            {--dry-run : Preview without sending}';

    protected $description = 'Send the Chrome extension launch announcement to all users';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificEmail = $this->option('to');
        $limit = $this->option('limit');
        $offset = (int) $this->option('offset');

        $subject = 'Chrome Extension is Live';

        if ($specificEmail) {
            return $this->sendToEmail($specificEmail, $subject, $dryRun);
        }

        return $this->sendToAllUsers($subject, $limit, $offset, $dryRun);
    }

    private function getHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 48px 24px; background: #ffffff; font-family: 'SF Mono', 'Fira Code', 'JetBrains Mono', Menlo, Monaco, Consolas, monospace; font-size: 14px; line-height: 1.8; color: #1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; margin: 0 auto;">
        <tr>
            <td>
                <h1 style="margin: 0 0 32px; font-size: 16px; font-weight: 600; color: #000; letter-spacing: -0.5px;">screensense.in</h1>
                
                <p style="margin: 0 0 20px;">
                    The <strong>Chrome extension</strong> is live.
                </p>
                
                <p style="margin: 0 0 20px;">
                    Record your screen directly from your browser. No app required.
                </p>
                
                <p style="margin: 0 0 32px; color: #666;">
                    > click extension<br>
                    > hit record<br>
                    > done.
                </p>
                
                <a href="https://chromewebstore.google.com/detail/screensense-screen-record/liloedaflfodiakjaiogpopgkegbkfod" style="display: inline-block; background: #000; color: #fff; text-decoration: none; padding: 10px 20px; font-size: 13px; font-family: inherit;">
                    install extension →
                </a>
                
                <p style="margin: 40px 0 0; font-size: 12px; color: #999;">
                    — screensense
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function sendToEmail(string $email, string $subject, bool $dryRun): int
    {
        $this->info("Sending to: {$email}");

        if ($dryRun) {
            $this->warn('[DRY RUN] Would send email to: ' . $email);
            $this->newLine();
            $this->line('Subject: ' . $subject);
            return self::SUCCESS;
        }

        try {
            Mail::html($this->getHtml(), function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject);
            });

            $this->info("✓ Sent to {$email}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Failed to send to {$email}: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function sendToAllUsers(string $subject, ?int $limit, int $offset, bool $dryRun): int
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

        $this->info("Total users in database: {$totalUsers}");
        $this->info("Sending to {$users->count()} users (offset: {$offset})");
        
        if ($dryRun) {
            $this->warn('[DRY RUN MODE]');
        }

        $this->newLine();

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $sent = 0;
        $failed = 0;
        $html = $this->getHtml();

        foreach ($users as $user) {
            if ($dryRun) {
                $bar->advance();
                $sent++;
                continue;
            }

            try {
                Mail::html($html, function ($message) use ($user, $subject) {
                    $message->to($user->email)
                        ->subject($subject);
                });

                $sent++;

                // Rate limiting - 2 emails per second max
                usleep(500000);

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
        $this->info("  Sent: {$sent}");
        if ($failed > 0) {
            $this->error("  Failed: {$failed}");
        }

        $nextOffset = $offset + $users->count();
        if ($nextOffset < $totalUsers) {
            $this->newLine();
            $this->info("To continue, run:");
            $this->comment("  php artisan mail:send-extension-launch --offset={$nextOffset}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

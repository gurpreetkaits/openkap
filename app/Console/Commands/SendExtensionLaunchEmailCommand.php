<?php

namespace App\Console\Commands;

use App\Data\EmailData;
use App\Data\ProductUpdateEmailData;
use App\Mail\ProductUpdateMail;
use App\Models\User;
use App\Services\ResendService;
use Illuminate\Console\Command;

class SendExtensionLaunchEmailCommand extends Command
{
    protected $signature = 'mail:send-extension-launch 
                            {--to= : Send to specific email instead of all users}
                            {--limit= : Limit number of users}
                            {--offset=0 : Start from this offset}
                            {--dry-run : Preview without sending}';

    protected $description = 'Send the Chrome extension launch announcement to all users';

    public function __construct(
        private ResendService $resendService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificEmail = $this->option('to');
        $limit = $this->option('limit');
        $offset = (int) $this->option('offset');

        // Email content
        $subject = 'ScreenSense Chrome Extension is Here!';
        
        $emailData = ProductUpdateEmailData::featureAnnouncement(
            headline: 'Record Directly from Your Browser',
            description: 'No apps required! Install our Chrome extension and start recording in seconds. Capture your screen, webcam, or both — right from your browser toolbar.',
            features: [
                [
                    'icon' => 'solar:chrome-bold',
                    'title' => 'One-Click Recording',
                    'description' => 'Click the extension icon and start recording instantly. No downloads, no setup.',
                ],
                [
                    'icon' => 'solar:monitor-smartphone-bold',
                    'title' => 'Full Screen or Tab',
                    'description' => 'Record your entire screen, a specific window, or just the current tab.',
                ],
                [
                    'icon' => 'solar:camera-bold',
                    'title' => 'Webcam Overlay',
                    'description' => 'Add a webcam bubble to your recordings for that personal touch.',
                ],
                [
                    'icon' => 'solar:cloud-upload-bold',
                    'title' => 'Instant Upload',
                    'description' => 'Your recordings are automatically uploaded and ready to share.',
                ],
            ],
            ctaText: 'Install Extension',
            ctaUrl: 'https://chromewebstore.google.com/detail/screensense-screen-record/liloedaflfodiakjaiogpopgkegbkfod',
            subheadline: 'The Easiest Way to Record'
        );

        $emailData = new ProductUpdateEmailData(
            headline: $emailData->headline,
            description: $emailData->description,
            features: $emailData->features,
            ctaText: $emailData->ctaText,
            ctaUrl: $emailData->ctaUrl,
            subheadline: $emailData->subheadline,
            badgeText: 'New: Chrome Extension',
            badgeIcon: 'solar:chrome-bold',
            showVisualCard: false,
            previewText: 'Record your screen directly from Chrome — no app required!',
        );

        if ($specificEmail) {
            return $this->sendToEmail($specificEmail, $subject, $emailData, $dryRun);
        }

        return $this->sendToAllUsers($subject, $emailData, $limit, $offset, $dryRun);
    }

    private function sendToEmail(string $email, string $subject, ProductUpdateEmailData $emailData, bool $dryRun): int
    {
        $this->info("Sending to: {$email}");

        if ($dryRun) {
            $this->warn('[DRY RUN] Would send email to: ' . $email);
            return self::SUCCESS;
        }

        try {
            $mailable = new ProductUpdateMail($emailData, $subject);
            $html = $mailable->render();

            $resendEmailData = new EmailData(
                from: config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                to: $email,
                subject: $subject,
                html: $html,
            );

            $response = $this->resendService->sendEmail($resendEmailData);
            $this->info("✓ Sent to {$email} (ID: {$response->id})");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Failed to send to {$email}: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function sendToAllUsers(string $subject, ProductUpdateEmailData $emailData, ?int $limit, int $offset, bool $dryRun): int
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

        foreach ($users as $user) {
            if ($dryRun) {
                $bar->advance();
                $sent++;
                continue;
            }

            try {
                $mailable = new ProductUpdateMail($emailData, $subject);
                $html = $mailable->render();

                $resendEmailData = new EmailData(
                    from: config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                    to: $user->email,
                    subject: $subject,
                    html: $html,
                );

                $this->resendService->forUser($user)->sendEmail($resendEmailData);
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

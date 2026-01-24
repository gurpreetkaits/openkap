<?php

namespace App\Console\Commands;

use App\Data\EmailData;
use App\Data\ProductUpdateEmailData;
use App\Mail\ProductUpdateMail;
use App\Services\ResendService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class SendProductUpdateMailCommand extends Command
{
    protected $signature = 'mail:send-product-update {--to= : Recipient email address}';

    protected $description = 'Send a test product update email';

    public function __construct(
        private ResendService $resendService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $to = $this->option('to') ?? text(
            label: 'Recipient email address',
            placeholder: 'user@example.com',
            required: true,
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address'
        );

        $subject = text(
            label: 'Email subject',
            default: 'Product Update',
            required: true
        );

        $headline = text(
            label: 'Headline',
            default: 'Exciting New Features',
            required: true
        );

        $subheadline = text(
            label: 'Subheadline (optional)',
            default: '',
        );

        $description = text(
            label: 'Description',
            default: 'We have been working hard to bring you new features that will make your experience even better.',
            required: true
        );

        $ctaText = text(
            label: 'CTA button text',
            default: 'Learn More',
            required: true
        );

        $ctaUrl = text(
            label: 'CTA button URL',
            default: config('app.url'),
            required: true
        );

        $showVisualCard = confirm(
            label: 'Show visual card?',
            default: false
        );

        $addFeatures = confirm(
            label: 'Add feature list?',
            default: false
        );

        $features = [];
        if ($addFeatures) {
            $this->info('Add features (enter empty title to finish):');
            while (true) {
                $featureTitle = text(
                    label: 'Feature title (empty to finish)',
                    default: '',
                );

                if (empty($featureTitle)) {
                    break;
                }

                $featureDescription = text(
                    label: 'Feature description',
                    required: true
                );

                $features[] = [
                    'icon' => 'solar:check-circle-bold',
                    'title' => $featureTitle,
                    'description' => $featureDescription,
                ];
            }
        }

        $emailData = new ProductUpdateEmailData(
            headline: $headline,
            description: $description,
            features: $features,
            ctaText: $ctaText,
            ctaUrl: $ctaUrl,
            subheadline: $subheadline ?: null,
            showVisualCard: $showVisualCard,
        );

        $this->info('Sending email...');

        try {
            // Render the mailable to HTML
            $mailable = new ProductUpdateMail($emailData, $subject);
            $html = $mailable->render();

            // Send via ResendService (logs to api_logs)
            $resendEmailData = new EmailData(
                from: config('mail.from.name').' <'.config('mail.from.address').'>',
                to: $to,
                subject: $subject,
                html: $html,
            );

            $response = $this->resendService->sendEmail($resendEmailData);
            $this->info("Product update email sent successfully to {$to}");
            $this->info("Resend ID: {$response->id}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send email: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}

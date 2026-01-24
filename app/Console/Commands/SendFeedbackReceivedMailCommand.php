<?php

namespace App\Console\Commands;

use App\Data\EmailData;
use App\Mail\FeedbackReceivedMail;
use App\Models\Feedback;
use App\Models\User;
use App\Services\ResendService;
use Illuminate\Console\Command;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class SendFeedbackReceivedMailCommand extends Command
{
    protected $signature = 'mail:send-feedback-received {--to= : Recipient email address} {--feedback= : Feedback ID to use}';

    protected $description = 'Send a test feedback received email';

    public function __construct(
        private ResendService $resendService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $to = $this->option('to') ?? text(
            label: 'Recipient email address',
            placeholder: 'admin@example.com',
            required: true,
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address'
        );

        $feedbackId = $this->option('feedback');
        $feedback = null;

        if ($feedbackId) {
            $feedback = Feedback::find($feedbackId);
            if (! $feedback) {
                $this->error("Feedback with ID {$feedbackId} not found.");

                return self::FAILURE;
            }
        } else {
            $feedbackCount = Feedback::count();

            if ($feedbackCount === 0) {
                $this->error('No feedback records found in the database.');
                $this->info('Create some feedback first or run: php artisan db:seed');

                return self::FAILURE;
            }

            $feedbackSource = select(
                label: 'Select feedback source',
                options: [
                    'search' => 'Search by title',
                    'latest' => 'Use latest feedback',
                    'id' => 'Enter feedback ID',
                ]
            );

            if ($feedbackSource === 'latest') {
                $feedback = Feedback::latest()->first();
            } elseif ($feedbackSource === 'id') {
                $id = text(
                    label: 'Enter feedback ID',
                    required: true,
                    validate: fn (string $value) => is_numeric($value) ? null : 'Please enter a valid ID'
                );
                $feedback = Feedback::find($id);
                if (! $feedback) {
                    $this->error("Feedback with ID {$id} not found.");

                    return self::FAILURE;
                }
            } else {
                $feedbackId = search(
                    label: 'Search feedback by title',
                    options: function (string $value) {
                        if (strlen($value) < 1) {
                            return Feedback::limit(10)->pluck('title', 'id')->toArray();
                        }

                        return Feedback::where('title', 'like', "%{$value}%")
                            ->limit(10)
                            ->pluck('title', 'id')
                            ->toArray();
                    },
                    placeholder: 'Start typing to search...',
                );
                $feedback = Feedback::find($feedbackId);
            }
        }

        if (! $feedback) {
            $this->error('Could not find feedback.');

            return self::FAILURE;
        }

        $user = $feedback->user ?? User::first();

        if (! $user) {
            $this->error('No user found for this feedback or in the database.');

            return self::FAILURE;
        }

        $this->info("Sending feedback email for: {$feedback->title}");
        $this->info("From user: {$user->name} ({$user->email})");

        try {
            // Render the mailable to HTML
            $mailable = new FeedbackReceivedMail($feedback, $user);
            $html = $mailable->render();
            $subject = 'New Feedback: '.$feedback->title;

            // Send via ResendService (logs to api_logs)
            $emailData = new EmailData(
                from: config('mail.from.name').' <'.config('mail.from.address').'>',
                to: $to,
                subject: $subject,
                html: $html,
            );

            $response = $this->resendService->sendEmail($emailData);
            $this->info("Feedback received email sent successfully to {$to}");
            $this->info("Resend ID: {$response->id}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send email: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
